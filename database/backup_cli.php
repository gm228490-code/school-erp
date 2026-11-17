<?php
declare(strict_types=1);

/**
 * Command-line database backup, e.g. from cron:
 *   0 2 * * * php /path/to/school/database/backup_cli.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_PATH . '/controllers/SystemController.php';

$dir = STORAGE_PATH . '/backups';
$name = 'backup_' . date('Ymd_His') . '.sql';
SystemController::dump($dir . '/' . $name);

$files = glob($dir . '/backup_*.sql') ?: [];
rsort($files);
foreach (array_slice($files, max(1, (int)setting('backup_keep', '10'))) as $old) {
    @unlink($old);
}
audit('backup', 'system', $name, 'CLI backup');
echo "Backup written: $name\n";
