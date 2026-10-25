<?php
declare(strict_types=1);

ini_set('display_errors', '0');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

function reply(string $message, int $status = 200): void
{
    http_response_code($status);
    echo $message;
    exit;
}

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
    reply('FORBIDDEN', 403);
}

$key = $_POST['key'] ?? '';

if (!is_string($key) || !hash_equals(BRIDGE_KEY, $key)) {
    reply('FORBIDDEN', 403);
}

try {
    $state = get_state();

    save_state([
        'last_reading_at' => date('Y-m-d H:i:s'),
    ]);

    if ((int) $state['running'] !== 1) {
        save_state(['last_led' => 'OFF']);
        reply('OFF');
    }

    if (($_POST['error'] ?? '') === '1') {
        save_state([
            'last_sensor_ok' => 0,
            'last_led' => 'OFF',
        ]);
        reply('OFF');
    }

    $tempInput = $_POST['temp'] ?? null;

    if (
        !is_string($tempInput) ||
        $tempInput === '' ||
        !is_numeric($tempInput)
    ) {
        save_state([
            'last_sensor_ok' => 0,
            'last_led' => 'OFF',
        ]);
        reply('OFF');
    }

    $temp = (float) $tempInput;

    if (!is_finite($temp) || $temp < -40 || $temp > 80) {
        save_state([
            'last_sensor_ok' => 0,
            'last_led' => 'OFF',
        ]);
        reply('OFF');
    }

    save_state([
        'last_temp' => $temp,
        'last_sensor_ok' => 1,
        'last_led' => 'OFF',
    ]);

    reply('OFF');

} catch (Throwable $e) {
    reply('ERROR', 500);
}