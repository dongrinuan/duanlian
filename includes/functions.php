<?php
/**
 * 通用功能函数库 (优化版)
 * 
 * 包含系统中使用的所有通用功能函数
 * 经过重构以提高性能和可维护性
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @author GooShare <contact@gooshare.xyz>
 * @version 2.0.0
 */

/**
 * 生成唯一短代码
 * 使用更高效和安全的方法生成代码
 * 
 * @param int $length 短代码长度
 * @return string 生成的短代码
 */
function generateShortCode($length = 6) {
    // 更适合URL的字符集 (排除了易混淆的字符如0,O,1,I,l等)
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $charsLength = strlen($chars) - 1;
    
    // 使用更安全的随机数生成方法 - PHP 7+ 推荐使用random_bytes
    if (function_exists('random_bytes')) {
        // 高安全性方法
        $bytes = random_bytes($length);
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[ord($bytes[$i]) % $charsLength];
        }
        
        return $code;
    }
    
    // 回退方法 - 如果random_bytes不可用
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[mt_rand(0, $charsLength)];
    }
    
    return $code;
}

/**
 * 检查URL是否有效
 * 执行多重验证确保URL格式正确
 * 
 * @param string $url 要检查的URL
 * @return bool 如果URL有效则返回true
 */
function isValidUrl($url) {
    // 基本验证
    if (empty($url)) {
        return false;
    }
    
    // 尝试解析URL结构
    $parsed = parse_url($url);
    
    // 检查URL是否完整且具有必要的部分
    if (empty($parsed['scheme']) || empty($parsed['host'])) {
        return false;
    }
    
    // 检查协议是否为http或https (安全限制)
    if (!in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
        return false;
    }
    
    // 使用PHP内置函数进行最终验证
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * 检查是否为移动设备 - 增强版
 * 使用更全面的检测方法识别各种移动设备
 * 
 * @return bool 如果是移动设备则返回true
 */
function isMobileDevice() {
    // 如果User-Agent不存在，默认返回false
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    // 主要移动平台关键词检测 - 涵盖大多数常见移动设备
    $mobileKeywords = [
        // 手机平台
        'Mobile', 'Android', 'iPhone', 'iPod', 'webOS',
        // 平板设备 (也视为移动设备)
        'iPad', 'tablet', 'PlayBook', 'Silk', 
        // 移动浏览器
        'Opera Mini', 'Opera Mobi', 'IEMobile', 'fennec', 'Kindle',
        // 其他移动设备和操作系统
        'BlackBerry', 'Nokia', 'Windows Phone', 'SymbianOS', 
        'SamsungBrowser', 'CriOS', 'FxiOS'
    ];
    
    // 检查User-Agent中是否包含移动设备关键词
    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return true;
        }
    }
    
    // 高级检测技术 - 检查HTTP_ACCEPT头
    // 某些WAP浏览器会发送特定的Accept头
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/vnd.wap.xhtml+xml') !== false) {
            return true;
        }
    }
    
    // 检查是否存在特定移动版头信息
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
        return true;
    }
    
    // 未检测到移动设备特征
    return false;
}

/**
 * 记录URL访问统计
 * 更新访问计数和最后访问时间
 * 
 * @param mysqli $conn 数据库连接
 * @param string $shortCode 短代码
 * @return void
 */
function logUrlAccess($conn, $shortCode) {
    // 更新访问计数和最后访问时间
    $stmt = $conn->prepare("UPDATE urls SET access_count = access_count + 1, last_accessed = NOW() WHERE short_code = ?");
    $stmt->bind_param("s", $shortCode);
    $stmt->execute();
    $stmt->close();
}

/**
 * 清理输入数据
 * 
 * @param string $data 要清理的数据
 * @return string 清理后的数据
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * 格式化日期时间为可读格式
 * 
 * @param string $datetime MySQL日期时间字符串
 * @return string 格式化后的日期时间
 */
function formatDate($datetime) {
    $date = new DateTime($datetime);
    return $date->format('Y-m-d H:i:s');
}

/**
 * 截断URL以适应显示
 * 
 * @param string $url 原始URL
 * @param int $length 最大长度
 * @return string 截断后的URL
 */
function truncateUrl($url, $length = 40) {
    if (strlen($url) <= $length) {
        return $url;
    }
    
    return substr($url, 0, $length - 3) . '...';
}

/**
 * 发送JSON响应 - 集中统一的响应函数
 * 
 * @param array $data 响应数据
 * @param int $statusCode HTTP状态码
 */
if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>