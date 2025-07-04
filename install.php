<?php
// 包含数据库配置
require_once 'includes/config.php';

// 设置页面编码
header('Content-Type: text/html; charset=utf-8');

// 创建urls表的SQL语句，包含所有需要的字段
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

// 创建索引的SQL语句
$createIndexSQL = "
CREATE INDEX idx_short_code ON urls(short_code);
CREATE INDEX idx_is_deleted ON urls(is_deleted);
";

// 尝试创建表
$tableCreated = false;
$indexCreated = false;
$errorMessage = '';

try {
    // 检查表是否已存在
    $tableExists = $conn->query("SHOW TABLES LIKE 'urls'")->num_rows > 0;
    
    if ($tableExists) {
        $tableCreated = true;
    } else {
        // 创建表
        $tableCreated = $conn->query($createTableSQL);
        if (!$tableCreated) {
            throw new Exception("创建表失败: " . $conn->error);
        }
        
        // 创建索引
        $indexCreated = $conn->query($createIndexSQL);
        if (!$indexCreated) {
            throw new Exception("创建索引失败: " . $conn->error);
        }
    }
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}

// 检查是否需要升级表结构
try {
    // 检查是否有password列
    $result = $conn->query("SHOW COLUMNS FROM urls LIKE 'password'");
    if ($result && $result->num_rows == 0) {
        $addColumnResult = $conn->query("ALTER TABLE urls ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT ''");
        if ($addColumnResult) {
            echo '<div class="success">已添加密码字段到数据库</div>';
        }
    }
    
    // 检查是否有restriction列
    $result = $conn->query("SHOW COLUMNS FROM urls LIKE 'restriction'");
    if ($result && $result->num_rows == 0) {
        $addColumnResult = $conn->query("ALTER TABLE urls ADD COLUMN restriction ENUM('mobile_only', 'any_device') DEFAULT 'mobile_only'");
        if ($addColumnResult) {
            echo '<div class="success">已添加访问限制字段到数据库</div>';
        }
    }
    
    // 检查是否有is_deleted列
    $result = $conn->query("SHOW COLUMNS FROM urls LIKE 'is_deleted'");
    if ($result && $result->num_rows == 0) {
        $addColumnResult = $conn->query("ALTER TABLE urls ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
        if ($addColumnResult) {
            echo '<div class="success">已添加软删除标记字段到数据库</div>';
            // 添加索引
            $conn->query("CREATE INDEX idx_is_deleted ON urls(is_deleted);");
        }
    }
} catch (Exception $e) {
    // 表可能不存在，忽略错误
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装 - 移动端专属链接生成器</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f7f9fc;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1, h2 {
            color: #2c3e50;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        code {
            background-color: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>移动端专属链接生成器 - 安装</h1>
    
    <div class="card">
        <h2>数据库连接</h2>
        <?php if ($conn->connect_error): ?>
            <div class="error">
                数据库连接失败: <?php echo $conn->connect_error; ?>
            </div>
            <p>请检查 <code>config.php</code> 文件中的数据库配置信息。</p>
        <?php else: ?>
            <div class="success">
                数据库连接成功！
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!$conn->connect_error): ?>
        <div class="card">
            <h2>表结构</h2>
            <?php if ($errorMessage): ?>
                <div class="error">
                    <?php echo $errorMessage; ?>
                </div>
            <?php elseif ($tableCreated): ?>
                <div class="success">
                    表 <code>urls</code> 已成功<?php echo $tableExists ? '验证' : '创建'; ?>！
                </div>
                <?php if (!$tableExists && $indexCreated): ?>
                    <div class="success">
                        索引 <code>idx_short_code</code> 已成功创建！
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>安装完成</h2>
            <?php if ($tableCreated && !$errorMessage): ?>
                <div class="success">
                    系统已成功安装！现在您可以开始使用移动端专属链接生成器了。
                </div>
                <a href="index.php" class="btn">开始使用</a>
            <?php else: ?>
                <div class="error">
                    安装未完成，请解决上面的错误后重试。
                </div>
                <a href="install.php" class="btn">重试安装</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>手动创建表</h2>
        <p>如果自动安装失败，您可以使用以下SQL语句在数据库中手动创建表：</p>
        <pre><code><?php echo htmlspecialchars($createTableSQL); ?></code></pre>
        <pre><code><?php echo htmlspecialchars($createIndexSQL); ?></code></pre>
    </div>
</body>
</html>
<?php
// 关闭数据库连接
$conn->close();
?>
