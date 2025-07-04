<?php
/**
 * 链接历史记录页面
 * 
 * 通过密码搜索查看和管理用户创建的链接
 * 功能包括:
 * - 按密码搜索链接
 * - 查看链接详情和访问统计
 * - 复制和删除链接
 * - 批量操作(复制/删除)多个链接
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @author GooShare <contact@gooshare.xyz>
 * @version 1.3.0
 */

// 开始会话，引入必要文件
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 设置页面标题并引入页头
$pageTitle = '链接历史记录';
include 'templates/header.php';

// 初始化数据库表 - 添加这段代码来解决表不存在的问题
try {
    // 检查urls表是否存在，如果不存在则创建
    $tableExists = $conn->query("SHOW TABLES LIKE 'urls'")->num_rows > 0;
    
    if (!$tableExists) {
        // 创建表
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS urls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_url VARCHAR(2048) NOT NULL,
            short_code VARCHAR(10) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
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
    }
} catch (Exception $e) {
    $error = "初始化数据库失败: " . $e->getMessage();
}

// 初始化变量
$links = [];
$error = null;
$password = '';
$hasSearched = false;

// 处理表单提交 - 密码搜索
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = sanitizeInput($_POST['password']);
    $hasSearched = true;
    
    // 验证密码
    if (empty($password)) {
        $error = "请输入密码";
    } else {
        try {
            // 检查数据库表是否存在
            $tableCheck = $conn->query("SHOW TABLES LIKE 'urls'");
            if ($tableCheck->num_rows === 0) {
                $error = "还没有创建任何链接，请先前往首页创建链接";
            } else {
                // 修改查询，增加获取restriction字段，并排除已删除的链接
                $stmt = $conn->prepare("SELECT id, original_url, short_code, restriction, created_at, access_count, last_accessed 
                        FROM urls 
                        WHERE password = ? AND (is_deleted IS NULL OR is_deleted = 0)
                        ORDER BY created_at DESC");
                
                // 添加错误检查
                if ($stmt === false) {
                    throw new Exception("数据库查询准备失败: " . $conn->error);
                }
                
                $stmt->bind_param("s", $password);
                $stmt->execute();
                $result = $stmt->get_result();
                
                // 处理查询结果
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        // 构建完整短链接URL
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'];
                        $shortUrl = "$protocol://$host/go.php?code=" . $row['short_code'];
                        
                        $row['short_url'] = $shortUrl;
                        $links[] = $row;
                    }
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $error = "获取历史记录时出错: " . $e->getMessage();
            error_log("History.php 错误: " . $e->getMessage());
        }
    }
}
?>

<!-- HTML部分开始 -->
<div class="page-header">
    <h2>链接历史记录</h2>
    <a href="index.php" class="btn btn-secondary">返回首页</a>
</div>

<!-- 错误提示区域 -->
<?php if ($error): ?>
    <div class="error-message"><?php echo $error; ?></div>
<?php endif; ?>

<!-- 通知区域 - 由JavaScript控制显示 -->
<div id="notification" class="notification" style="display: none;"></div>

<!-- 密码搜索表单 -->
<div class="search-container">
    <form method="post" class="password-search-form">
        <div class="form-group">
            <label for="password">输入创建链接时设置的密码:</label>
            <div class="search-input-group">
                <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>" placeholder="请输入密码" required>
                <button type="submit" class="btn">搜索链接</button>
            </div>
            <small class="form-text">输入您创建链接时设置的密码，查看和管理您的链接</small>
        </div>
    </form>
</div>

