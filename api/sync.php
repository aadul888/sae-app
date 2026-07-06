<?php
/**
 * SAE API Router for Data Sync
 * Routes requests to appropriate sync endpoints
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 1 for debugging

// Include the sync endpoint handler
require_once __DIR__ . '/endpoints/sync_data.php';
?>