<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rules.php';

ini_set('display_errors', '0');
header('Content-Type: text/plain');

function reply(string $word, int $code = 200): void
{
    http_response_code($code);
    echo $word;
    exit;
}

// Step 1: key and caller
$key = (string) ($_POST['key'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!hash_equals(BRIDGE_KEY, $key) || !in_array($ip, ['127.0.0.1', '::1'], true)) {
    reply('FORBIDDEN', 403);
}

try {
    $state = get_state();
    $now = date('Y-m-d H:i:s');

    // Step 2: raw reading or sensor error
    $error = (($_POST['error'] ?? '') === '1');
    $temp = null;
    if (!$error) {
        $raw = $_POST['temp'] ?? '';
        if (!is_numeric($raw) || (float) $raw < -40 || (float) $raw > 80) {
            $error = true;
        } else {
            $temp = round((float) $raw, 1);
        }
    }

    // Step 3: simulation replaces the reading
    $isTest = ((int) $state['sim_on'] === 1 && $state['sim_temp'] !== null);
    if ($isTest) {
        $temp = (float) $state['sim_temp'];
        $error = false;
    }

    // Step 4: heartbeat, then stop if idle or off
    save_state(['last_reading_at' => $now]);
    $running = ((int) $state['running'] === 1 && !empty($state['room_name']));
    if (!$running) {
        save_state(['last_led' => 'OFF']);
        reply('OFF');
    }

    if ($error) {
        save_state([
            'last_sensor_ok' => 0,
            'last_temp'      => null,
            'last_label'     => 'Sensor Error',
            'last_led'       => 'OFF',
        ]);
        reply('OFF');
    }

    // Step 6: hourly check
    check_hourly_record($isTest, $state);

    // Step 7: mode rules (run before saving this reading, so the
    // baseline never includes the reading being judged)
    $result = evaluate_reading($temp, $isTest, $state);

    // Step 5: save the reading and prune old ones
    $ins = db()->prepare('INSERT INTO readings (temp, taken_at, is_test) VALUES (?, ?, ?)');
    $ins->execute([$temp, $now, $isTest ? 1 : 0]);
    $del = db()->prepare('DELETE FROM readings WHERE taken_at < ?');
    $del->execute([date('Y-m-d H:i:s', time() - READINGS_KEEP_MINUTES * 60)]);

    // Silence (a new alert episode clears an old silence)
    $led = $result['led'];
    $fresh = get_state();
    if ($fresh['episode_state'] !== null && $fresh['episode_state'] !== $state['episode_state']) {
        save_state(['silenced' => 0]);
        $fresh['silenced'] = 0;
    }
    if ($led === 'BLINK' && (int) $fresh['silenced'] === 1) {
        $led = 'OFF';
    } elseif ($led !== 'BLINK' && (int) $fresh['silenced'] === 1) {
        save_state(['silenced' => 0]);
    }

    // Step 8: record the event
    if ($result['event'] !== null) {
        $rec = db()->prepare(
            'INSERT INTO records (recorded_at, room_name, mode, temp, min_temp, max_temp, event, is_test)
             VALUES (?, ?, ?, ?, NULL, NULL, ?, ?)'
        );
        $rec->execute([$now, $state['room_name'], $state['mode'], $temp, $result['event'], $isTest ? 1 : 0]);
    }

    // Steps 9 and 10
    save_state([
        'last_temp'      => $temp,
        'last_label'     => $result['label'],
        'last_led'       => $led,
        'last_sensor_ok' => 1,
    ]);
    reply($led);
} catch (Throwable $e) {
    error_log($e->getMessage());
    reply('ERROR', 500);
}