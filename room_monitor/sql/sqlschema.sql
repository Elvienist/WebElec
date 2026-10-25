CREATE DATABASE IF NOT EXISTS room_monitor DEFAULT CHARACTER SET utf8mb4;
USE room_monitor;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    created_by INT UNSIGNED NULL,
    created_at DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_state (
    id TINYINT UNSIGNED PRIMARY KEY,
    running TINYINT(1) DEFAULT 0,
    silenced TINYINT(1) DEFAULT 0,
    sim_on TINYINT(1) DEFAULT 0,
    last_sensor_ok TINYINT(1) DEFAULT 1,
    mode VARCHAR(10) DEFAULT 'general',
    room_name VARCHAR(100) NULL,
    sim_temp DECIMAL(4,1) NULL,
    custom_low DECIMAL(4,1) DEFAULT 25.0,
    custom_high DECIMAL(4,1) DEFAULT 32.0,
    last_temp DECIMAL(4,1) NULL,
    custom_log_style VARCHAR(10) DEFAULT 'crossing',
    custom_interval_min TINYINT UNSIGNED DEFAULT 5,
    last_label VARCHAR(20) NULL,
    last_led VARCHAR(5) DEFAULT 'OFF',
    episode_state VARCHAR(20) NULL,
    last_reading_at DATETIME NULL,
    calibration_start DATETIME NULL,
    last_hour_bucket DATETIME NULL,
    last_periodic_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS readings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    temp DECIMAL(4,1),
    taken_at DATETIME,
    is_test TINYINT(1) DEFAULT 0,
    INDEX idx_is_test_taken_at (is_test, taken_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recorded_at DATETIME,
    room_name VARCHAR(100),
    mode VARCHAR(10),
    temp DECIMAL(4,1) NULL,
    min_temp DECIMAL(4,1) NULL,
    max_temp DECIMAL(4,1) NULL,
    event VARCHAR(20),
    is_test TINYINT(1) DEFAULT 0,
    INDEX idx_recorded_at (recorded_at),
    INDEX idx_room_name (room_name),
    INDEX idx_mode (mode),
    INDEX idx_event (event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;