<?php
/**
 * 管理员控制台 - 主入口
 * 
 * 处理管理员登录和管理功能的统一入口
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @author GooShare <contact@gooshare.xyz>
 * @version 1.1.0
 */

// 开始会话，引入必要文件
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        // 不进行重定向，直接显示管理页面
    } else {
        $error = '密码不正确';
    }
}

// 处理登出请求
if (isset($_GET['logout'])) {
    // 清除会话数据
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// 获取错误消息
$loginError = isset($_GET['error']) ? $_GET['error'] : '';
if ($loginError === 'unauthorized') {
    $error = '请先登录后再访问管理页面';
}

// 检查管理员登录状态
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// 如果已登录，获取系统数据
if ($isAdmin) {
    try {
        // 总链接数
        $result = $conn->query("SELECT COUNT(*) as count FROM urls");
        $totalLinks = $result ? $result->fetch_assoc()['count'] : 0;
        
        // 活跃链接数
        $result = $conn->query("SELECT COUNT(*) as count FROM urls WHERE is_deleted = 0 OR is_deleted IS NULL");
        $activeLinks = $result ? $result->fetch_assoc()['count'] : 0;
        
        // 软删除链接数
        $result = $conn->query("SELECT COUNT(*) as count FROM urls WHERE is_deleted = 1");
        $deletedLinks = $result ? $result->fetch_assoc()['count'] : 0;
        
        // 今日创建
        $today = date('Y-m-d');
        $result = $conn->query("SELECT COUNT(*) as count FROM urls WHERE DATE(created_at) = '$today'");
        $todayLinks = $result ? $result->fetch_assoc()['count'] : 0;
        
        // 总访问次数
        $result = $conn->query("SELECT SUM(access_count) as total FROM urls");
        $totalAccess = $result ? $result->fetch_assoc()['total'] : 0;
    } catch (Exception $e) {
        error_log("Admin error: " . $e->getMessage());
    }
    
    // 设置页面标题并引入页头
    $pageTitle = '管理首页';
    include '../templates/admin_header.php';
?>

<div class="dashboard-wrapper">
    <div class="page-header">
        <h2><span class="header-icon">🏠</span> 系统管理中心</h2>
        <div class="action-buttons">
            <a href="../index.php" class="btn btn-secondary"><span class="btn-icon">🌐</span> 访问前台</a>
            <a href="?logout=1" class="btn btn-danger"><span class="btn-icon">🚪</span> 退出登录</a>
        </div>
    </div>
    
    <!-- 系统概览 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">📊</span> 系统概览</h3>
            <span class="section-subtitle">截至 <?php echo date('Y-m-d H:i'); ?> 的系统数据</span>
        </div>
        
        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-card-inner">
                    <div class="stats-icon primary">📊</div>
                    <div class="stats-content">
                        <div class="stats-value"><?php echo number_format($totalLinks); ?></div>
                        <div class="stats-label">总链接数</div>
                    </div>
                </div>
                <div class="stats-footer">
                    <span class="stats-trend positive">系统总计</span>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-card-inner">
                    <div class="stats-icon success">✅</div>
                    <div class="stats-content">
                        <div class="stats-value"><?php echo number_format($activeLinks); ?></div>
                        <div class="stats-label">活跃链接</div>
                    </div>
                </div>
                <div class="stats-footer">
                    <span class="stats-trend">可正常访问</span>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-card-inner">
                    <div class="stats-icon warning">🗑️</div>
                    <div class="stats-content">
                        <div class="stats-value"><?php echo number_format($deletedLinks); ?></div>
                        <div class="stats-label">回收站链接</div>
                    </div>
                </div>
                <div class="stats-footer">
                    <a href="recycle.php" class="stats-link">管理回收站 →</a>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-card-inner">
                    <div class="stats-icon info">📅</div>
                    <div class="stats-content">
                        <div class="stats-value"><?php echo number_format($todayLinks); ?></div>
                        <div class="stats-label">今日新增</div>
                    </div>
                </div>
                <div class="stats-footer">
                    <span class="stats-trend"><?php echo date('Y-m-d'); ?></span>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-card-inner">
                    <div class="stats-icon accent">👁️</div>
                    <div class="stats-content">
                        <div class="stats-value"><?php echo number_format($totalAccess); ?></div>
                        <div class="stats-label">总访问量</div>
                    </div>
                </div>
                <div class="stats-footer">
                    <span class="stats-trend positive">累计点击次数</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 快速管理工具 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">🔧</span> 管理工具</h3>
            <span class="section-subtitle">常用功能快速访问</span>
        </div>
        
        <div class="tools-grid">
            <a href="links.php" class="tool-card">
                <div class="tool-icon">🔗</div>
                <div class="tool-content">
                    <div class="tool-title">链接管理</div>
                    <div class="tool-desc">查看和管理所有短链接</div>
                </div>
                <div class="tool-action">
                    <span class="action-btn">进入 →</span>
                </div>
            </a>
            
            <a href="recycle.php" class="tool-card">
                <div class="tool-icon">♻️</div>
                <div class="tool-content">
                    <div class="tool-title">链接回收站</div>
                    <div class="tool-desc">管理已删除的链接和回收ID</div>
                </div>
                <div class="tool-action">
                    <span class="action-btn">进入 →</span>
                </div>
            </a>
            
            <a href="update_db.php" class="tool-card">
                <div class="tool-icon">🔄</div>
                <div class="tool-content">
                    <div class="tool-title">更新数据库</div>
                    <div class="tool-desc">检查并更新数据库结构</div>
                </div>
                <div class="tool-action">
                    <span class="action-btn">进入 →</span>
                </div>
            </a>
            
            <a href="debug.php" class="tool-card">
                <div class="tool-icon">🛠️</div>
                <div class="tool-content">
                    <div class="tool-title">系统诊断</div>
                    <div class="tool-desc">检查系统状态和环境</div>
                </div>
                <div class="tool-action">
                    <span class="action-btn">进入 →</span>
                </div>
            </a>
        </div>
    </div>
    
    <!-- 系统信息 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">ℹ️</span> 系统信息</h3>
            <span class="section-subtitle">服务器和应用环境</span>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-icon">🐘</div>
                <div class="info-content">
                    <div class="info-label">PHP版本</div>
                    <div class="info-value"><?php echo phpversion(); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">🗄️</div>
                <div class="info-content">
                    <div class="info-label">MySQL版本</div>
                    <div class="info-value"><?php echo $conn->server_info; ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">🖥️</div>
                <div class="info-content">
                    <div class="info-label">服务器环境</div>
                    <div class="info-value"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE']); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">📌</div>
                <div class="info-content">
                    <div class="info-label">应用版本</div>
                    <div class="info-value">1.3.0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    // 关闭数据库连接并引入页脚
    $conn->close();
    include '../templates/admin_footer.php';
} else {
    // 未登录，显示登录页面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 移动端专属链接生成器</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .login-btn {
            width: 100%;
            margin-top: 10px;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        .login-footer a {
            color: #4361ee;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .admin-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">管理员登录</h2>
        
        <?php if (isset($error)): ?>
            <div class="admin-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['admin_error'])): ?>
            <div class="admin-error"><?php echo $_SESSION['admin_error']; unset($_SESSION['admin_error']); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="password">管理员密码:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn login-btn">登录</button>
        </form>
        
        <div class="login-footer">
            <a href="../index.php">返回首页</a>
        </div>
    </div>
</body>
</html>
<?php
}
?>
