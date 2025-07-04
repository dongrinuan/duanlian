<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : '管理员控制面板'; ?> - 移动端专属链接生成器</title>
    <!-- 添加Google Fonts - Inter字体 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- 先加载通用样式，后加载管理员样式，确保样式优先级正确 -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- 添加网站图标 -->
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <!-- 版权信息 -->
    <meta name="author" content="GooShare - https://gooshare.xyz/">
    <meta name="copyright" content="Copyright (c) <?php echo date('Y'); ?> GooShare. All rights reserved.">
</head>
<body>
    <!-- 遮罩层 -->
    <div class="sidebar-overlay"></div>
    
    <div class="admin-container">
        <!-- 侧边栏 -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <svg width="30" height="30" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="#4361ee"/>
                        <path d="M28 20C28 24.4183 24.4183 28 20 28C15.5817 28 12 24.4183 12 20C12 15.5817 15.5817 12 20 12C24.4183 12 28 15.5817 28 20Z" stroke="white" stroke-width="2"/>
                        <path d="M20 12V28" stroke="white" stroke-width="2"/>
                        <path d="M28 20L12 20" stroke="white" stroke-width="2"/>
                    </svg>
                    <span>管理控制台</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🏠</span> 管理首页
                    </a></li>
                    <li><a href="links.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'links.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🔗</span> 链接管理
                    </a></li>
                    <li><a href="recycle.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'recycle.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">♻️</span> 回收站
                    </a></li>
                    <li><a href="update_db.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'update_db.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🔄</span> 数据库更新
                    </a></li>
                    <li><a href="debug.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'debug.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🛠️</span> 系统诊断
                    </a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="../index.php"><span class="nav-icon">🏠</span> 返回前台</a>
                <?php if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                <a href="index.php?logout=1"><span class="nav-icon">🚪</span> 退出登录</a>
                <?php endif; ?>
            </div>
        </aside>
        
        <!-- 内容区域 -->
        <main class="admin-content">
