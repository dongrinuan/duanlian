<?php
/**
 * 性能监控工具
 * 
 * 用于监控和优化系统性能
 */

class Performance {
    private static $startTime;
    private static $benchmarks = [];
    private static $memoryUsage = [];

    /**
     * 开始计时
     */
    public static function start() {
        self::$startTime = microtime(true);
        self::addMemoryUsage('start');
    }

    /**
     * 记录检查点
     * 
     * @param string $checkpoint 检查点名称
     */
    public static function checkpoint($checkpoint) {
        $time = microtime(true);
        self::$benchmarks[$checkpoint] = $time - self::$startTime;
        self::addMemoryUsage($checkpoint);
    }

    /**
     * 记录内存使用情况
     */
    private static function addMemoryUsage($checkpoint) {
        self::$memoryUsage[$checkpoint] = memory_get_usage(true);
    }

    /**
     * 获取性能报告
     * 
     * @return array 性能数据
     */
    public static function getReport() {
        // 添加最终检查点
        self::checkpoint('end');
        
        $report = [
            'total_time' => self::$benchmarks['end'],
            'checkpoints' => self::$benchmarks,
            'memory' => [
                'peak' => memory_get_peak_usage(true),
                'checkpoints' => self::$memoryUsage
            ]
        ];
        
        return $report;
    }
    
    /**
     * 打印性能报告（仅在调试模式下）
     */
    public static function printReport() {
        if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
            return;
        }
        
        $report = self::getReport();
        
        echo "\n<!-- Performance Report -->\n";
        echo "<!-- Total Time: " . round($report['total_time'] * 1000, 2) . " ms -->\n";
        echo "<!-- Peak Memory: " . self::formatBytes($report['memory']['peak']) . " -->\n";
        echo "<!-- Checkpoints:\n";
        
        foreach ($report['checkpoints'] as $checkpoint => $time) {
            $memory = isset($report['memory']['checkpoints'][$checkpoint]) 
                    ? self::formatBytes($report['memory']['checkpoints'][$checkpoint]) 
                    : 'N/A';
            
            echo "     $checkpoint: " . round($time * 1000, 2) . " ms / $memory\n";
        }
        
        echo "-->\n";
    }
    
    /**
     * 格式化字节为可读格式
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
