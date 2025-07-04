<?php
/**
 * API处理程序
 * 
 * 处理创建和删除短链接的请求
 * 提供REST风格的API接口，支持POST、DELETE和GET操作
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @version 1.1.0
 */

// 包含配置和函数库
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 使用config.php中定义的DEBUG_MODE，不重复定义

// 启用错误捕获
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // 初始化数据库表
    initializeDatabase($conn);
    
    // 设置更长的执行时间限制，避免处理大量数据时超时
    set_time_limit(120);
    
    // 基于HTTP方法路由到适当的处理函数
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            handlePostRequest($conn);
            break;
        case 'DELETE':
            handleDeleteRequest($conn);
            break;
        case 'GET':
            handleGetRequest($conn);
            break;
        default:
            throw new Exception('不支持的HTTP方法: ' . $method);
    }
} catch (Exception $e) {
    // 更详细的错误日志记录
    error_log(sprintf(
        "API错误 [%s]: %s in %s:%d\nStack trace:\n%s\nPOST数据: %s",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString(),
        json_encode($_POST)
    ));
    
    // 返回友好的错误信息
    sendJsonResponse([
        'success' => false,
        'error' => 'server_error',
        'message' => '服务器处理请求时出错',
        'details' => DEBUG_MODE ? $e->getMessage() : null
    ], 500);
}

// 恢复错误处理
restore_error_handler();

// 关闭数据库连接
$conn->close();

/**
 * 检查并初始化数据库
 * 
 * @param mysqli $conn 数据库连接
 * @throws Exception 如果数据库操作失败
 */
function initializeDatabase($conn) {
    // 检查urls表是否存在，如果不存在则创建
    $tableExists = $conn->query("SHOW TABLES LIKE 'urls'")->num_rows > 0;
    
    if (!$tableExists) {
        // 创建表，添加restriction字段和is_deleted字段
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS urls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_url VARCHAR(2048) NOT NULL,
            short_code VARCHAR(10) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            restriction ENUM('mobile_only', 'any_device') DEFAULT 'mobile_only',
            is_deleted TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            access_count INT DEFAULT 0,
            last_accessed TIMESTAMP NULL
        ) ENGINE=InnoDB;
        ";
        
        if (!$conn->query($createTableSQL)) {
            throw new Exception("无法创建urls表: " . $conn->error);
        }
        
        // 创建索引
        $createIndexSQL = "CREATE INDEX idx_short_code ON urls(short_code);";
        $conn->query($createIndexSQL);
        
        // 创建is_deleted索引，优化查找删除的记录
        $createDeletedIndexSQL = "CREATE INDEX idx_is_deleted ON urls(is_deleted);";
        $conn->query($createDeletedIndexSQL);
    } else {
        // 检查表中是否有password列，如果没有则添加
        $checkPasswordColumn = $conn->query("SHOW COLUMNS FROM urls LIKE 'password'");
        if ($checkPasswordColumn->num_rows === 0) {
            // 添加password列
            if (!$conn->query("ALTER TABLE urls ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT ''")) {
                throw new Exception("无法添加密码列: " . $conn->error);
            }
            error_log("已自动添加password列到urls表");
        }
        
        // 检查表中是否有restriction列，如果没有则添加
        $checkRestrictionColumn = $conn->query("SHOW COLUMNS FROM urls LIKE 'restriction'");
        if ($checkRestrictionColumn->num_rows === 0) {
            if (!$conn->query("ALTER TABLE urls ADD COLUMN restriction ENUM('mobile_only', 'any_device') DEFAULT 'mobile_only'")) {
                throw new Exception("无法添加访问限制列: " . $conn->error);
            }
            error_log("已自动添加restriction列到urls表");
        }
        
        // 检查表中是否有is_deleted列，如果没有则添加
        $checkDeletedColumn = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
        if ($checkDeletedColumn->num_rows === 0) {
            if (!$conn->query("ALTER TABLE urls ADD COLUMN is_deleted TINYINT(1) DEFAULT 0")) {
                throw new Exception("无法添加软删除标记列: " . $conn->error);
            }
            // 添加索引优化查询性能
            $conn->query("CREATE INDEX idx_is_deleted ON urls(is_deleted);");
            error_log("已自动添加is_deleted列到urls表");
        }
    }
}

/**
 * 处理POST请求 - 创建短链接
 * 
 * @param mysqli $conn 数据库连接
 */
