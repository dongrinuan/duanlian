<?php
/**
 * 短链接重定向处理程序
 * 
 * 接收短代码，查询原始URL并处理重定向
 * 根据设备类型和访问限制执行不同操作：
 * - 任意设备可访问的链接：直接重定向
 * - 仅移动设备可访问的链接：
 *   - 移动设备：重定向到原始URL
 *   - 桌面设备：显示错误页面并生成二维码
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @author GooShare <contact@gooshare.xyz>
 * @version 1.2.0
 */

// 包含配置和函数
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/disk_detector.php'; // 引入网盘检测模块

// 获取短代码参数
$shortCode = isset($_GET['code']) ? sanitizeInput($_GET['code']) : '';

// 验证短代码是否存在
if (empty($shortCode)) {
    // 没有提供短代码，重定向到首页
    header('Location: index.php');
    exit;
}

// 查询数据库获取原始URL和访问限制
$stmt = $conn->prepare("SELECT id, original_url, restriction FROM urls WHERE short_code = ? AND (is_deleted IS NULL OR is_deleted = 0)");
$stmt->bind_param("s", $shortCode);
$stmt->execute();
$result = $stmt->get_result();

// 检查短代码是否有效
if ($result->num_rows === 0) {
    // 短代码不存在，重定向到首页并显示错误
    header('Location: index.php?error=invalid_link');
    exit;
}

// 获取原始URL和访问限制
$row = $result->fetch_assoc();
$originalUrl = $row['original_url'];
$restriction = isset($row['restriction']) ? $row['restriction'] : 'mobile_only'; // 默认为仅移动设备
$stmt->close();

// 检查是否为网盘链接
$diskInfo = detectDiskLink($originalUrl);

// 记录访问
logUrlAccess($conn, $shortCode);

// 检查访问限制类型
if ($restriction === 'any_device') {
    // 任意设备可访问 - 直接重定向
    header('Location: ' . $originalUrl);
    exit;
} else {
    // 仅移动设备可访问 - 检查设备类型
    if (isMobileDevice()) {
        // 移动设备访问 - 直接重定向
        header('Location: ' . $originalUrl);
        exit;
    } else {
        // 非移动设备访问 - 显示错误页面和二维码
        $isDiskLink = $diskInfo['is_disk_link'];
        $diskName = $diskInfo['disk_name'];
        
        // 输出错误页面HTML
        ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isDiskLink ? $diskName.'链接 - 仅限移动设备访问' : '仅限移动设备访问'; ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f7f9fc;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        
        .error-container {
            max-width: 600px;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 { color: #e74c3c; margin-bottom: 20px; }
        .icon { font-size: 72px; margin-bottom: 20px; color: #e74c3c; }
        p { line-height: 1.6; margin-bottom: 20px; font-size: 18px; }
        .qr-container { margin-top: 30px; }
        .qr-container p { margin-bottom: 15px; font-weight: bold; }
        
        .home-link {
            display: inline-block;
            margin-top: 30px;
            color: #3498db;
            text-decoration: none;
        }
        
        .home-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">⚠️</div>
        <h1>此链接仅限移动设备访问</h1>
        <?php if ($isDiskLink): ?>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #3498db;">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <img src="/assets/images/disk_icons/<?php echo htmlspecialchars($diskInfo['disk_type']); ?>.svg" alt="<?php echo htmlspecialchars($diskName); ?>图标" width="24" height="24" style="margin-right: 10px;">
                <p style="font-weight: bold; color: #3498db; margin: 0;">检测到<?php echo htmlspecialchars($diskName); ?>链接</p>
            </div>
            <p>您正在访问的是一个<?php echo htmlspecialchars($diskName); ?>分享链接，需要在移动设备上打开。</p>
        </div>
        <?php endif; ?>
        <p>抱歉，您正在尝试从桌面设备访问仅适用于移动设备的链接。</p>
        <p>请使用智能手机或平板电脑扫描下方二维码，或者在移动设备上访问当前网址。</p>
        
        <div class="qr-container">
            <p>使用移动设备扫描:</p>
            <?php
            // 获取完整的URL
            $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            // 生成二维码图片URL，使用新的 api.qrtool.cn
            // 原始代码：$qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($currentUrl);
            $qrImageUrl = "https://api.qrtool.cn/?text=" . urlencode($currentUrl) . "&size=150&level=H";
            ?>
            <img src="<?php echo $qrImageUrl; ?>" alt="QR码" width="150" height="150">
        </div>
        
        <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/index.php"; ?>" class="home-link">返回首页</a>
    </div>
</body>
</html>
        <?php
    }
}

// 关闭数据库连接
$conn->close();
?>