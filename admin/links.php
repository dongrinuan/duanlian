<?php
/**
 * 链接管理面板
 * 
 * 管理员查看和管理所有链接的页面
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
$pageTitle = '链接管理';
include '../templates/admin_header.php';

// 初始化变量
$links = [];
$error = null;
$success = null;

// 处理表单提交 - 删除链接
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    try {
        // 删除链接 - 使用软删除
        $stmt = $conn->prepare("UPDATE urls SET is_deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        
        if ($result) {
            $success = "链接 #$id 已成功删除";
        } else {
            $error = "删除链接失败: " . $conn->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "删除失败: " . $e->getMessage();
    }
}

// 获取所有链接（排除已软删除的）
try {
    $sql = "SELECT id, original_url, short_code, password, restriction, created_at, access_count, last_accessed 
            FROM urls 
            WHERE is_deleted = 0 OR is_deleted IS NULL
            ORDER BY id ASC";
    $result = $conn->query($sql);
    
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
} catch (Exception $e) {
    $error = "获取链接失败: " . $e->getMessage();
}
?>

<!-- HTML部分开始 -->
<div class="page-header">
    <h2>链接管理</h2>
    <div>
        <a href="recycle.php" class="btn btn-secondary">查看回收站</a>
        <a href="index.php" class="btn btn-secondary">返回管理面板</a>
    </div>
</div>

<!-- 显示结果消息 -->
<?php if ($error): ?>
    <div class="error-message"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-message"><?php echo $success; ?></div>
<?php endif; ?>

<!-- 通知区域 - 由JavaScript控制显示 -->
<div id="notification" class="notification" style="display: none;"></div>

<!-- 批量操作按钮 -->
<div class="batch-operations">
    <button id="batchDeleteBtn" class="btn btn-danger" disabled>批量删除</button>
    <span class="selection-count" id="selectionCount">已选择 0 项</span>
</div>

<!-- 链接列表 -->
<div class="links-container">
    <h3>所有链接 (<?php echo count($links); ?>)</h3>
    
    <?php if (empty($links)): ?>
        <div class="empty-state">
            <p>当前没有任何链接记录。</p>
            <a href="../index.php" class="btn">创建新链接</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
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
                        <td><input type="checkbox" class="link-select" data-id="<?php echo $link['id']; ?>" data-url="<?php echo htmlspecialchars($link['short_url']); ?>"></td>
                        <td><?php echo $link['id']; ?></td>
                        <td class="url-cell" title="<?php echo htmlspecialchars($link['original_url']); ?>">
                            <?php echo htmlspecialchars(truncateUrl($link['original_url'], 30)); ?>
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
                            <button class="delete-btn" data-id="<?php echo $link['id']; ?>">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- CSS样式 -->
<style>
    .links-container {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .admin-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 15px;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .admin-table th {
        background-color: #f1f4f9;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.05em;
    }
    
    .actions {
        display: flex;
        gap: 5px;
    }
    
    .inline-form {
        display: inline-block;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    
    .empty-state p {
        margin-bottom: 20px;
        color: #7f8c8d;
        font-size: 18px;
    }
    
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        border-left: 4px solid #28a745;
    }
    
    .batch-operations {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    
    .selection-count {
        font-size: 14px;
        color: #6c757d;
    }
    
    /* 禁用按钮样式 */
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* 选中行高亮 */
    tr.selected {
        background-color: rgba(67, 97, 238, 0.05);
    }

    /* 响应式表格样式 */
    @media (max-width: 768px) {
        .admin-table {
            font-size: 12px; /* 在小屏幕上减小字体大小 */
        }

        .admin-table th,
        .admin-table td {
            padding: 8px 5px; /* 减小单元格内边距 */
            white-space: normal; /* 允许内容换行 */
        }

        .admin-table .url-cell {
            word-break: break-all; /* 强制长链接换行 */
            max-width: 150px; /* 限制最大宽度 */
            font-size: 11px; /* 进一步减小链接字体 */
        }

        .admin-table th:nth-child(6),
        .admin-table td:nth-child(6) { /* 隐藏创建时间列 */
            display: none;
        }
    }
</style>

