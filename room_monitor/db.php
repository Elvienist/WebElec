<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=3307;dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("SET time_zone = '+08:00'");

    return $pdo;
}

function get_state(): array
{
    $stmt = db()->prepare(
        'SELECT * FROM system_state WHERE id = :id LIMIT 1'
    );

    $stmt->execute(['id' => 1]);
    $state = $stmt->fetch();

    if ($state === false) {
        throw new RuntimeException(
            'The system_state row with id = 1 is missing.'
        );
    }

    return $state;
}

function save_state(array $fields): void
{
    $allowed = [
        'running',
        'mode',
        'room_name',
        'silenced',
        'sim_on',
        'sim_temp',
        'custom_low',
        'custom_high',
        'custom_log_style',
        'custom_interval_min',
        'last_temp',
        'last_label',
        'last_led',
        'last_reading_at',
        'last_sensor_ok',
        'calibration_start',
        'episode_state',
        'last_hour_bucket',
        'last_periodic_at',
    ];

    if ($fields === []) {
        return;
    }

    $updates = [];
    $params = [];

    foreach ($fields as $field => $value) {
        if (!in_array($field, $allowed, true)) {
            throw new InvalidArgumentException(
                'Invalid system-state field: ' . $field
            );
        }

        $updates[] = "`$field` = :$field";
        $params[$field] = $value;
    }

    $params['id'] = 1;

    $sql = 'UPDATE system_state SET '
        . implode(', ', $updates)
        . ' WHERE id = :id';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
}

function reset_tracking(): void
{
    save_state([
        'calibration_start' => null,
        'episode_state' => null,
        'silenced' => 0,
        'last_periodic_at' => null,
        'last_hour_bucket' => null,
    ]);
}