<!-- 历史记录展示区域 -->
<div class="history-container">
    <?php if ($hasSearched): ?>
        <?php if (empty($links)): ?>
            <div class="empty-state">
                <p>未找到使用该密码创建的链接。</p>
                <a href="index.php" class="btn">创建新链接</a>
            </div>
        <?php else: ?>
            <div class="results-header">
                <div class="flex-space-between">
                    <h3>搜索结果: 共找到 <?php echo count($links); ?> 个链接</h3>
                    <div class="batch-actions">
                        <button id="batchCopyBtn" class="btn btn-secondary btn-sm" disabled>批量复制</button>
                        <button id="batchDeleteBtn" class="btn btn-danger btn-sm" disabled>批量删除</button>
                    </div>
                </div>
            </div>
            
            <!-- 分离表头和表体 -->
            <div class="table-header">
                <table class="history-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>原始链接</th>
                        <th>短链接</th>
                        <th>访问限制</th>
                        <th>创建时间</th>
                        <th>访问次数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                </table>
            </div>
            
            <div class="table-container">
                <table class="history-table table-body">
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>原始链接</th>
                        <th>短链接</th>
                        <th>访问限制</th>
                        <th>创建时间</th>
                        <th>访问次数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="linksList">
                    <?php foreach ($links as $link): ?>
                    <tr data-id="<?php echo $link['id']; ?>">
                        <td>
                            <input type="checkbox" class="link-select" 
                                   data-id="<?php echo $link['id']; ?>" 
                                   data-url="<?php echo htmlspecialchars($link['short_url']); ?>">
                        </td>
                        <td class="url-cell" title="<?php echo htmlspecialchars($link['original_url']); ?>">
                            <?php echo htmlspecialchars(truncateUrl($link['original_url'], 40)); ?>
                        </td>
                        <td class="url-cell">
                            <span class="short-url-text"><?php echo htmlspecialchars($link['short_url']); ?></span>
                        </td>
                        <td>
                            <?php 
                                $restrictionText = isset($link['restriction']) && $link['restriction'] == 'any_device' 
                                    ? '<span class="tag tag-success">全设备</span>' 
                                    : '<span class="tag tag-primary">仅移动</span>'; 
                                echo $restrictionText;
                            ?>
                        </td>
                        <td><?php echo formatDate($link['created_at']); ?></td>
                        <td><?php echo $link['access_count']; ?></td>
                        <td>
                            <button class="copy-btn" data-url="<?php echo htmlspecialchars($link['short_url']); ?>">复制</button>
                            <button class="delete-btn" data-id="<?php echo $link['id']; ?>" data-password="<?php echo htmlspecialchars($password); ?>">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <p>请输入创建链接时设置的密码来查看您的链接。</p>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript处理部分 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM 元素
    const notification = document.getElementById('notification');
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.link-select');
    const batchCopyBtn = document.getElementById('batchCopyBtn');
    const batchDeleteBtn = document.getElementById('batchDeleteBtn');
    const password = '<?php echo htmlspecialchars($password); ?>';
    
    // 初始化功能
    initializeCopyButtons();
    initializeDeleteButtons();
    initializeBatchOperations();
    initializeFixedHeader();
    
    // 复制按钮功能
    function initializeCopyButtons() {
        const copyButtons = document.querySelectorAll('.copy-btn');
        copyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                copyToClipboard(this.getAttribute('data-url'), this);
            });
        });
    }
    
    // 复制内容到剪贴板
    function copyToClipboard(text, button) {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        if (button) {
            // 显示复制成功提示
            const originalText = button.textContent;
            button.textContent = '已复制!';
            button.style.backgroundColor = '#27ae60';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.backgroundColor = '';
            }, 2000);
        }
    }
    
    // 删除功能
    function initializeDeleteButtons() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                
                if (confirm('确定要删除这个链接吗？此操作无法撤销。')) {
                    deleteLink(id, password).then(success => {
                        if (success) {
                            removeTableRow(id);
                            checkEmptyTable();
                        }
                    });
                }
            });
        });
    }
    
    // 删除链接的API请求
    function deleteLink(id, password) {
        return fetch('api/index.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                return true;
            } else {
                showNotification(data.message, 'error');
                return false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('服务器错误，请稍后再试', 'error');
            return false;
        });
    }
    
    // 移除表格行
    function removeTableRow(id) {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) row.remove();
    }
    
    // 检查表格是否为空
    function checkEmptyTable() {
        if (document.querySelectorAll('#linksList tr').length === 0) {
            document.querySelector('.history-container').innerHTML = `
                <div class="results-header">
                    <h3>搜索结果: 共找到 0 个链接</h3>
                </div>
                <div class="empty-state">
                    <p>未找到使用该密码创建的链接。</p>
                    <a href="index.php" class="btn">创建新链接</a>
                </div>
            `;
        }
    }
    
    // 批量操作功能
    function initializeBatchOperations() {
        // 全选/取消全选
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBatchButtons();
            });
        }
        
        // 单个复选框变化时更新批量按钮状态
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBatchButtons);
        });
        
        // 批量复制
        if (batchCopyBtn) {
            batchCopyBtn.addEventListener('click', batchCopy);
        }
        
        // 批量删除
        if (batchDeleteBtn) {
            batchDeleteBtn.addEventListener('click', batchDelete);
        }
    }
    
    // 更新批量按钮状态
    function updateBatchButtons() {
        const selectedCount = document.querySelectorAll('.link-select:checked').length;
        batchCopyBtn.disabled = selectedCount === 0;
        batchDeleteBtn.disabled = selectedCount === 0;
        
        // 更新全选框状态
        if (checkboxes.length > 0) {
            if (selectedCount === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (selectedCount === checkboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }
    }
    
    // 批量复制
    function batchCopy() {
        const selectedUrls = Array.from(document.querySelectorAll('.link-select:checked'))
            .map(checkbox => checkbox.getAttribute('data-url'));
            
        if (selectedUrls.length === 0) return;
        
        copyToClipboard(selectedUrls.join('\n'));
        showNotification(`已复制 ${selectedUrls.length} 个链接`, 'success');
    }
    
    // 批量删除
    function batchDelete() {
        const selectedItems = Array.from(document.querySelectorAll('.link-select:checked'))
            .map(checkbox => ({
                id: checkbox.getAttribute('data-id'),
                element: checkbox.closest('tr')
            }));
            
        if (selectedItems.length === 0) return;
        
        if (confirm(`确定要删除这 ${selectedItems.length} 个链接吗？此操作无法撤销。`)) {
            let deletedCount = 0;
            let failedCount = 0;
            
            // 使用Promise.all并行处理所有删除请求
            Promise.all(selectedItems.map(item => {
                return deleteLink(item.id, password)
                    .then(success => {
                        if (success) {
                            item.element.remove();
                            deletedCount++;
                        } else {
                            failedCount++;
                        }
                    })
                    .catch(() => failedCount++);
            }))
            .then(() => {
                // 显示操作结果
                let message = '';
                if (deletedCount > 0) {
                    message += `成功删除 ${deletedCount} 个链接。`;
                }
                if (failedCount > 0) {
                    message += `${failedCount} 个链接删除失败。`;
                }
                
                showNotification(message, deletedCount > 0 ? 'success' : 'error');
                checkEmptyTable();
                updateBatchButtons();
            });
        }
    }
    
    // 初始化固定表头功能
    function initializeFixedHeader() {
        const tableContainer = document.querySelector('.table-container');
        if (tableContainer) {
            tableContainer.addEventListener('scroll', function() {
                const tableHeader = document.querySelector('.table-header');
                if (tableHeader) {
                    tableHeader.scrollLeft = this.scrollLeft;
                }
            });
        }
    }
    
    /**
     * 显示通知
     * 显示操作结果通知并自动隐藏
     * 
     * @param {string} message 通知消息
     * @param {string} type 通知类型 ('success'或'error')
     */
    function showNotification(message, type) {
        notification.textContent = message;
        notification.className = 'notification ' + type;
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.style.display = 'none';
                notification.style.opacity = '1';
            }, 500);
        }, 3000);
    }
});
</script>

<?php
// 关闭数据库连接并引入页脚
$conn->close();
include 'templates/footer.php';
?>
