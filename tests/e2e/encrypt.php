<?php

/**
 * Test helper: encrypt a value with the GLPI key, like GLPIKey::encrypt().
 * Usage: php encrypt.php /path/to/glpi value
 */

[, $glpiDir, $value] = $argv;
define('GLPI_CONFIG_DIR', getenv('GLPI_CONFIG_DIR') ?: $glpiDir . '/config');
require $glpiDir . '/vendor/autoload.php';

echo (new GLPIKey())->encrypt($value);
