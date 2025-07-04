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
            $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($currentUrl);
            ?>
            <img src="<?php echo $qrImageUrl; ?>" alt="QR码" width="150" height="150">
        </div>
        
        <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/index.php"; ?>" class="home-link">返回首页</a>
    </div>
</body>
</html>