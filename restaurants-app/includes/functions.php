<?php

/**
 * Helper Functions
 */

function dd(...$vars): void
{
    foreach ($vars as $var) {
        var_dump($var);
    }
    die;
}

function env(string $key, $default = null)
{
    return Database::getConfig($key, $default);
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function jsonResponse(bool $success, $data = null, string $error = null, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    exit;
}

function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function t(string $key, array $params = []): string
{
    static $lang = null;
    
    if ($lang === null) {
        $langFile = __DIR__ . '/../lang/' . (($_SESSION['lang'] ?? 'en') . '.php');
        $lang = file_exists($langFile) ? require $langFile : [];
    }
    
    $translation = $lang[$key] ?? $key;
    
    foreach ($params as $placeholder => $value) {
        $translation = str_replace('{' . $placeholder . '}', $value, $translation);
    }
    
    return $translation;
}

function formatDate(string $date, string $format = 'Y-m-d H:i'): string
{
    return date($format, strtotime($date));
}

function slugify(string $text): string
{
    $text = preg_replace('/[^a-z0-9]+/i', '-', strtolower($text));
    return trim($text, '-');
}