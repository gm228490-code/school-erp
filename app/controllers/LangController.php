<?php
declare(strict_types=1);

final class LangController
{
    public function switch(): void
    {
        $lang = input('lang') === 'ur' ? 'ur' : 'en';
        $_SESSION['lang'] = $lang;
        if (Auth::check()) {
            DB::update('users', ['lang' => $lang], 'id=?', [Auth::id()]);
        }
        back();
    }

    public function theme(): void
    {
        $theme = input('theme') === 'dark' ? 'dark' : 'light';
        DB::update('users', ['theme' => $theme], 'id=?', [Auth::id()]);
        json_out(['ok' => true]);
    }

    /** Switch the academic session being viewed */
    public function session(): void
    {
        $id = input_int('id');
        if ($id && DB::val('SELECT id FROM academic_sessions WHERE id=?', [$id])) {
            $_SESSION['session_id'] = $id;
        }
        back();
    }
}
