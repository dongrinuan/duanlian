<?php
/**
 * 管理员API处理程序
 * 
 * 提供管理员专用功能，如无需密码删除链接
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @version 1.0.1
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 设置响应类型为JSON
header('Content-Type: application/json');

// 验证管理员身份
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    sendJsonResponse([
        'success' => false,
        'message' => '未授权访问'
    ], 403);
}

try {
    // 处理删除请求
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!isset($data['id']) && (!isset($data['batch']) || !isset($data['ids']))) {
            sendJsonResponse([
                'success' => false,
                'message' => '缺少必要参数'
            ]);
        }
        
        // 处理批量删除
        if (isset($data['batch']) && isset($data['ids']) && is_array($data['ids'])) {
            $ids = array_map('intval', $data['ids']);
            
            if (empty($ids)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => '未提供有效的ID'
                ]);
            }
            
            // 检查表是否有is_deleted列
            $result = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
            $hasDeletedColumn = $result && $result->num_rows > 0;
            
            if ($hasDeletedColumn) {
                // 使用软删除 - 移动到回收站
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $conn->prepare("UPDATE urls SET is_deleted = 1 WHERE id IN ($placeholders)");
            } else {
                // 使用硬删除
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $conn->prepare("DELETE FROM urls WHERE id IN ($placeholders)");
            }
            
            if (!$stmt) {
                sendJsonResponse([
                    'success' => false,
                    'message' => '准备批量删除语句失败: ' . $conn->error
                ]);
            }
            
            // 动态生成参数类型字符串
            $types = str_repeat('i', count($ids));
            $stmt->bind_param($types, ...$ids);
            
            $success = $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            sendJsonResponse([
                'success' => $success,
                'affectedRows' => $affectedRows,
                'message' => $success ? "成功删除 {$affectedRows} 个链接" : '删除链接失败: ' . $conn->error
            ]);
        } else {
            // 单个删除处理
            $id = (int)$data['id'];
            
            // 检查表是否有is_deleted列
            $result = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
            $hasDeletedColumn = $result && $result->num_rows > 0;
            
            if ($hasDeletedColumn) {
                // 使用软删除 - 移动到回收站
                $stmt = $conn->prepare("UPDATE urls SET is_deleted = 1 WHERE id = ?");
            } else {
                // 使用硬删除
                $stmt = $conn->prepare("DELETE FROM urls WHERE id = ?");
            }
            
            if (!$stmt) {
                sendJsonResponse([
                    'success' => false, 
                    'message' => '准备删除语句失败: ' . $conn->error
                ]);
            }
            
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            
            sendJsonResponse([
                'success' => $success,
                'message' => $success ? '链接已成功删除' : '删除链接失败: ' . $conn->error
            ]);
        }
    } else {
        // 非DELETE请求返回错误
        sendJsonResponse([
            'success' => false,
            'message' => '无效的请求方法'
        ], 405);
    }
} catch (Exception $e) {
    error_log("API错误: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => '服务器错误: ' . $e->getMessage()
    ], 500);
}

// 关闭数据库连接
$conn->close();
?>