function handlePostRequest($conn) {
    $contentType = getRequestContentType();
    
    // 解析请求体
    if (isJsonContentType($contentType)) {
        handleJsonPost($conn);
    } else {
        handleFormPost($conn);
    }
}

/**
 * 处理JSON格式的POST请求
 * 
 * @param mysqli $conn 数据库连接
 */
function handleJsonPost($conn) {
    // 添加日志记录便于调试
    $json = file_get_contents('php://input');
    error_log("处理JSON请求: " . $json);
    $data = json_decode($json, true);
    
    if (isset($data['batch']) && $data['batch'] === true && isset($data['urls'])) {
        $urls = $data['urls'];
        
        // 添加批量转换数量限制
        if (count($urls) > 50) {
            sendJsonResponse([
                'success' => false,
                'message' => '批量转换每次最多支持50个链接'
            ]);
        }
        
        $password = isset($data['password']) ? sanitizeInput($data['password']) : '';
        $restriction = isset($data['restriction']) ? sanitizeInput($data['restriction']) : 'mobile_only';
        
        if (empty($password)) {
            sendJsonResponse([
                'success' => false,
                'message' => '请设置密码用于链接管理'
            ]);
        }
        
        $results = [];
        
        foreach ($urls as $url) {
            if (!isValidUrl($url)) {
                $results[] = [
                    'originalUrl' => $url,
                    'success' => false,
                    'message' => '错误: 无效的URL格式'
                ];
                continue;
            }
            
            try {
                $shortCodeResult = createShortCode($conn, $url, $password, $restriction);
                
                if ($shortCodeResult['success']) {
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $shortUrl = "$protocol://$host/go.php?code=" . $shortCodeResult['shortCode'];
                    
                    $results[] = [
                        'originalUrl' => $url,
                        'success' => true,
                        'shortUrl' => $shortUrl,
                        'shortCode' => $shortCodeResult['shortCode'],
                        'restriction' => $restriction
                    ];
                } else {
                    $results[] = [
                        'originalUrl' => $url,
                        'success' => false,
                        'message' => '错误: ' . $shortCodeResult['message']
                    ];
                }
            } catch (Exception $e) {
                $results[] = [
                    'originalUrl' => $url,
                    'success' => false,
                    'message' => '错误: ' . $e->getMessage()
                ];
            }
        }
        
        sendJsonResponse([
            'success' => true,
            'results' => $results
        ]);
    }
}

/**
 * 处理表单格式的POST请求
 * 
 * @param mysqli $conn 数据库连接
 */
function handleFormPost($conn) {
    // 添加详细日志记录
    error_log("原始POST数据内容: " . file_get_contents("php://input"));
    error_log("当前POST数组: " . print_r($_POST, true));
    error_log("Content-Type: " . getRequestContentType());
    
    // 尝试从原始输入流解析数据
    if (empty($_POST)) {
        parse_str(file_get_contents("php://input"), $postData);
        if (!empty($postData)) {
            error_log("从输入流解析的数据: " . print_r($postData, true));
            $_POST = $postData;
        }
    }
    
    // 获取URL和密码
    $url = isset($_POST['url']) ? sanitizeInput($_POST['url']) : '';
    $password = isset($_POST['password']) ? sanitizeInput($_POST['password']) : '';
    $restriction = isset($_POST['restriction']) ? sanitizeInput($_POST['restriction']) : 'mobile_only';
    
    error_log("处理URL: " . $url . ", 密码: " . $password . ", 密码长度: " . strlen($password));
    
    if (empty($url) || empty($password)) {
        error_log("参数缺失: URL=" . ($url ? '是' : '否') . ", 密码=" . ($password ? '是' : '否'));
        sendJsonResponse([
            'success' => false,
            'message' => '缺少必要参数，请确保填写了URL和密码'
        ]);
    }
    
    // 检查POST数据是否存在并记录
    error_log("POST数据接收: " . json_encode($_POST));
    
    // 验证数据
    if (!isValidUrl($url)) {
        error_log("无效URL: " . $url);
        sendJsonResponse([
            'success' => false,
            'message' => '请输入有效的URL（必须包含http://或https://）'
        ]);
    }
    
    if (empty($password)) {
        sendJsonResponse([
            'success' => false,
            'message' => '请设置密码用于链接管理'
        ]);
    }
    
    try {
        // 检查连接状态
        if ($conn->ping() === false) {
            // 尝试重新连接
            error_log("数据库连接已断开，尝试重新连接");
            $conn->close();
            require_once 'includes/config.php'; // 重新加载配置并连接
            global $conn;
        }
        
        $shortCodeResult = createShortCode($conn, $url, $password, $restriction);
        
        if ($shortCodeResult['success']) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $shortUrl = "$protocol://$host/go.php?code=" . $shortCodeResult['shortCode'];
            
            sendJsonResponse([
                'success' => true,
                'shortUrl' => $shortUrl,
                'shortCode' => $shortCodeResult['shortCode'],
                'restriction' => $restriction
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => $shortCodeResult['message']
            ]);
        }
    } catch (Exception $e) {
        error_log("创建短链接时出错: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        sendJsonResponse([
            'success' => false,
            'message' => "处理请求时出错，请稍后再试"
        ]);
    }
}

