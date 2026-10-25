<?php

date_default_timezone_set('Asia/Manila');

define('BRIDGE_KEY', 'change-me-bridge-key');
define('BASELINE_MINUTES', 30);
define('SPIKE_RISE', 2.0);
define('HYSTERESIS', 0.5);
define('FIRE_TEMP', 55.0);
define('POSSIBLE_FIRE_TEMP', 45.0);
define('CALIBRATE_MINUTES', 5);
define('CUSTOM_HYSTERESIS', 0.5);

define('SIM_NORMAL', 25.0);
define('SIM_ELEVATED', 42.0);
define('SIM_FIRE', 58.0);
define('SIM_SPIKE_ADD', 3.0);
