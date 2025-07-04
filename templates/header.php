<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : '移动端专属链接生成器'; ?></title>
    <!-- 添加Google Fonts - Inter字体 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- 添加jQuery和jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- 添加网站图标 -->
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <!-- 添加主JavaScript文件 -->
    <script src="assets/js/main.js"></script>
    <!-- 版权信息 -->
    <meta name="author" content="GooShare - https://gooshare.xyz/">
    <meta name="copyright" content="Copyright (c) <?php echo date('Y'); ?> GooShare. All rights reserved.">
</head>
<body>
    <div class="container">
        <header>
            <!-- 添加LOGO区域 -->
            <div class="logo-container">
                <svg class="logo" width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="40" rx="8" fill="#4361ee"/>
                    <path d="M28 20C28 24.4183 24.4183 28 20 28C15.5817 28 12 24.4183 12 20C12 15.5817 15.5817 12 20 12C24.4183 12 28 15.5817 28 20Z" stroke="white" stroke-width="2"/>
                    <path d="M20 12V28" stroke="white" stroke-width="2"/>
                    <path d="M28 20L12 20" stroke="white" stroke-width="2"/>
                </svg>
                <h1>移动端专属链接</h1>
            </div>
            <p>创建专属于移动设备的访问链接</p>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">首页</a></li>
                    <li><a href="history.php">历史记录</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