/**
 * 处理DELETE请求 - 删除短链接
 * 
 * @param mysqli $conn 数据库连接
 */
function handleDeleteRequest($conn) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['id']) || !isset($data['password'])) {
        sendJsonResponse([
            'success' => false,
            'message' => '缺少必要参数'
        ]);
    }
    
    $id = (int)$data['id'];
    $password = sanitizeInput($data['password']);
    
    $stmt = $conn->prepare("SELECT password FROM urls WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendJsonResponse([
            'success' => false,
            'message' => '找不到指定的链接'
        ]);
    }
    
    $row = $result->fetch_assoc();
    $savedPassword = $row['password'];
    $stmt->close();
    
    if ($password !== $savedPassword) {
        sendJsonResponse([
            'success' => false,
            'message' => '密码错误，无法删除'
        ]);
    }
    
    // 检查表是否有is_deleted列
    $result = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
    $hasDeletedColumn = $result && $result->num_rows > 0;
    
    if ($hasDeletedColumn) {
        // 使用软删除
        $stmt = $conn->prepare("UPDATE urls SET is_deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
    } else {
        // 使用硬删除
        $stmt = $conn->prepare("DELETE FROM urls WHERE id = ?");
        $stmt->bind_param("i", $id);
    }
    
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        sendJsonResponse([
            'success' => true,
            'message' => '链接已成功删除'
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => '删除链接失败: ' . $conn->error
        ]);
    }
}

/**
 * 处理GET请求 - 获取短链接信息
 * 
 * @param mysqli $conn 数据库连接
 */
function handleGetRequest($conn) {
    $code = isset($_GET['code']) ? sanitizeInput($_GET['code']) : '';
    
    if (empty($code)) {
        sendJsonResponse([
            'success' => false,
            'message' => '未提供短代码参数'
        ]);
    }
    
    $stmt = $conn->prepare("SELECT original_url, access_count, created_at FROM urls WHERE short_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendJsonResponse([
            'success' => false,
            'message' => '找不到指定的短链接'
        ]);
    }
    
    $data = $result->fetch_assoc();
    $stmt->close();
    
    sendJsonResponse([
        'success' => true,
        'url' => $data['original_url'],
        'access_count' => $data['access_count'],
        'created_at' => $data['created_at']
    ]);
}

/**
 * 获取请求内容类型
 * 
 * @return string 请求内容类型
 */
function getRequestContentType() {
    return isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
}

/**
 * 检查是否为JSON内容类型
 * 
 * @param string $contentType 请求内容类型
 * @return bool 是否为JSON内容类型
 */
function isJsonContentType($contentType) {
    return stripos($contentType, 'application/json') !== false;
}

/**
 * 创建短代码并保存到数据库
 * 
 * @param mysqli $conn 数据库连接
 * @param string $url 原始URL
 * @param string $password 用于删除的密码
 * @param string $restriction 访问限制类型
 * @return array 包含成功状态和短代码或错误信息的数组
 */
