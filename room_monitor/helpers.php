<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function label_tone(string $label): string {
    switch ($label) {
        case 'Normal':
        case 'Safe':
        case 'Within Range':
            return 'good';
        case 'Warming':
        case 'Elevated':
            return 'warn';
        case 'Spike':
        case 'Fire Detected':
        case 'Too High':
            return 'danger';
        case 'Too Low':
            return 'low';
        default:
            return 'muted';
    }
}

function live_status(): array {
    $state = get_state();
    $now = time();
    $last_reading = $state['last_reading_at'] ? strtotime($state['last_reading_at']) : 0;
    $signal_ok = ($last_reading > 0 && ($now - $last_reading) <= NO_SIGNAL_SECONDS);

    $running_state = 'off';
    if ((int)$state['running'] === 1 && !empty($state['room_name'])) {
        $running_state = 'running';
    } elseif ((int)$state['running'] !== 1 && !empty($state['room_name'])) {
        $running_state = 'off';
    } else {
        $running_state = 'idle';
    }

    $label = $state['last_label'];
    $temp = $state['last_temp'];
    $led = $state['last_led'];
    $tone = label_tone($label);

    if (!$signal_ok && $running_state === 'running') {
        $label = 'No Signal';
        $tone = 'muted';
        $led = 'OFF';
        $temp = null;
    } elseif ($running_state === 'off') {
        $label = 'Off';
        $temp = null;
        $led = 'OFF';
    } elseif ($running_state === 'idle') {
        $label = 'Idle';
        $temp = null;
        $led = 'OFF';
    }

    return [
        'ok' => true,
        'state' => $running_state,
        'temp' => $temp !== null ? (float)$temp : null,
        'label' => $label,
        'tone' => $tone,
        'mode' => $state['mode'],
        'room' => $state['room_name'],
        'led' => $led,
        'signal' => $signal_ok,
        'updated_at' => $state['last_reading_at'] ? date('H:i:s', strtotime($state['last_reading_at'])) : '--',
        'sim_on' => (bool)$state['sim_on'],
        'silenced' => (bool)$state['silenced']
    ];
}