<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function evaluate_reading(float $temp, bool $is_test, array $state): array {
    $now = date('Y-m-d H:i:s');
    $mode = $state['mode'];
    
    $label = 'Normal';
    $led = 'OFF';
    $event = null;
    $episode_state = $state['episode_state'];

    $pdo = db();
    $baseline_cutoff = date('Y-m-d H:i:s', strtotime('-' . BASELINE_MINUTES . ' minutes'));
    $stmt = $pdo->prepare("SELECT AVG(temp) as avg_temp FROM readings WHERE is_test = ? AND taken_at >= ? AND taken_at < ?");
    $stmt->execute([$is_test ? 1 : 0, $baseline_cutoff, $now]);
    $res = $stmt->fetch();
    $baseline = $res['avg_temp'] !== null ? (float)$res['avg_temp'] : null;

    if ($mode === 'general') {
        if ($state['calibration_start'] === null) {
            save_state(['calibration_start' => $now]);
            $calib_start_time = time();
        } else {
            $calib_start_time = strtotime($state['calibration_start']);
        }

        if ((time() - $calib_start_time) < (CALIBRATE_MINUTES * 60)) {
            return [
                'label' => 'Calibrating',
                'led' => 'OFF',
                'event' => null
            ];
        }

        if ($baseline === null) {
            $baseline = $temp;
        }

        $warming_threshold = $baseline + WARM_RISE;
        $spike_threshold = $baseline + SPIKE_RISE;
        $spike_end_threshold = $spike_threshold - HYSTERESIS;

        $in_spike = ($episode_state === 'SPIKE');

        if (!$in_spike && $temp >= $spike_threshold) {
            $label = 'Spike';
            $led = 'BLINK';
            $event = 'SPIKE';
            $episode_state = 'SPIKE';
        } elseif ($in_spike && $temp >= $spike_end_threshold) {
            $label = 'Spike';
            $led = 'BLINK';
        } else {
            if ($in_spike && $temp < $spike_end_threshold) {
                $episode_state = null;
            }

            if ($temp >= warming_threshold) {
                $label = 'Warming';
                $led = 'OFF';
            } else {
                $label = 'Normal';
                $led = 'OFF';
            }
        }

    } elseif ($mode === 'fire') {
        $in_fire = ($episode_state === 'FIRE');
        $in_elevated = ($episode_state === 'POSSIBLE_FIRE');

        $fire_end_threshold = FIRE_TEMP - HYSTERESIS;
        $elevated_end_threshold = POSSIBLE_FIRE_TEMP - HYSTERESIS;

        if ($temp >= FIRE_TEMP || ($in_fire && $temp >= $fire_end_threshold)) {
            $label = 'Fire Detected';
            $led = 'BLINK';
            if (!$in_fire) {
                $event = 'FIRE';
                $episode_state = 'FIRE';
            }
        } elseif ($temp >= POSSIBLE_FIRE_TEMP || (($in_fire || $in_elevated) && $temp >= $elevated_end_threshold)) {
            $label = 'Elevated';
            $led = 'OFF';
            if (!$in_fire && !$in_elevated) {
                $event = 'POSSIBLE_FIRE';
                $episode_state = 'POSSIBLE_FIRE';
            } elseif ($in_fire && $temp < $fire_end_threshold) {
                $episode_state = 'POSSIBLE_FIRE';
            }
        } else {
            $label = 'Safe';
            $led = 'OFF';
            $episode_state = null;
        }

    } elseif ($mode === 'custom') {
        $low = (float)$state['custom_low'];
        $high = (float)$state['custom_high'];
        $log_style = $state['custom_log_style'];
        $interval_min = (int)$state['custom_interval_min'];

        $low_threshold = $low - CUSTOM_HYSTERESIS;
        $high_threshold = $high + CUSTOM_HYSTERESIS;

        $in_high = ($episode_state === 'TOO_HIGH');
        $in_low = ($episode_state === 'TOO_LOW');

        if ($temp > $high || ($in_high && $temp > $high_threshold)) {
            $label = 'Too High';
            $led = 'BLINK';
            if (!$in_high) {
                $episode_state = 'TOO_HIGH';
                if ($log_style === 'crossing') {
                    $event = 'LIMIT_HIGH';
                }
            }
        } elseif ($temp < $low || ($in_low && $temp < $low_threshold)) {
            $label = 'Too Low';
            $led = 'BLINK';
            if (!$in_low) {
                $episode_state = 'TOO_LOW';
                if ($log_style === 'crossing') {
                    $event = 'LIMIT_LOW';
                }
            }
        } else {
            $label = 'Within Range';
            $led = 'OFF';
            $episode_state = null;
        }

        if ($log_style === 'periodic') {
            $last_periodic = $state['last_periodic_at'];
            $time_diff = $last_periodic ? (time() - strtotime($last_periodic)) : PHP_INT_MAX;
            if ($time_diff >= ($interval_min * 60)) {
                $event = 'PERIODIC';
                save_state(['last_periodic_at' => $now]);
            }
        }
    }

    save_state(['episode_state' => $episode_state]);

    return [
        'label' => $label,
        'led' => $led,
        'event' => $event
    ];
}

function check_hourly_record(bool $is_test, array $state): void {
    if ($state['mode'] !== 'general') return;

    $now_time = time();
    $current_hour_start = date('Y-m-d H:00:00', $now_time);
    $last_bucket = $state['last_hour_bucket'];

    if ($last_bucket === null) {
        save_state(['last_hour_bucket' => $current_hour_start]);
        return;
    }

    if ($current_hour_start !== $last_bucket) {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT AVG(temp) as avg_t, MIN(temp) as min_t, MAX(temp) as max_t FROM readings WHERE is_test = ? AND taken_at >= ? AND taken_at < ?");
        $stmt->execute([$is_test ? 1 : 0, $last_bucket, $current_hour_start]);
        $row = $stmt->fetch();

        if ($row && $row['avg_t'] !== null) {
            $insert = $pdo->prepare("INSERT INTO records (recorded_at, room_name, mode, temp, min_temp, max_temp, event, is_test) VALUES (?, ?, 'general', ?, ?, ?, 'HOURLY', ?)");
            $insert->execute([
                $last_bucket,
                $state['room_name'],
                (float)$row['avg_t'],
                (float)$row['min_t'],
                (float)$row['max_t'],
                $is_test ? 1 : 0
            ]);
        }

        save_state(['last_hour_bucket' => $current_hour_start]);
    }
}