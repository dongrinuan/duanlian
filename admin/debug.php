<?php
/**
 * 调试辅助工具
 * 显示服务器环境信息和测试数据库连接
 * 
 * 安全警告: 在生产环境中应当移除或限制访问此文件
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
$pageTitle = '系统诊断';
include '../templates/admin_header.php';

// 错误报告设置
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 基本环境信息
$phpVersion = phpversion();
$serverInfo = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$scriptFilename = $_SERVER['SCRIPT_FILENAME'];
$postMaxSize = ini_get('post_max_size');
$uploadMaxFilesize = ini_get('upload_max_filesize');
$maxExecutionTime = ini_get('max_execution_time');
$memoryLimit = ini_get('memory_limit');

// 检查扩展
$mysqlEnabled = extension_loaded('mysqli');
$pdoEnabled = extension_loaded('pdo');
$pdoMysqlEnabled = extension_loaded('pdo_mysql');
$jsonEnabled = extension_loaded('json');
$mbstringEnabled = extension_loaded('mbstring');

// 测试文件读写
$logsDir = __DIR__ . '/../logs';
$canCreateDir = !file_exists($logsDir) ? @mkdir($logsDir, 0755, true) : true;
$canWriteFile = $canCreateDir ? @file_put_contents($logsDir . '/test.txt', 'Test') !== false : false;

// 操作系统信息
$osInfo = php_uname();
$serverTime = date('Y-m-d H:i:s');
$timeZone = date_default_timezone_get();
?>

<div class="dashboard-wrapper">
    <div class="page-header">
        <h2><span class="header-icon">🛠️</span> 系统诊断工具</h2>
        <div class="action-buttons">
            <a href="index.php" class="btn btn-secondary"><span class="btn-icon">◀️</span> 返回首页</a>
        </div>
    </div>

    <!-- PHP环境信息 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">🐘</span> PHP环境信息</h3>
            <span class="section-subtitle">系统运行环境详情</span>
        </div>
        
        <div class="info-grid diagnostic-grid">
            <div class="info-item">
                <div class="info-icon">📊</div>
                <div class="info-content">
                    <div class="info-label">PHP版本</div>
                    <div class="info-value">
                        <?php echo $phpVersion; ?>
                        <?php if (version_compare($phpVersion, '7.0.0', '>=')): ?>
                            <span class="badge success">✓</span>
                        <?php else: ?>
                            <span class="badge warning">! 建议 PHP 7.0+</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">🖥️</div>
                <div class="info-content">
                    <div class="info-label">服务器软件</div>
                    <div class="info-value"><?php echo htmlspecialchars($serverInfo); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">⏱️</div>
                <div class="info-content">
                    <div class="info-label">最大执行时间</div>
                    <div class="info-value"><?php echo $maxExecutionTime; ?> 秒</div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">💾</div>
                <div class="info-content">
                    <div class="info-label">内存限制</div>
                    <div class="info-value"><?php echo $memoryLimit; ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">📤</div>
                <div class="info-content">
                    <div class="info-label">上传限制</div>
                    <div class="info-value"><?php echo $uploadMaxFilesize; ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">📦</div>
                <div class="info-content">
                    <div class="info-label">POST最大大小</div>
                    <div class="info-value"><?php echo $postMaxSize; ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">🕒</div>
                <div class="info-content">
                    <div class="info-label">服务器时间</div>
                    <div class="info-value"><?php echo $serverTime; ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">🌐</div>
                <div class="info-content">
                    <div class="info-label">时区设置</div>
                    <div class="info-value"><?php echo $timeZone; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PHP扩展 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">🧩</span> PHP扩展</h3>
            <span class="section-subtitle">必要和推荐的PHP扩展检查</span>
        </div>
        
        <div class="extension-grid">
            <div class="extension-item <?php echo $mysqlEnabled ? 'success' : 'error'; ?>">
                <div class="extension-icon"><?php echo $mysqlEnabled ? '✅' : '❌'; ?></div>
                <div class="extension-name">MySQLi</div>
                <div class="extension-status">
                    <?php echo $mysqlEnabled ? '<span class="status-text success">已启用</span>' : '<span class="status-text error">未启用（必需）</span>'; ?>
                </div>
            </div>
            
            <div class="extension-item <?php echo $pdoEnabled ? 'success' : 'warning'; ?>">
                <div class="extension-icon"><?php echo $pdoEnabled ? '✅' : '⚠️'; ?></div>
                <div class="extension-name">PDO</div>
                <div class="extension-status">
                    <?php echo $pdoEnabled ? '<span class="status-text success">已启用</span>' : '<span class="status-text warning">未启用（推荐）</span>'; ?>
                </div>
            </div>
            
            <div class="extension-item <?php echo $pdoMysqlEnabled ? 'success' : 'warning'; ?>">
                <div class="extension-icon"><?php echo $pdoMysqlEnabled ? '✅' : '⚠️'; ?></div>
                <div class="extension-name">PDO MySQL</div>
                <div class="extension-status">
                    <?php echo $pdoMysqlEnabled ? '<span class="status-text success">已启用</span>' : '<span class="status-text warning">未启用（推荐）</span>'; ?>
                </div>
            </div>
            
            <div class="extension-item <?php echo $jsonEnabled ? 'success' : 'error'; ?>">
                <div class="extension-icon"><?php echo $jsonEnabled ? '✅' : '❌'; ?></div>
                <div class="extension-name">JSON</div>
                <div class="extension-status">
                    <?php echo $jsonEnabled ? '<span class="status-text success">已启用</span>' : '<span class="status-text error">未启用（必需）</span>'; ?>
                </div>
            </div>
            
            <div class="extension-item <?php echo $mbstringEnabled ? 'success' : 'warning'; ?>">
                <div class="extension-icon"><?php echo $mbstringEnabled ? '✅' : '⚠️'; ?></div>
                <div class="extension-name">Mbstring</div>
                <div class="extension-status">
                    <?php echo $mbstringEnabled ? '<span class="status-text success">已启用</span>' : '<span class="status-text warning">未启用（推荐）</span>'; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 数据库连接 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">🗄️</span> 数据库连接</h3>
            <span class="section-subtitle">数据库连接状态和信息</span>
        </div>
        
        <div class="database-status">
            <?php if (!$conn->connect_error): ?>
                <div class="status-indicator success">
                    <div class="status-icon">✅</div>
                    <div class="status-message">数据库连接成功</div>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-icon">🗃️</div>
                        <div class="info-content">
                            <div class="info-label">数据库版本</div>
                            <div class="info-value"><?php echo $conn->server_info; ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">📝</div>
                        <div class="info-content">
                            <div class="info-label">数据库名称</div>
                            <div class="info-value"><?php echo htmlspecialchars($db_name); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">🔤</div>
                        <div class="info-content">
                            <div class="info-label">字符集</div>
                            <div class="info-value"><?php echo $conn->character_set_name(); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- 数据库表格情况 -->
                <div class="table-status">
                    <h4>数据库表</h4>
                    <?php
                        $tablesResult = $conn->query("SHOW TABLES");
                        if ($tablesResult && $tablesResult->num_rows > 0) {
                            echo '<div class="db-tables-grid">';
                            while ($tableRow = $tablesResult->fetch_row()) {
                                $tableName = $tableRow[0];
                                
                                // 获取表的记录数
                                $recordCountResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                                $recordCount = 0;
                                if ($recordCountResult) {
                                    $recordCount = $recordCountResult->fetch_assoc()['count'];
                                }
                                
                                echo '<div class="db-table-item">';
                                echo '<div class="table-name">' . htmlspecialchars($tableName) . '</div>';
                                echo '<div class="table-records">记录数: ' . number_format($recordCount) . '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="empty-state">数据库中没有表</div>';
                        }
                    ?>
                </div>
            <?php else: ?>
                <div class="status-indicator error">
                    <div class="status-icon">❌</div>
                    <div class="status-message">数据库连接失败: <?php echo $conn->connect_error; ?></div>
                </div>
                <div class="db-error-suggestions">
                    <h4>可能的解决方案:</h4>
                    <ul>
                        <li>检查数据库服务器是否运行</li>
                        <li>验证config.php中的数据库凭据是否正确</li>
                        <li>确保数据库用户有足够的权限</li>
                        <li>检查数据库是否存在</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 文件系统 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">📂</span> 文件系统</h3>
            <span class="section-subtitle">文件系统权限和状态</span>
        </div>
        
        <div class="filesystem-grid">
            <div class="filesystem-item">
                <div class="filesystem-icon <?php echo $canCreateDir ? 'success' : 'error'; ?>">
                    <?php echo $canCreateDir ? '✅' : '❌'; ?>
                </div>
                <div class="filesystem-content">
                    <div class="filesystem-name">目录创建权限</div>
                    <div class="filesystem-status">
                        <?php echo $canCreateDir ? '可以创建目录' : '无法创建目录'; ?>
                    </div>
                </div>
            </div>
            
            <div class="filesystem-item">
                <div class="filesystem-icon <?php echo $canWriteFile ? 'success' : 'error'; ?>">
                    <?php echo $canWriteFile ? '✅' : '❌'; ?>
                </div>
                <div class="filesystem-content">
                    <div class="filesystem-name">文件写入权限</div>
                    <div class="filesystem-status">
                        <?php echo $canWriteFile ? '可以写入文件' : '无法写入文件'; ?>
                    </div>
                </div>
            </div>
            
            <div class="filesystem-item">
                <div class="filesystem-icon success">✅</div>
                <div class="filesystem-content">
                    <div class="filesystem-name">文档根目录</div>
                    <div class="filesystem-path"><?php echo htmlspecialchars($documentRoot); ?></div>
                </div>
            </div>
            
            <div class="filesystem-item">
                <div class="filesystem-icon success">✅</div>
                <div class="filesystem-content">
                    <div class="filesystem-name">脚本路径</div>
                    <div class="filesystem-path"><?php echo htmlspecialchars($scriptFilename); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 诊断工具 -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3><span class="section-icon">🧪</span> 诊断工具</h3>
            <span class="section-subtitle">系统功能测试工具</span>
        </div>
        
        <div class="diagnostic-tools-grid">
            <div class="diagnostic-tool">
                <div class="tool-header">
                    <div class="tool-title">API通信测试</div>
                    <button id="apiTestBtn" class="btn btn-sm btn-primary">执行测试</button>
                </div>
                <div class="tool-result" id="apiTestResult">
                    <div class="result-waiting">点击执行按钮运行测试</div>
                </div>
            </div>
            
            <div class="diagnostic-tool">
                <div class="tool-header">
                    <div class="tool-title">数据库查询测试</div>
                    <button id="dbTestBtn" class="btn btn-sm btn-primary">执行测试</button>
                </div>
                <div class="tool-result" id="dbTestResult">
                    <div class="result-waiting">点击执行按钮测试数据库连接速度和状态</div>
                </div>
                <div class="tool-description">
                    执行简单查询，测试数据库连接状态和响应时间
                </div>
            </div>
            
            <div class="diagnostic-tool">
                <div class="tool-header">
                    <div class="tool-title">查看PHP信息</div>
                    <a href="debug.php?phpinfo=1" target="_blank" class="btn btn-sm btn-secondary">查看详情</a>
                </div>
                <div class="tool-description">
                    打开新窗口查看详细的PHP配置信息
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .diagnostic-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important;
    }
    
    .extension-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .extension-item {
        display: flex;
        align-items: center;
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        transition: transform 0.2s;
    }
    
    .extension-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .extension-item.success {
        border-left: 4px solid #2ecc71;
    }
    
    .extension-item.warning {
        border-left: 4px solid #f1c40f;
    }
    
    .extension-item.error {
        border-left: 4px solid #e74c3c;
    }
    
    .extension-icon {
        font-size: 20px;
        margin-right: 15px;
    }
    
    .extension-name {
        font-weight: 600;
        flex: 1;
    }
    
    .extension-status {
        font-size: 13px;
    }
    
    .status-text {
        padding: 3px 8px;
        border-radius: 4px;
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    .status-text.success {
        background-color: rgba(46, 204, 113, 0.1);
        color: #27ae60;
    }
    
    .status-text.warning {
        background-color: rgba(241, 196, 15, 0.1);
        color: #f39c12;
    }
    
    .status-text.error {
        background-color: rgba(231, 76, 60, 0.1);
        color: #c0392b;
    }
    
    .database-status {
        margin-top: 20px;
    }
    
    .status-indicator {
        display: flex;
        align-items: center;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .status-indicator.success {
        background-color: rgba(46, 204, 113, 0.1);
        border-left: 4px solid #2ecc71;
    }
    
    .status-indicator.error {
        background-color: rgba(231, 76, 60, 0.1);
        border-left: 4px solid #e74c3c;
    }
    
    .status-icon {
        font-size: 24px;
        margin-right: 15px;
    }
    
    .status-message {
        font-size: 16px;
        font-weight: 500;
    }
    
    .db-tables-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    
    .db-table-item {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        transition: transform 0.2s;
    }
    
    .db-table-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .table-name {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .table-records {
        font-size: 13px;
        color: #7f8c8d;
    }
    
    .table-status h4, .db-error-suggestions h4 {
        margin-top: 20px;
        margin-bottom: 15px;
        font-size: 16px;
        color: #2c3e50;
    }
    
    .empty-state {
        text-align: center;
        padding: 30px;
        color: #7f8c8d;
        font-style: italic;
    }
    
    .filesystem-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .filesystem-item {
        display: flex;
        align-items: center;
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        transition: transform 0.2s;
    }
    
    .filesystem-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .filesystem-icon {
        font-size: 20px;
        margin-right: 15px;
        width: 30px;
        text-align: center;
    }
    
    .filesystem-icon.success {
        color: #2ecc71;
    }
    
    .filesystem-icon.error {
        color: #e74c3c;
    }
    
    .filesystem-content {
        flex: 1;
    }
    
    .filesystem-name {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .filesystem-status, .filesystem-path {
        font-size: 13px;
        color: #7f8c8d;
        word-break: break-all;
    }
    
    .diagnostic-tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .diagnostic-tool {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        transition: transform 0.2s;
    }
    
    .diagnostic-tool:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .tool-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .tool-title {
        font-weight: 600;
        font-size: 16px;
    }
    
    .tool-result {
        margin-top: 15px;
        padding: 15px;
        background-color: #fff;
        border-radius: 6px;
        border: 1px solid #eee;
        min-height: 60px;
    }
    
    .result-waiting {
        color: #7f8c8d;
        font-style: italic;
    }
    
    .tool-description {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        margin-left: 5px;
    }
    
    .badge.success {
        background-color: rgba(46, 204, 113, 0.1);
        color: #27ae60;
    }
    
    .badge.warning {
        background-color: rgba(241, 196, 15, 0.1);
        color: #f39c12;
    }
    
    @media (max-width: 768px) {
        .extension-grid,
        .filesystem-grid,
        .diagnostic-tools-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php if (isset($_GET['phpinfo'])): ?>
    <style>
        .phpinfo-wrapper {
            margin-top: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow: hidden;
        }
        .phpinfo-wrapper div.center {
            text-align: left !important;
        }
        .phpinfo-wrapper h1 {
            font-size: 24px !important;
            color: #2c3e50 !important;
        }
        .phpinfo-wrapper h2 {
            font-size: 20px !important;
            color: #3498db !important;
        }
        .phpinfo-wrapper table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 15px 0 !important;
        }
        .phpinfo-wrapper td, .phpinfo-wrapper th {
            padding: 8px !important;
            border: 1px solid #eee !important;
        }
        .phpinfo-wrapper tr:nth-child(odd) {
            background-color: #f8f9fa !important;
        }
    </style>
    <div class="phpinfo-wrapper"></div>
        <?php
            ob_start();
            phpinfo();
            $phpinfo = ob_get_clean();
            
            // 提取<body>内容
            $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%s', '$1', $phpinfo);
            echo $phpinfo;
        ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // API通信测试
    const apiTestBtn = document.getElementById('apiTestBtn');
    const apiTestResult = document.getElementById('apiTestResult');
    
    if (apiTestBtn && apiTestResult) {
        apiTestBtn.addEventListener('click', function() {
            apiTestResult.innerHTML = '<div class="loading-indicator">测试中...</div>';
            
            fetch('../api/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'url': 'https://example.com',
                    'password': 'test_password',
                    'restriction': 'mobile_only'
                })
            })
            .then(response => {
                let result = `<div style="margin-bottom:10px">HTTP状态码: <span class="${response.ok ? 'text-success' : 'text-danger'}">${response.status}</span></div>`;
                return response.json().then(data => {
                    result += `<pre style="background:#f0f0f0;padding:10px;overflow:auto;font-size:12px;max-height:150px">${JSON.stringify(data, null, 2)}</pre>`;
                    return { html: result, success: response.ok };
                });
            })
            .then(data => {
                apiTestResult.innerHTML = data.html;
                apiTestResult.classList.add(data.success ? 'success-result' : 'error-result');
            })
            .catch(error => {
                apiTestResult.innerHTML = `<div class="error-message">测试失败: ${error.message}</div>`;
                apiTestResult.classList.add('error-result');
            });
        });
    }
    
    // 数据库测试
    const dbTestBtn = document.getElementById('dbTestBtn');
    const dbTestResult = document.getElementById('dbTestResult');
    
    if (dbTestBtn && dbTestResult) {
        dbTestBtn.addEventListener('click', function() {
            dbTestResult.innerHTML = '<div class="loading-indicator">测试中...</div>';
            
            fetch('?db_test=1', {
                method: 'GET'
            })
            .then(response => response.text())
            .then(html => {
                dbTestResult.innerHTML = html;
            })
            .catch(error => {
                dbTestResult.innerHTML = `<div class="error-message">测试失败: ${error.message}</div>`;
            });
        });
    }
});
</script>

<?php
// 处理数据库测试请求
if (isset($_GET['db_test'])) {
    try {
        $startTime = microtime(true);
        $result = $conn->query("SELECT 1");
        $endTime = microtime(true);
        $queryTime = round(($endTime - $startTime) * 1000, 2);
        
        // 获取额外的数据库信息
        $threadInfo = $conn->thread_id ? "线程ID: {$conn->thread_id}" : "";
        $connectionStats = $conn->stat() ? "连接状态: {$conn->stat()}" : "";
        
        echo '<div class="db-test-result success">';
        echo '<div class="test-status">查询成功 ✅</div>';
        echo '<div class="test-details">';
        echo '<div>查询时间: <strong>' . $queryTime . ' ms</strong> ' . ($queryTime < 10 ? '(响应良好)' : '(响应较慢)') . '</div>';
        if ($threadInfo) echo "<div>$threadInfo</div>";
        if ($connectionStats) echo "<div>$connectionStats</div>";
        echo '</div>';
        echo '</div>';
    } catch (Exception $e) {
        echo '<div class="db-test-result error">';
        echo '<div class="test-status">查询失败 ❌</div>';
        echo '<div class="test-details">错误: ' . $e->getMessage() . '</div>';
        echo '<div class="error-suggestion">请检查数据库配置和连接状态</div>';
        echo '</div>';
    }
    exit;
}

// 关闭数据库连接并引入页脚
$conn->close();
include '../templates/admin_footer.php';
?>