<!-- JavaScript处理部分 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 获取所有删除按钮
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const copyButtons = document.querySelectorAll('.copy-btn');
    const notification = document.getElementById('notification');
    const selectAll = document.getElementById('selectAll');
    
    // 为每个删除按钮添加点击事件
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            
            if (confirm('确定要删除这个链接吗？此操作会将链接移至回收站。')) {
                // 使用fetch API发送删除请求
                fetch('../api/admin.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        admin: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 从页面删除该行
                        const row = document.querySelector(`tr[data-id="${id}"]`);
                        if (row) row.remove();
                        
                        // 更新链接计数
                        updateLinkCount();
                        
                        // 显示成功通知
                        showNotification(data.message, 'success');
                    } else {
                        // 显示错误通知
                        showNotification(data.message || '删除失败', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('服务器错误，请稍后再试', 'error');
                });
            }
        });
    });
    
    // 批量处理逻辑
    if (selectAll) {
        const checkboxes = document.querySelectorAll('.link-select');
        const batchDeleteBtn = document.getElementById('batchDeleteBtn');
        
        // 全选/取消全选
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            
            // 更新批量删除按钮状态
            if (batchDeleteBtn) {
                batchDeleteBtn.disabled = !this.checked && document.querySelectorAll('.link-select:checked').length === 0;
            }
        });
        
        // 单个复选框变化时更新全选状态
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedCount = document.querySelectorAll('.link-select:checked').length;
                selectAll.checked = checkedCount === checkboxes.length;
                
                // 更新批量删除按钮状态
                if (batchDeleteBtn) {
                    batchDeleteBtn.disabled = checkedCount === 0;
                }
            });
        });
        
        // 批量删除按钮点击事件
        if (batchDeleteBtn) {
            batchDeleteBtn.addEventListener('click', function() {
                const selectedIds = Array.from(document.querySelectorAll('.link-select:checked'))
                    .map(checkbox => checkbox.getAttribute('data-id'));
                
                if (selectedIds.length === 0) return;
                
                if (confirm(`确定要删除选中的 ${selectedIds.length} 个链接吗？`)) {
                    // 触发批量删除API
                    fetch('../api/admin.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            batch: true,
                            ids: selectedIds,
                            admin: true
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // 从页面移除所有被删除的行
                            selectedIds.forEach(id => {
                                const row = document.querySelector(`tr[data-id="${id}"]`);
                                if (row) row.remove();
                            });
                            
                            // 更新链接计数
                            updateLinkCount();
                            
                            // 显示成功通知
                            showNotification(data.message, 'success');
                            
                            // 重置选择状态
                            selectAll.checked = false;
                            batchDeleteBtn.disabled = true;
                        } else {
                            showNotification(data.message || '批量删除失败', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('服务器错误，请稍后再试', 'error');
                    });
                }
            });
        }
    }
    
    // 为复制按钮添加点击事件
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            copyToClipboard(url, this);
        });
    });
    
    // 复制到剪贴板函数
    function copyToClipboard(text, button) {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        // 视觉反馈
        const originalText = button.textContent;
        button.textContent = '已复制!';
        button.style.backgroundColor = '#27ae60';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
        }, 2000);
    }
    
    // 更新链接计数
    function updateLinkCount() {
        const count = document.querySelectorAll('#linksList tr').length;
        document.querySelector('.links-container h3').textContent = `所有链接 (${count})`;
        
        // 如果没有链接了，显示空状态
        if (count === 0) {
            document.querySelector('.table-container').innerHTML = `
                <div class="empty-state">
                    <p>当前没有任何链接记录。</p>
                    <a href="../index.php" class="btn">创建新链接</a>
                </div>
            `;
        }
    }
    
    // 显示通知函数
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
    
    // 获取DOM元素
    const checkboxes = document.querySelectorAll('.link-select');
    const selectionCount = document.getElementById('selectionCount');
    
    // 全选/取消全选
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const isChecked = this.checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                const row = checkbox.closest('tr');
                if (isChecked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
            
            // 更新按钮状态和选择计数
            updateBatchButtons();
        });
    }
    
    // 单个复选框变化事件
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // 更新行样式
            const row = this.closest('tr');
            if (this.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
            
            // 更新全选框状态
            const checkedCount = document.querySelectorAll('.link-select:checked').length;
            selectAll.checked = checkedCount === checkboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            
            // 更新按钮状态和选择计数
            updateBatchButtons();
        });
    });
    
    // 更新批量操作按钮状态
    function updateBatchButtons() {
        const selectedCount = document.querySelectorAll('.link-select:checked').length;
        batchDeleteBtn.disabled = selectedCount === 0;
        selectionCount.textContent = `已选择 ${selectedCount} 项`;
    }
    
    // 批量删除处理
    if (batchDeleteBtn) {
        batchDeleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.link-select:checked'))
                .map(checkbox => parseInt(checkbox.getAttribute('data-id')));
            
            if (selectedIds.length === 0) return;
            
            if (confirm(`确定要删除选中的 ${selectedIds.length} 个链接吗？这些链接将被移到回收站。`)) {
                // 发送批量删除请求
                fetch('../api/admin.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        batch: true,
                        ids: selectedIds,
                        admin: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 从表格中移除被删除的行
                        selectedIds.forEach(id => {
                            const row = document.querySelector(`tr[data-id="${id}"]`);
                            if (row) row.remove();
                        });
                        
                        // 更新链接计数
                        updateLinkCount();
                        
                        // 重置选择状态
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                        updateBatchButtons();
                        
                        // 显示成功通知
                        showNotification(`成功删除 ${data.affectedRows} 个链接`, 'success');
                    } else {
                        // 显示错误通知
                        showNotification(data.message || '批量删除操作失败', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('服务器错误，请稍后再试', 'error');
                });
            }
        });
    }
});
</script>

<?php
// 关闭数据库连接并引入页脚
$conn->close();
include '../templates/admin_footer.php';
?>