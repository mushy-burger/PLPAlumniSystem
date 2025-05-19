<?php

define('IS_CRON', true);
define('DEBUG_MODE', true); 

date_default_timezone_set('Asia/Manila');

include 'admin/db_connect.php';
include 'archive_functions.php';

$current_date = date('Y-m-d H:i:s');

$log_message = "=================================================\n";
$log_message .= "ARCHIVE EVENT CHECK - STARTED\n";
$log_message .= "=================================================\n";
$log_message .= "Archive check run at: " . $current_date . "\n";
$log_message .= "System timezone: " . date_default_timezone_get() . "\n";
$log_message .= "PHP Version: " . phpversion() . "\n";
$log_message .= "Server timestamp: " . time() . "\n";
$log_message .= "Server microtime: " . microtime(true) . "\n";
$log_message .= "-------------------------------------------------\n";

$archive_result = archive_expired_events($conn);

if (DEBUG_MODE && isset($archive_result['debug_info'])) {
    $log_message .= "DEBUG INFORMATION:\n";
    $log_message .= "PHP DateTime settings:\n";
    $log_message .= "  - PHP Version: " . $archive_result['debug_info']['php_version'] . "\n";
    $log_message .= "  - Configured Timezone: " . $archive_result['debug_info']['timezone'] . "\n";
    $log_message .= "  - Server Time: " . $archive_result['debug_info']['server_time'] . "\n";
    $log_message .= "  - Server Microtime: " . $archive_result['debug_info']['server_microtime'] . "\n";
    $log_message .= "  - DateTime Timezone: " . $archive_result['debug_info']['datetime_object']['timezone'] . "\n";
    
    $log_message .= "SQL Query: " . $archive_result['debug_info']['sql_query'] . "\n";
    $log_message .= "Total Events Found: " . $archive_result['debug_info']['total_events_found'] . "\n";
    $log_message .= "-------------------------------------------------\n";
}

$events_checked = count($archive_result['details']);
$events_archived = $archive_result['archived_count'];
$events_pending = $events_checked - $events_archived;

$log_message .= "SUMMARY RESULTS:\n";
$log_message .= "Found $events_checked events with archive dates set\n";
$log_message .= "$events_archived events were archived\n";
$log_message .= "$events_pending events are pending future archive dates\n";

if (!empty($archive_result['errors'])) {
    $log_message .= "\nERRORS ENCOUNTERED:\n";
    foreach ($archive_result['errors'] as $error) {
        $log_message .= "  - " . $error . "\n";
    }
}

if (!empty($archive_result['details'])) {
    $log_message .= "\nDETAILED EVENT INFORMATION:\n";
    foreach ($archive_result['details'] as $detail) {
        $log_message .= "-------------------------------------------------\n";
        $log_message .= "EVENT ID " . $detail['id'] . ": " . $detail['title'] . "\n";
        $log_message .= "  Archive date: " . $detail['archive_date'] . " (timestamp: " . $detail['archive_timestamp'] . ")\n";
        $log_message .= "  Current time: " . $detail['current_time'] . " (timestamp: " . $detail['current_timestamp'] . ")\n";
        $log_message .= "  Time comparison:\n";
        $log_message .= "    - Time difference: " . $detail['time_difference_seconds'] . " seconds\n";
        
        if (isset($detail['seconds_remaining'])) {
            $log_message .= "    - Seconds remaining: " . $detail['seconds_remaining'] . "\n";
        }
        
        if (isset($detail['comparison'])) {
            $log_message .= "    - Current time is AFTER archive time: " . ($detail['comparison']['is_after'] ? "YES" : "NO") . "\n";
            $log_message .= "    - Current time is SAME as archive time: " . ($detail['comparison']['is_same'] ? "YES" : "NO") . "\n";
            $log_message .= "    - Current time is BEFORE archive time: " . ($detail['comparison']['is_before'] ? "YES" : "NO") . "\n";
        }
        
        $log_message .= "  Action taken: " . ($detail['will_archive'] ? "ARCHIVED" : "NOT ARCHIVED") . "\n";
        
        if ($detail['status'] === 'not_yet_expired') {
            $log_message .= "  Status: Not yet expired - Time remaining: " . $detail['time_remaining'] . "\n";
        } else {
            $log_message .= "  Status: " . $detail['status'] . "\n";
            if (isset($detail['error'])) {
                $log_message .= "  Error: " . $detail['error'] . "\n";
            }
        }
    }
}

$log_message .= "=================================================\n";
$log_message .= "ARCHIVE EVENT CHECK - COMPLETED\n";
$log_message .= "=================================================\n\n";

if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

$log_file = 'logs/archive_events_' . date('Y-m-d') . '.log';
file_put_contents($log_file, $log_message, FILE_APPEND);

echo "Archive process completed. See logs at $log_file for details."; 