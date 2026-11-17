<?php
declare(strict_types=1);

final class NotificationsController
{
    public function index(): void
    {
        $uid = Auth::id();
        $p = paginate('SELECT COUNT(*) FROM notifications WHERE user_id=?', 'SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC', [$uid], 25);
        view('notifications/index', ['p' => $p, 'title' => t('Notifications')], Auth::isPortal() ? 'portal' : 'main');
    }

    public function feed(): void
    {
        $rows = DB::all('SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 10', [Auth::id()]);
        foreach ($rows as &$r) {
            $r['ago'] = self::ago($r['created_at']);
        }
        json_out(['ok' => true, 'items' => $rows, 'unread' => (int)DB::val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [Auth::id()])]);
    }

    public function count(): void
    {
        json_out(['ok' => true, 'unread' => (int)DB::val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [Auth::id()])]);
    }

    public function open(): void
    {
        $n = DB::row('SELECT * FROM notifications WHERE id=? AND user_id=?', [input_int('id', 0), Auth::id()]);
        if (!$n) {
            abort(404);
        }
        DB::update('notifications', ['is_read' => 1], 'id=?', [$n['id']]);
        $link = (string)$n['link'];
        redirect($link !== '' && str_starts_with($link, 'index.php?') ? base_url() . '/' . $link : url('notifications'));
    }

    public function read_all(): void
    {
        DB::q('UPDATE notifications SET is_read=1 WHERE user_id=?', [Auth::id()]);
        flash('success', t('All notifications marked as read.'));
        back();
    }

    public function delete(): void
    {
        DB::q('DELETE FROM notifications WHERE id=? AND user_id=?', [input_int('id', 0), Auth::id()]);
        json_out(['ok' => true]);
    }

    public static function ago(string $dt): string
    {
        $d = time() - strtotime($dt);
        return match (true) {
            $d < 60 => t('just now'),
            $d < 3600 => floor($d / 60) . ' ' . t('min ago'),
            $d < 86400 => floor($d / 3600) . ' ' . t('hours ago'),
            $d < 604800 => floor($d / 86400) . ' ' . t('days ago'),
            default => fmt_date($dt),
        };
    }
}
