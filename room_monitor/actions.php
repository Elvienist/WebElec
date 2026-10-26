<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function redirect_back(): void
{
    $allowed = ['index.php', 'records.php', 'admins.php'];
    $target = 'index.php';
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        $page = basename((string) parse_url($ref, PHP_URL_PATH));
        if (in_array($page, $allowed, true)) {
            $target = $page;
        }
    }
    header('Location: ' . $target);
    exit;
}

function starting_label(string $mode): string
{
    return [
        'general' => 'Calibrating',
        'fire'    => 'Safe',
        'custom'  => 'Within Range',
    ][$mode] ?? 'Normal';
}

// HOURLY row for the current hour so far, under the current room.
function write_partial_hour(array $state): void
{
    if ((int) $state['running'] !== 1
        || $state['mode'] !== 'general'
        || empty($state['room_name'])) {
        return;
    }

    $isTest = (int) $state['sim_on'];
    $stmt = db()->prepare(
        'SELECT AVG(temp) AS a, MIN(temp) AS n, MAX(temp) AS x
         FROM readings WHERE is_test = ? AND taken_at >= ?'
    );
    $stmt->execute([$isTest, date('Y-m-d H:00:00')]);
    $row = $stmt->fetch();

    if (!$row || $row['a'] === null) {
        return;
    }

    $ins = db()->prepare(
        "INSERT INTO records
         (recorded_at, room_name, mode, temp, min_temp, max_temp, event, is_test)
         VALUES (?, ?, 'general', ?, ?, ?, 'HOURLY', ?)"
    );
    $ins->execute([
        date('Y-m-d H:i:s'),
        $state['room_name'],
        round((float) $row['a'], 1),
        $row['n'],
        $row['x'],
        $isTest,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only.');
}

require_login();
csrf_check();

$action = (string) ($_POST['action'] ?? '');

try {
    $state = get_state();

    switch ($action) {
        case 'set_mode':
            $mode = (string) ($_POST['mode'] ?? '');
            if (!in_array($mode, ['general', 'fire', 'custom'], true)) {
                flash_set('error', 'Choose a valid mode.');
                break;
            }

            $fields = ['mode' => $mode];

            if ($mode === 'custom') {
                $low = filter_var($_POST['custom_low'] ?? '', FILTER_VALIDATE_FLOAT);
                $high = filter_var($_POST['custom_high'] ?? '', FILTER_VALIDATE_FLOAT);
                $style = (string) ($_POST['custom_log_style'] ?? '');
                $interval = filter_var($_POST['custom_interval_min'] ?? '', FILTER_VALIDATE_INT);

                if ($low === false || $high === false || $low >= $high) {
                    flash_set('error', 'The lower limit must be a number below the upper limit.');
                    break;
                }
                if ($low < 0 || $high > 60) {
                    flash_set('error', 'Limits must be between 0 and 60.');
                    break;
                }
                if (!in_array($style, ['crossing', 'periodic'], true)) {
                    flash_set('error', 'Choose a logging style.');
                    break;
                }
                if ($interval === false || $interval < 1 || $interval > 60) {
                    flash_set('error', 'The interval must be 1 to 60 minutes.');
                    break;
                }

                $fields['custom_low'] = $low;
                $fields['custom_high'] = $high;
                $fields['custom_log_style'] = $style;
                $fields['custom_interval_min'] = $interval;
            }

            if ($state['mode'] === 'general' && $mode !== 'general') {
                write_partial_hour($state);
            }

            $fields['last_label'] = starting_label($mode);
            save_state($fields);
            reset_tracking();
            flash_set('success', 'Mode saved.');
            break;

        case 'set_room':
            if (!empty($state['room_name'])) {
                flash_set('error', 'A room is already set. Use Change Room first.');
                break;
            }
            $room = trim((string) ($_POST['room_name'] ?? ''));
            $len = mb_strlen($room);
            if ($len < 1 || $len > 100) {
                flash_set('error', 'Room name must be 1 to 100 characters.');
                break;
            }
            save_state([
                'room_name'  => $room,
                'running'    => 1,
                'last_led'   => 'OFF',
                'last_label' => starting_label($state['mode']),
            ]);
            reset_tracking();
            flash_set('success', 'Room set. Monitoring started.');
            break;

        case 'change_room':
            if (empty($state['room_name'])) {
                flash_set('error', 'No room is set.');
                break;
            }
            write_partial_hour($state);
            save_state([
                'running'    => 0,
                'room_name'  => null,
                'last_led'   => 'OFF',
                'last_label' => 'Idle',
            ]);
            reset_tracking();
            flash_set('success', 'Room cleared. Enter a new room name to start.');
            break;

        case 'toggle_system':
            if ((int) $state['running'] === 1) {
                write_partial_hour($state);
                save_state([
                    'running'    => 0,
                    'last_led'   => 'OFF',
                    'last_label' => 'Off',
                ]);
                reset_tracking();
                flash_set('success', 'System off.');
            } elseif (empty($state['room_name'])) {
                flash_set('error', 'Enter a room name first.');
            } else {
                save_state([
                    'running'    => 1,
                    'last_led'   => 'OFF',
                    'last_label' => starting_label($state['mode']),
                ]);
                reset_tracking();
                flash_set('success', 'System on.');
            }
            break;

        case 'silence':
            if ((int) $state['running'] === 1 && $state['last_led'] === 'BLINK') {
                save_state(['silenced' => 1, 'last_led' => 'OFF']);
                flash_set('success', 'LED silenced until the alert ends.');
            } else {
                flash_set('error', 'Nothing to silence.');
            }
            break;
                case 'test_sim':
            if ((string) ($_POST['sim_on'] ?? '0') === '1') {
                save_state([
                    'sim_on'   => 1,
                    'sim_temp' => $state['sim_temp'] !== null ? (float) $state['sim_temp'] : SIM_NORMAL,
                ]);
                flash_set('success', 'Simulation on.');
            } else {
                save_state(['sim_on' => 0, 'sim_temp' => null, 'episode_state' => null]);
                flash_set('success', 'Simulation off.');
            }
            break;

        case 'test_scenario':
            $scenario = (string) ($_POST['scenario'] ?? '');
            $temps = [
                'normal'   => SIM_NORMAL,
                'elevated' => SIM_ELEVATED,
                'fire'     => SIM_FIRE,
                'too_low'  => (float) $state['custom_low'] - 3,
                'too_high' => (float) $state['custom_high'] + 3,
            ];
            if ($scenario === 'spike') {
                $cut = date('Y-m-d H:i:s', time() - BASELINE_MINUTES * 60);
                $stmt = db()->prepare('SELECT COUNT(*) FROM readings WHERE is_test = 1 AND taken_at >= ?');
                $stmt->execute([$cut]);
                if ((int) $stmt->fetchColumn() < 60) {
                    $ins = db()->prepare('INSERT INTO readings (temp, taken_at, is_test) VALUES (29.0, ?, 1)');
                    for ($i = 1; $i <= 120; $i++) {
                        $ins->execute([date('Y-m-d H:i:s', time() - $i * 12)]);
                    }
                }
                $stmt = db()->prepare('SELECT AVG(temp) FROM readings WHERE is_test = 1 AND taken_at >= ?');
                $stmt->execute([$cut]);
                $temps['spike'] = round((float) $stmt->fetchColumn() + SIM_SPIKE_ADD, 1);
            }
            if (!isset($temps[$scenario])) {
                flash_set('error', 'Unknown scenario.');
                break;
            }
            save_state(['sim_on' => 1, 'sim_temp' => $temps[$scenario]]);
            flash_set('success', 'Scenario applied.');
            break;

        case 'test_manual':
            $t = filter_var($_POST['temp'] ?? '', FILTER_VALIDATE_FLOAT);
            if ($t === false || $t < 0 || $t > 60) {
                flash_set('error', 'Enter a temperature from 0 to 60.');
                break;
            }
            save_state(['sim_on' => 1, 'sim_temp' => round($t, 1)]);
            flash_set('success', 'Temperature set.');
            break;

        case 'test_force_hourly':
            if ($state['mode'] !== 'general') {
                flash_set('error', 'Switch to General mode first.');
                break;
            }
            if (empty($state['room_name'])) {
                flash_set('error', 'Set a room first.');
                break;
            }
            $isTest = (int) $state['sim_on'];
            $stmt = db()->prepare(
                'SELECT AVG(temp) AS a, MIN(temp) AS n, MAX(temp) AS x
                 FROM readings WHERE is_test = ? AND taken_at >= ?'
            );
            $stmt->execute([$isTest, date('Y-m-d H:00:00')]);
            $row = $stmt->fetch();
            if (!$row || $row['a'] === null) {
                flash_set('error', 'No readings yet.');
                break;
            }
            db()->prepare(
                "INSERT INTO records (recorded_at, room_name, mode, temp, min_temp, max_temp, event, is_test)
                 VALUES (?, ?, 'general', ?, ?, ?, 'HOURLY', ?)"
            )->execute([date('Y-m-d H:i:s'), $state['room_name'], round((float) $row['a'], 1), $row['n'], $row['x'], $isTest]);
            flash_set('success', 'Hourly record written.');
            break;

        case 'test_clear':
            db()->exec('DELETE FROM records WHERE is_test = 1');
            db()->exec('DELETE FROM readings WHERE is_test = 1');
            save_state(['sim_on' => 0, 'sim_temp' => null, 'episode_state' => null]);
            flash_set('success', 'Test data cleared.');
            break;

        default:
            flash_set('error', 'Unknown action.');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash_set('error', 'Something went wrong. Please try again.');
}

redirect_back();
