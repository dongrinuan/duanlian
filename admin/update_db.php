<?php
/**
 * 数据库升级脚本
 * 
 * 用于检查和添加缺失的数据库字段
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @version 1.0.0
 */

// 开始会话，引入必要文件
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查管理员权限
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$isAdmin) {
    header('Location: index.php?error=unauthorized');
    exit;
}

// 设置页面标题并引入页头
$pageTitle = '数据库更新';
include '../templates/admin_header.php';

$updates = [];
$errors = [];

try {
    // 检查数据库连接
    if ($conn->connect_error) {
        throw new Exception("数据库连接失败: " . $conn->connect_error);
    }
    
    // 检查urls表是否存在
    $tableCheck = $conn->query("SHOW TABLES LIKE 'urls'");
    if ($tableCheck->num_rows === 0) {
        // 创建表，包含所有需要的字段
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS urls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_url VARCHAR(2048) NOT NULL,
            short_code VARCHAR(10) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL DEFAULT '',
            restriction ENUM('mobile_only', 'any_device') DEFAULT 'mobile_only',
            is_deleted TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            access_count INT DEFAULT 0,
            last_accessed TIMESTAMP NULL
        ) ENGINE=InnoDB;
        ";
        
        if ($conn->query($createTableSQL)) {
            $updates[] = "创建了urls表";
            
            // 创建索引
            $createIndexSQL = "CREATE INDEX idx_short_code ON urls(short_code);";
            if ($conn->query($createIndexSQL)) {
                $updates[] = "创建了短链接代码索引";
            } else {
                $errors[] = "创建索引失败: " . $conn->error;
            }
            
            // 创建额外的索引
            $conn->query("CREATE INDEX idx_is_deleted ON urls(is_deleted);");
        } else {
            throw new Exception("创建数据表失败: " . $conn->error);
        }
    } else {
        // 检查并添加password列
        $passwordCheck = $conn->query("SHOW COLUMNS FROM urls LIKE 'password'");
        if ($passwordCheck->num_rows === 0) {
            if ($conn->query("ALTER TABLE urls ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT ''")) {
                $updates[] = "添加了密码字段";
            } else {
                $errors[] = "添加密码字段失败: " . $conn->error;
            }
        }
        
        // 检查并添加restriction列
        $restrictionCheck = $conn->query("SHOW COLUMNS FROM urls LIKE 'restriction'");
        if ($restrictionCheck->num_rows === 0) {
            if ($conn->query("ALTER TABLE urls ADD COLUMN restriction ENUM('mobile_only', 'any_device') DEFAULT 'mobile_only'")) {
                $updates[] = "添加了访问限制字段";
            } else {
                $errors[] = "添加访问限制字段失败: " . $conn->error;
            }
        }
        
        // 检查并添加is_deleted列
        $deletedCheck = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
        if ($deletedCheck->num_rows === 0) {
            if ($conn->query("ALTER TABLE urls ADD COLUMN is_deleted TINYINT(1) DEFAULT 0")) {
                $updates[] = "添加了软删除标记字段";
                // 添加索引
                $conn->query("CREATE INDEX idx_is_deleted ON urls(is_deleted);");
            } else {
                $errors[] = "添加软删除标记字段失败: " . $conn->error;
            }
        }
    }
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}
?>

<div class="dashboard-wrapper">
    <div class="page-header">
        <h2><span class="header-icon">🔄</span> 数据库更新</h2>
        <div class="action-buttons">
            <a href="index.php" class="btn btn-secondary"><span class="btn-icon">◀️</span> 返回首页</a>
        </div>
    </div>

    <!-- 更新结果区域 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">⚙️</span> 更新结果</h3>
            <span class="section-subtitle">检查并更新数据库结构</span>
        </div>
        
        <div class="results-content">
            <?php if (empty($updates) && empty($errors)): ?>
                <div class="result-card success">
                    <div class="result-icon">✅</div>
                    <div class="result-message">数据库结构已是最新，无需更新。</div>
                </div>
            <?php else: ?>
                <?php if (!empty($updates)): ?>
                    <div class="result-section">
                        <h4>成功更新项</h4>
                        <div class="result-items">
                            <?php foreach ($updates as $index => $update): ?>
                                <div class="result-card success">
                                    <div class="result-icon">✅</div>
                                    <div class="result-message"><?php echo $update; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="result-section">
                        <h4>更新失败项</h4>
                        <div class="result-items">
                            <?php foreach ($errors as $index => $error): ?>
                                <div class="result-card error">
                                    <div class="result-icon">❌</div>
                                    <div class="result-message"><?php echo $error; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 下一步操作 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">⏭️</span> 后续操作</h3>
        </div>
        
        <div class="actions-grid">
            <a href="index.php" class="tool-card">
                <div class="tool-icon">🏠</div>
                <div class="tool-content">
                    <div class="tool-title">返回管理首页</div>
                    <div class="tool-desc">返回到管理控制中心</div>
                </div>
                <div class="tool-action">
                    <span class="action-btn">前往 →</span>
                </div>
            </a>
            
            <a href="debug.php" class="tool-card">
                <div class="tool-icon">🛠️</div>
                <div class="tool-content">
                    <div class="tool-title">系统诊断</div>
                    <div class="tool-desc">检查系统运行状态</div>
                </div>
                <div class="tool-action">
                    <span class="action-btn">前往 →</span>
                </div>
            </a>
            
            <a href="links.php" class="tool-card">
                <div class="tool-icon">🔗</div>
                <div class="tool-content">
                    <div class="tool-title">链接管理</div>
                    <div class="tool-desc">查看和管理系统链接</div>
                </div>
                <div class="tool-action">
                    <span class="action-btn">前往 →</span>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    .results-content {
        margin-top: 20px;
    }
    
    .result-section {
        margin-bottom: 25px;
    }
    
    .result-section h4 {
        font-size: 16px;
        color: #2c3e50;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }
    
    .result-items {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .result-card {
        display: flex;
        align-items: center;
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .result-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .result-card.success {
        background-color: #efffef;
        border-left: 4px solid #2ecc71;
    }
    
    .result-card.error {
        background-color: #fff0f0;
        border-left: 4px solid #e74c3c;
    }
    
    .result-icon {
        font-size: 20px;
        margin-right: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
    }
    
    .result-message {
        flex: 1;
        font-size: 15px;
    }
    
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    @media (max-width: 768px) {
        .result-card {
            padding: 12px;
        }
        
        .result-icon {
            font-size: 18px;
            margin-right: 10px;
        }
        
        .result-message {
            font-size: 14px;
        }
    }
</style>

<?php
// 关闭数据库连接并引入页脚
$conn->close();
include '../templates/admin_footer.php';
?>