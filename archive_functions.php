<?php

function archive_expired_events($conn) {
    $result = [
        'archived_count' => 0,
        'errors' => [],
        'details' => [],
        'debug' => []
    ];
    
    date_default_timezone_set('Asia/Manila');
    
    $current_timestamp = time();
    $current_time_mysql = date('Y-m-d H:i:s', $current_timestamp);
    
    $result['debug']['php_timezone'] = date_default_timezone_get();
    $result['debug']['timezone_offset'] = date('P');
    $result['debug']['timezone_offset_seconds'] = date('Z');
    $result['debug']['server_timezone'] = ini_get('date.timezone');
    $result['debug']['php_version'] = PHP_VERSION;
    
    $result['debug']['current_timestamp'] = $current_timestamp;
    $result['debug']['current_time_mysql'] = $current_time_mysql;
    $result['debug']['current_time_readable'] = date('F j, Y h:i:s A', $current_timestamp);
    $result['debug']['request_time'] = $_SERVER['REQUEST_TIME'] ?? 'Not available';
    $result['debug']['request_time_diff'] = $_SERVER['REQUEST_TIME'] ? ($current_timestamp - $_SERVER['REQUEST_TIME']) : 'N/A';
    
    $mysql_time_result = $conn->query("SELECT NOW() as mysql_time");
    if ($mysql_time_result) {
        $mysql_time = $mysql_time_result->fetch_assoc()['mysql_time'];
        $result['debug']['mysql_server_time'] = $mysql_time;
        $result['debug']['mysql_timestamp'] = strtotime($mysql_time);
        
        $time_diff = strtotime($mysql_time) - $current_timestamp;
        $result['debug']['time_difference_mysql_php'] = $time_diff;
        
        if (abs($time_diff) > 60) { 
            $result['errors'][] = "WARNING: MySQL and PHP times differ by " . abs($time_diff) . " seconds!";
        }
    }
    
    $sql = "SELECT * FROM events WHERE archive_date IS NOT NULL";
    $events = $conn->query($sql);
    
    if (!$events) {
        $result['errors'][] = "Error querying events: " . $conn->error;
        return $result;
    }
    
    if ($events->num_rows === 0) {
        return $result;
    }
    
    while ($event = $events->fetch_assoc()) {
        $event_id = $event['id'];
        $title = $event['title'];
        $archive_date_raw = $event['archive_date'];
        
        $event_detail = [
            'id' => $event_id,
            'title' => $title,
            'archive_date_raw' => $archive_date_raw,
            'current_time' => $current_time_mysql,
            'archive_time_readable' => 'Unknown'
        ];
        
        if (empty($archive_date_raw)) {
            $result['errors'][] = "Event ID $event_id has empty archive date";
            continue;
        }
        
        $archive_timestamp = strtotime($archive_date_raw);
        
        if ($archive_timestamp === false) {
            $result['errors'][] = "Invalid archive date format for event ID $event_id: '$archive_date_raw'";
            continue;
        }
        
        $archive_time_readable = date('F j, Y h:i:s A', $archive_timestamp);
        $event_detail['archive_time_readable'] = $archive_time_readable;
        
        $event_detail['archive_date_original'] = $archive_date_raw;
        $event_detail['archive_timestamp'] = $archive_timestamp;
        
        $timezone = new DateTimeZone('Asia/Manila');
        
        $current_date = new DateTime('now', $timezone);
        $archive_date = new DateTime($archive_date_raw, $timezone);

        if ($archive_date->format('s') === '00' && 
            (strpos($archive_date_raw, ':59:') === false)) {
            if (strpos($archive_date_raw, ':59') !== false) {
                $archive_date->setTime(
                    (int)$archive_date->format('H'),
                    (int)$archive_date->format('i'),
                    59
                );
            }
        }
        
        $event_detail['current_datetime_obj'] = $current_date->format('Y-m-d H:i:s');
        $event_detail['archive_datetime_obj'] = $archive_date->format('Y-m-d H:i:s');
        $event_detail['current_datetime_with_tz'] = $current_date->format('Y-m-d H:i:s P');
        $event_detail['archive_datetime_with_tz'] = $archive_date->format('Y-m-d H:i:s P');
        
        $time_diff = $current_date->getTimestamp() - $archive_date->getTimestamp();
        $event_detail['time_diff_seconds'] = $time_diff;
        
        $should_archive = $current_date > $archive_date;
        
        $event_detail['debug_time'] = [
            'current_time' => $current_date->format('Y-m-d H:i:s P'),
            'archive_time' => $archive_date->format('Y-m-d H:i:s P'),
            'time_diff_seconds' => $time_diff,
            'should_archive' => $should_archive ? 'Yes' : 'No',
            'reason' => $should_archive ? 
                       "Current time is after archive time" : 
                       "Current time is not yet after archive time"
        ];
        
        $event_detail['current_timestamp'] = $current_date->getTimestamp();
        $event_detail['archive_timestamp'] = $archive_date->getTimestamp();
        $event_detail['comparison'] = [
            'is_after' => $current_date > $archive_date,
            'is_same' => $current_date == $archive_date,
            'is_before' => $current_date < $archive_date
        ];
        
        if ($should_archive) {
            $time_diff_minutes = floor($time_diff / 60);
            $time_diff_hours = floor($time_diff_minutes / 60);
            $time_diff_minutes = $time_diff_minutes % 60;
            
            $event_detail['archive_reason'] = "Archive time passed ({$time_diff_hours}h {$time_diff_minutes}m ago)";
        } else {
            $interval = $current_date->diff($archive_date);
            $hours = $interval->h + ($interval->days * 24);
            $minutes = $interval->i;
            $seconds = $interval->s;
            
            $event_detail['archive_reason'] = "Archive time not yet reached (time remaining: {$hours}h {$minutes}m {$seconds}s)";
            $event_detail['status'] = 'not_yet_archived';
            $event_detail['will_archive'] = false;
        }
        
        $event_detail['current_date_parts'] = [
            'year' => (int)$current_date->format('Y'), 
            'month' => (int)$current_date->format('m'), 
            'day' => (int)$current_date->format('d'),
            'hour' => (int)$current_date->format('H'), 
            'minute' => (int)$current_date->format('i'), 
            'second' => (int)$current_date->format('s')
        ];
        $event_detail['archive_date_parts'] = [
            'year' => (int)$archive_date->format('Y'), 
            'month' => (int)$archive_date->format('m'), 
            'day' => (int)$archive_date->format('d'),
            'hour' => (int)$archive_date->format('H'), 
            'minute' => (int)$archive_date->format('i'), 
            'second' => (int)$archive_date->format('s')
        ];
        
        if ($should_archive) {
            try {
                $conn->begin_transaction();
                
                $title_safe = $conn->real_escape_string($event['title']);
                $content_safe = $conn->real_escape_string($event['content']);
                $schedule = $event['schedule'];
                $banner = $event['banner'];
                $gform_link_safe = $conn->real_escape_string($event['gform_link'] ?? '');
                
                $sql = "INSERT INTO archived_events (title, content, schedule, banner, gform_link, original_id) 
                       VALUES ('$title_safe', '$content_safe', '$schedule', '$banner', '$gform_link_safe', $event_id)";
                
                if (!$conn->query($sql)) {
                    throw new Exception("Error inserting to archived_events: " . $conn->error);
                }
                
                $sql = "DELETE FROM events WHERE id = $event_id";
                if (!$conn->query($sql)) {
                    throw new Exception("Error deleting from events: " . $conn->error);
                }
                
                $conn->commit();
                
                $result['archived_count']++;
                $event_detail['status'] = 'archived_successfully';
                $event_detail['will_archive'] = true;
                
            } catch (Exception $e) {
                $conn->rollback();
                
                $event_detail['status'] = 'archive_failed';
                $event_detail['error'] = $e->getMessage();
                $result['errors'][] = "Failed to archive event ID $event_id: " . $e->getMessage();
            }
        }
        
        $result['details'][] = $event_detail;
    }
    
    return $result;
}

function validateDate($date, $format = 'Y-m-d H:i:s') {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return true;
    }
    
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
} 