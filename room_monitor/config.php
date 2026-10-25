
<?php

declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'room_monitor');
define('DB_USER', 'root');
define('DB_PASS', '');

define('TIMEZONE', 'Asia/Manila');
define('BRIDGE_KEY', 'change-me-bridge-key');

define('BASELINE_MINUTES', 30);
define('CALIBRATE_MINUTES', 5);

define('WARM_RISE', 2.0);
define('SPIKE_RISE', 5.0);
define('HYSTERESIS', 2.0);
define('CUSTOM_HYSTERESIS', 0.5);

define('POSSIBLE_FIRE_TEMP', 35.0);
define('FIRE_TEMP', 40.0);

define('NO_SIGNAL_SECONDS', 10);
define('READINGS_KEEP_MINUTES', 120);
define('PAGE_SIZE', 50);

define('SIM_NORMAL', 29.0);
define('SIM_ELEVATED', 36.0);
define('SIM_FIRE', 42.0);
define('SIM_SPIKE_ADD', 6.0);

date_default_timezone_set(TIMEZONE);
