<?php
/**
 * 系统配置文件
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @author GooShare <contact@gooshare.xyz>
 * @version 1.0.4
 */

// 启用所有错误报告
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 设置错误日志路径 - 修复日志创建问题
$log_dir = dirname(__DIR__) . '/logs';
try {
    if (!file_exists($log_dir)) {
        if (!mkdir($log_dir, 0755, true)) {
            // 如果无法创建目录，fallback到系统临时目录
            $log_dir = sys_get_temp_dir();
        }
    }
    ini_set('error_log', $log_dir . '/php_errors.log');
} catch (Exception $e) {
    // 出错时fallback到默认错误日志
}

// 数据库配置
$db_host = "localhost"; 
$db_user = "112";       
$db_pass = "12345678";  
$db_name = "ce";        

// 创建数据库连接
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// 检查连接
if ($conn->connect_error) {
    error_log("数据库连接失败: " . $conn->connect_error);
    die("数据库连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");

// 统一设置调试模式
define('DEBUG_MODE', false); // 生产环境为false，开发环境可设为true

// 管理员密码设置 - 统一使用一个定义
define('ADMIN_PASSWORD', 'admin123'); // 请修改为您自己的安全密码

// 可选：配置其他全局常量
define('SITE_NAME', '移动端专属链接生成器');
define('SITE_URL', 'https://example.com'); // 修改为您的网站URL
?>
