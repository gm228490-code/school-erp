<?php
declare(strict_types=1);

/**
 * School ERP - Front controller.
 * Routes: index.php?r=module/action   (module => app/controllers/{Module}Controller.php)
 */
require __DIR__ . '/app/bootstrap.php';

$route = (string)($_GET['r'] ?? 'dashboard');
if (!preg_match('#^[a-z_]+(/[a-z_]+)?$#', $route)) {
    $route = 'dashboard';
}
[$module, $action] = array_pad(explode('/', $route), 2, 'index');

$public = ['auth/login', 'auth/logout', 'lang/switch', 'file/show'];

try {
    if (is_post()) {
        csrf_verify();
    }
    if (!in_array("$module/$action", $public, true)) {
        if (!Auth::check()) {
            if (is_ajax()) {
                json_out(['ok' => false, 'message' => 'Session expired. Please log in again.'], 401);
            }
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '';
            redirect(url('auth/login'));
        }
        // Portal users (parents/students) are confined to the portal
        // fees/receipt, fees/ledger and exams/report_card check portal ownership themselves; other actions require staff permissions.
        $portalAllowed = ['portal', 'auth', 'notifications', 'lang', 'file', 'fees', 'exams'];
        if (Auth::isPortal() && !in_array($module, $portalAllowed, true)) {
            redirect(url('portal'));
        }
    }

    $class = str_replace('_', '', ucwords($module, '_')) . 'Controller';
    $file = APP_PATH . '/controllers/' . $class . '.php';
    if (!is_file($file)) {
        abort(404);
    }
    require_once $file;
    $method = method_exists($class, $action) ? $action : lcfirst(str_replace('_', '', ucwords($action, '_')));
    if (!class_exists($class) || !method_exists($class, $method) || str_starts_with($method, '_') || in_array(strtolower($method), ['__construct', 'render', 'guard', 'student', 'children'], true)) {
        abort(404);
    }
    $rm = new ReflectionMethod($class, $method);
    if (!$rm->isPublic() || $rm->isStatic()) {
        abort(404);
    }
    (new $class())->$method();
} catch (ValidationException $e) {
    if (is_ajax()) {
        json_out(['ok' => false, 'message' => t($e->getMessage()), 'errors' => $e->errors], 422);
    }
    keep_old();
    $_SESSION['_errors'] = $e->errors;
    flash('danger', t($e->getMessage()) . ' ' . implode(' ', array_map('t', $e->errors)));
    back();
} catch (HttpException $e) {
    $code = $e->getCode() ?: 500;
    if (is_ajax()) {
        json_out(['ok' => false, 'message' => $e->getMessage()], $code);
    }
    http_response_code($code);
    echo render('errors/error', ['code' => $code, 'message' => $e->getMessage()], Auth::user() ? 'main' : 'blank');
} catch (PDOException $e) {
    error_log('[DB] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $msg = str_starts_with((string)$e->getCode(), '23')
        ? (str_contains($e->getMessage(), 'Duplicate') ? t('A record with the same unique value already exists.') : t('This record is linked to other records and cannot be changed or deleted.'))
        : t('A database error occurred. Please try again.');
    if (is_ajax()) {
        json_out(['ok' => false, 'message' => $msg], 400);
    }
    if (str_starts_with((string)$e->getCode(), '23')) {
        flash('danger', $msg);
        back();
    }
    http_response_code(500);
    echo render('errors/error', ['code' => 500, 'message' => $msg], Auth::user() ? 'main' : 'blank');
} catch (Throwable $e) {
    error_log('[APP] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    $msg = !empty($GLOBALS['CONFIG']['debug']) ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : t('An unexpected error occurred. It has been logged.');
    if (is_ajax()) {
        json_out(['ok' => false, 'message' => $msg], 500);
    }
    http_response_code(500);
    try {
        echo render('errors/error', ['code' => 500, 'message' => $msg], 'blank');
    } catch (Throwable) {
        echo 'Server error';
    }
}
