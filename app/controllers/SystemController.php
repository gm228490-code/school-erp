<?php
declare(strict_types=1);

final class SystemController
{
    private function dir(): string
    {
        return STORAGE_PATH . '/backups';
    }

    private function files(): array
    {
        $out = [];
        foreach (glob($this->dir() . '/*.sql') ?: [] as $f) {
            $out[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
        }
        usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    private function safeName(string $n): string
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+\.sql$/', $n) || !is_file($this->dir() . '/' . $n)) {
            abort(404);
        }
        return $this->dir() . '/' . $n;
    }

    public function backup(): void
    {
        authorize('system.backup');
        $stats = DB::all("SELECT table_name AS t, table_rows AS r, ROUND((data_length+index_length)/1024) AS kb FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY table_name");
        view('system/backup', ['files' => $this->files(), 'stats' => $stats, 'title' => t('Backup & Restore')]);
    }

    /** Create a full SQL dump (structure + data) using PDO only */
    public static function dump(string $file): void
    {
        $pdo = DB::pdo();
        $fh = fopen($file, 'w');
        if (!$fh) {
            throw new RuntimeException('Cannot write backup file');
        }
        fwrite($fh, "-- School ERP database backup\n-- Created: " . date('Y-m-d H:i:s') . "\n-- Version: " . APP_VERSION . "\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        foreach (DB::col('SHOW TABLES') as $table) {
            $create = DB::row("SHOW CREATE TABLE `$table`");
            fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n\n");
            $st = $pdo->query("SELECT * FROM `$table`");
            $batch = [];
            $cols = null;
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $cols ??= '`' . implode('`,`', array_keys($row)) . '`';
                $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row)) . ')';
                if (count($batch) >= 200) {
                    fwrite($fh, "INSERT INTO `$table` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                fwrite($fh, "INSERT INTO `$table` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            fwrite($fh, "\n");
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    public function create(): void
    {
        authorize('system.backup');
        @set_time_limit(300);
        $name = 'backup_' . date('Ymd_His') . '.sql';
        self::dump($this->dir() . '/' . $name);
        // retention
        $keep = max(1, (int)setting('backup_keep', '10'));
        foreach (array_slice($this->files(), $keep) as $old) {
            @unlink($this->dir() . '/' . $old['name']);
        }
        audit('backup', 'system', $name);
        flash('success', t('Backup created') . ': ' . $name);
        redirect(url('system/backup'));
    }

    public function download(): void
    {
        authorize('system.backup');
        $f = $this->safeName((string)input('file'));
        audit('download', 'system', basename($f));
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($f) . '"');
        header('Content-Length: ' . filesize($f));
        readfile($f);
        exit;
    }

    public function delete(): void
    {
        authorize('system.backup');
        $f = $this->safeName((string)input('file'));
        unlink($f);
        audit('delete', 'system', basename($f));
        json_out(['ok' => true, 'message' => t('Backup deleted.')]);
    }

    /** Split SQL text into statements, respecting quotes and comments */
    public static function splitSql(string $sql): array
    {
        $out = [];
        $buf = '';
        $len = strlen($sql);
        $q = null;
        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($q) {
                $buf .= $c;
                if ($c === '\\') {
                    $buf .= $sql[++$i] ?? '';
                } elseif ($c === $q) {
                    $q = null;
                }
                continue;
            }
            if ($c === '-' && ($sql[$i + 1] ?? '') === '-' && in_array($sql[$i + 2] ?? "\n", [' ', "\t", "\n", "\r"], true)) {
                $i = strpos($sql, "\n", $i) ?: $len;
                continue;
            }
            if ($c === '/' && ($sql[$i + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') {
                $q = $c;
            }
            if ($c === ';') {
                if (trim($buf) !== '') {
                    $out[] = trim($buf);
                }
                $buf = '';
                continue;
            }
            $buf .= $c;
        }
        if (trim($buf) !== '') {
            $out[] = trim($buf);
        }
        return $out;
    }

    public function restore(): void
    {
        authorize('system.backup');
        if (!Auth::isSuper()) {
            abort(403, t('Only the Super Admin can restore the database.'));
        }
        if (input('confirm_text') !== 'RESTORE') {
            throw new ValidationException(['confirm_text' => 'Type RESTORE to confirm.']);
        }
        if ($name = (string)input('file')) {
            $path = $this->safeName($name);
        } else {
            $f = $_FILES['sql'] ?? null;
            if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']) || !preg_match('/\.sql$/i', $f['name'])) {
                throw new ValidationException(['sql' => 'Upload a .sql backup file.']);
            }
            $path = $f['tmp_name'];
        }
        @set_time_limit(600);
        // safety backup first
        self::dump($this->dir() . '/pre_restore_' . date('Ymd_His') . '.sql');
        $sql = file_get_contents($path);
        $statements = self::splitSql((string)$sql);
        $pdo = DB::pdo();
        $n = 0;
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach ($statements as $stmt) {
                if (preg_match('/^(CREATE\s+DATABASE|USE\s|DROP\s+DATABASE)/i', $stmt)) {
                    continue; // never switch or drop databases
                }
                $pdo->exec($stmt);
                $n++;
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        } catch (Throwable $e) {
            error_log('[RESTORE] ' . $e->getMessage());
            flash('danger', t('Restore stopped with an error after') . " $n " . t('statements') . ': ' . mb_substr($e->getMessage(), 0, 200) . ' ' . t('A safety backup was saved before restoring.'));
            redirect(url('system/backup'));
        }
        audit('restore', 'system', null, "Executed $n statements");
        flash('success', sprintf(t('Database restored successfully (%d statements). Please log in again if your session ends.'), $n));
        redirect(url('system/backup'));
    }

    public function errors(): void
    {
        authorize('system.audit');
        $file = STORAGE_PATH . '/logs/error.log';
        $lines = [];
        if (is_file($file)) {
            $size = filesize($file);
            $fh = fopen($file, 'r');
            fseek($fh, max(0, $size - 200000));
            $lines = array_slice(explode("\n", (string)stream_get_contents($fh)), -400);
            fclose($fh);
        }
        view('system/errors', ['lines' => array_reverse(array_filter($lines)), 'size' => is_file($file) ? filesize($file) : 0, 'title' => t('Error Log')]);
    }

    public function clear_errors(): void
    {
        authorize('system.audit');
        if (!Auth::isSuper()) {
            abort(403);
        }
        file_put_contents(STORAGE_PATH . '/logs/error.log', '');
        audit('clear', 'error_log');
        flash('success', t('Error log cleared.'));
        redirect(url('system/errors'));
    }
}