function createShortCode($conn, $url, $password, $restriction = 'mobile_only') {
    $isUnique = false;
    $shortCode = '';
    $maxAttempts = 5;
    $attempts = 0;
    
    // 首先检查是否有被软删除的链接ID可以复用
    $deletedCode = checkForDeletedShortCodes($conn);
    if ($deletedCode) {
        // 如果有被删除的短代码，直接复用
        $shortCode = $deletedCode;
        $isUnique = true;
    } else {
        // 否则生成新的短代码
        while (!$isUnique && $attempts < $maxAttempts) {
            $shortCode = generateShortCode();
            
            $stmt = $conn->prepare("SELECT id FROM urls WHERE short_code = ?");
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => "数据库查询准备失败: " . $conn->error
                ];
            }
            $stmt->bind_param("s", $shortCode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $isUnique = true;
            }
            
            $stmt->close();
            $attempts++;
        }
    }
    
    if (!$isUnique) {
        return [
            'success' => false,
            'message' => '无法生成唯一链接，请重试'
        ];
    }
    
    try {
        // 检查表结构
        $checkRestrictionColumn = $conn->query("SHOW COLUMNS FROM urls LIKE 'restriction'");
        $hasRestrictionColumn = $checkRestrictionColumn->num_rows > 0;
        
        $checkDeletedColumn = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
        $hasDeletedColumn = $checkDeletedColumn->num_rows > 0;
        
        // 如果是复用的短代码，执行更新操作
        if ($deletedCode) {
            if ($hasRestrictionColumn && $hasDeletedColumn) {
                $stmt = $conn->prepare("UPDATE urls SET original_url = ?, password = ?, restriction = ?, is_deleted = 0, access_count = 0, created_at = NOW(), last_accessed = NULL WHERE short_code = ?");
                $stmt->bind_param("ssss", $url, $password, $restriction, $shortCode);
            } else if ($hasDeletedColumn) {
                $stmt = $conn->prepare("UPDATE urls SET original_url = ?, password = ?, is_deleted = 0, access_count = 0, created_at = NOW(), last_accessed = NULL WHERE short_code = ?");
                $stmt->bind_param("sss", $url, $password, $shortCode);
            } else {
                // 如果没有软删除列，创建新记录
                return insertNewShortCode($conn, $url, $shortCode, $password, $restriction, $hasRestrictionColumn);
            }
            
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => "数据库更新准备失败: " . $conn->error
                ];
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                return [
                    'success' => true,
                    'shortCode' => $shortCode,
                    'restriction' => $restriction
                ];
            } else {
                $error = $stmt->error;
                $stmt->close();
                return [
                    'success' => false,
                    'message' => "更新URL时出错: " . $error
                ];
            }
        } else {
            // 创建新记录
            return insertNewShortCode($conn, $url, $shortCode, $password, $restriction, $hasRestrictionColumn);
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "处理请求时出错: " . $e->getMessage()
        ];
    }
}

/**
 * 插入新的短链接记录
 * 
 * @param mysqli $conn 数据库连接
 * @param string $url 原始URL
 * @param string $shortCode 短代码
 * @param string $password 密码
 * @param string $restriction 访问限制
 * @param bool $hasRestrictionColumn 表是否有restriction列
 * @return array 操作结果
 */
function insertNewShortCode($conn, $url, $shortCode, $password, $restriction, $hasRestrictionColumn) {
    // 检查表是否有is_deleted列
    $checkDeletedColumn = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
    $hasDeletedColumn = $checkDeletedColumn->num_rows > 0;
    
    if ($hasRestrictionColumn && $hasDeletedColumn) {
        $stmt = $conn->prepare("INSERT INTO urls (original_url, short_code, password, restriction, is_deleted) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $url, $shortCode, $password, $restriction);
    } else if ($hasRestrictionColumn) {
        $stmt = $conn->prepare("INSERT INTO urls (original_url, short_code, password, restriction) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $url, $shortCode, $password, $restriction);
    } else if ($hasDeletedColumn) {
        $stmt = $conn->prepare("INSERT INTO urls (original_url, short_code, password, is_deleted) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $url, $shortCode, $password);
    } else {
        $stmt = $conn->prepare("INSERT INTO urls (original_url, short_code, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $url, $shortCode, $password);
    }
    
    if (!$stmt) {
        return [
            'success' => false,
            'message' => "数据库插入准备失败: " . $conn->error
        ];
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        return [
            'success' => true,
            'shortCode' => $shortCode,
            'restriction' => $restriction
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => false,
            'message' => "保存URL时出错: " . $error
        ];
    }
}

/**
 * 检查是否有被软删除的短代码可以复用
 * 
 * @param mysqli $conn 数据库连接
 * @return string|false 如果有可复用的短代码则返回，否则返回false
 */
function checkForDeletedShortCodes($conn) {
    // 检查表是否有is_deleted列
    $result = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
    if ($result && $result->num_rows > 0) {
        // 获取一个被软删除的短代码
        $stmt = $conn->prepare("SELECT short_code FROM urls WHERE is_deleted = 1 LIMIT 1");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stmt->close();
                return $row['short_code'];
            }
            $stmt->close();
        }
    }
    return false;
}
?>