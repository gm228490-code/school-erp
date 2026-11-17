<?php
declare(strict_types=1);

/**
 * Serves uploaded files through PHP so that access can be controlled.
 * The uploads/ directory itself is blocked from direct web access via .htaccess.
 */
final class FileController
{
    public function show(): void
    {
        $p = (string)($_GET['p'] ?? '');
        if (!preg_match('#^[a-z0-9_]+/\d{4}/\d{2}/[a-f0-9]{32}\.[a-z0-9]{2,5}$#', $p)) {
            abort(404);
        }
        $dir = explode('/', $p)[0];
        // School logo is public (login page); everything else needs a login
        $public = $dir === 'settings';
        if (!$public && !Auth::check()) {
            abort(403);
        }
        // Portal users may only access photos & homework/document files linked to their own children
        if (!$public && Auth::isPortal() && !$this->portalCanSee($p, $dir)) {
            abort(403);
        }
        $full = UPLOAD_PATH . '/' . $p;
        if (!is_file($full)) {
            abort(404);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($full) ?: 'application/octet-stream';
        $inline = str_starts_with($mime, 'image/') || $mime === 'application/pdf';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($full));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($full) . '"');
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($full);
        exit;
    }

    private function portalCanSee(string $p, string $dir): bool
    {
        $ids = PortalController::studentIds();
        if (!$ids) {
            return false;
        }
        $in = DB::in($ids);
        return match ($dir) {
            'students' => (bool)DB::val("SELECT COUNT(*) FROM students WHERE photo=? AND id IN $in", array_merge([$p], $ids)),
            'homework' => (bool)DB::val("SELECT COUNT(*) FROM homework h JOIN students s ON s.class_id=h.class_id AND (h.section_id IS NULL OR h.section_id=s.section_id) WHERE h.attachment=? AND s.id IN $in", array_merge([$p], $ids)),
            'submissions' => (bool)DB::val("SELECT COUNT(*) FROM homework_submissions WHERE attachment=? AND student_id IN $in", array_merge([$p], $ids)),
            'users' => $p === (Auth::user()['photo'] ?? ''),
            default => false,
        };
    }
}
