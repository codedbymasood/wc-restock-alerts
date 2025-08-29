<?php
/**
 * Logger class.
 *
 * @package store-boost-kit\admin\
 * @author Store Boost Kit <hello@storeboostkit.com>
 * @version 1.0
 */

namespace STOBOKIT;

defined( 'ABSPATH' ) || exit;

class Logger {
    private $option_name = 'app_debug_logs';
    private $max_logs = 50;
    
    public function __construct($max_logs = 50) {
      $this->max_logs = $max_logs;
    }
    
    private function addLog($level, $message, $context = []) {
        // Get existing logs
        $logs = get_option($this->option_name, []);
        
        // Create new log entry
        $log_entry = [
            'id' => uniqid(),
            'timestamp' => current_time('mysql'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'file' => $this->getCallerInfo()['file'] ?? null,
            'line' => $this->getCallerInfo()['line'] ?? null,
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        
        // Add to beginning of array
        array_unshift($logs, $log_entry);
        
        // Keep only the last X logs
        $logs = array_slice($logs, 0, $this->max_logs);
        
        // Save back to options
        update_option($this->option_name, $logs);
    }
    
    private function getCallerInfo() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        // Skip this method and the log level method
        $caller = $backtrace[3] ?? $backtrace[2] ?? [];
        
        return [
            'file' => isset($caller['file']) ? basename($caller['file']) : null,
            'line' => $caller['line'] ?? null
        ];
    }
    
    public function info($message, $context = []) {
        $this->addLog('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->addLog('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->addLog('ERROR', $message, $context);
    }
    
    public function debug($message, $context = []) {
        $this->addLog('DEBUG', $message, $context);
    }
    
    public function getLogs() {
        return get_option($this->option_name, []);
    }
    
    public function clearLogs() {
        return delete_option($this->option_name);
    }
    
    public function exportAsText() {
        $logs = $this->getLogs();
        $text = "=== DEBUG LOGS EXPORT ===\n";
        $text .= "Generated: " . current_time('mysql') . "\n";
        $text .= "Total Records: " . count($logs) . "\n";
        $text .= str_repeat("=", 40) . "\n\n";
        
        foreach ($logs as $log) {
            $text .= "[{$log['timestamp']}] {$log['level']}: {$log['message']}\n";
            
            if (!empty($log['context'])) {
                $text .= "Context: " . json_encode($log['context']) . "\n";
            }
            
            if ($log['file']) {
                $text .= "File: {$log['file']}";
                if ($log['line']) {
                    $text .= " (Line: {$log['line']})";
                }
                $text .= "\n";
            }
            
            if ($log['user_id']) {
                $user = get_user_by('ID', $log['user_id']);
                $text .= "User: " . ($user ? $user->user_login : 'Unknown') . " (ID: {$log['user_id']})\n";
            }
            
            if ($log['ip']) {
                $text .= "IP: {$log['ip']}\n";
            }
            
            $text .= str_repeat("-", 25) . "\n\n";
        }
        
        return $text;
    }
}