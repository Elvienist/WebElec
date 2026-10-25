<?php

require_once 'config.php';

echo 'Configuration loaded successfully.<br>';
echo 'PHP timezone: ' . date_default_timezone_get() . '<br>';
echo 'Current time: ' . date('Y-m-d H:i:s');
