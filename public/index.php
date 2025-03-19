<?php
/**
 * REST API Entry Point
 */

// Define app base path
define('APP_BASE_PATH', realpath(__DIR__ . '/..'));

// Autoload dependencies
require APP_BASE_PATH . '/vendor/autoload.php';

use Ody\Foundation\Bootstrap;

// Initialize application
$app = Bootstrap::init();

// Run the application
$app->run();