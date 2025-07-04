<?php
/**
 * 链接ID回收工具
 * 
 * 显示已软删除的链接，可以彻底删除或恢复
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @version 1.0.0
 */

// 开始会话，引入必要文件
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 增强安全检查：验证管理员权限
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// 防止直接访问，强制要求先登录管理员帐户
if (!$isAdmin) {
    // 记录未授权访问尝试
    error_log("未授权尝试访问 recycle.php: IP=" . $_SERVER['REMOTE_ADDR']);
    
    // 重定向到管理员登录页面并显示错误消息
    $_SESSION['admin_error'] = '请先登录管理员账户才能访问此页面';
    header('Location: index.php');
    exit;
}

// 设置页面标题并引入页头
$pageTitle = '链接ID回收工具';
include '../templates/admin_header.php';

// 初始化变量
$links = [];
$error = null;
$success = null;

// 处理表单提交 - 永久删除或恢复链接
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $action = sanitizeInput($_POST['action']);
        $id = (int)$_POST['id'];
        
        try {
            if ($action === 'restore') {
                // 恢复链接
                $stmt = $conn->prepare("UPDATE urls SET is_deleted = 0 WHERE id = ?");
                $stmt->bind_param("i", $id);
                $result = $stmt->execute();
                if ($result) {
                    $success = "链接 #$id 已成功恢复";
                } else {
                    $error = "恢复链接失败: " . $conn->error;
                }
                $stmt->close();
            } elseif ($action === 'delete') {
                // 永久删除链接
                $stmt = $conn->prepare("DELETE FROM urls WHERE id = ?");
                $stmt->bind_param("i", $id);
                $result = $stmt->execute();
                if ($result) {
                    $success = "链接 #$id 已永久删除";
                } else {
                    $error = "删除链接失败: " . $conn->error;
                }
                $stmt->close();
            }
            // 移除批量全部操作的处理逻辑
        } catch (Exception $e) {
            $error = "操作失败: " . $e->getMessage();
        }
    }
    if (isset($_POST['action'])) {
        // 批量恢复
        if ($_POST['action'] === 'batch_restore' && isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_map('intval', $_POST['ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            try {
                $stmt = $conn->prepare("UPDATE urls SET is_deleted = 0 WHERE id IN ($placeholders)");
                
                if (!$stmt) {
                    throw new Exception("准备批量恢复语句失败: " . $conn->error);
                }
                
                // 动态生成参数类型字符串
                $types = str_repeat('i', count($ids));
                $stmt->bind_param($types, ...$ids);
                
                $result = $stmt->execute();
                $affectedRows = $stmt->affected_rows;
                $stmt->close();
                
                if ($result) {
                    $success = "已成功恢复 $affectedRows 个链接";
                } else {
                    $error = "批量恢复链接失败: " . $conn->error;
                }
            } catch (Exception $e) {
                $error = "批量恢复操作失败: " . $e->getMessage();
            }
        }
        
        // 批量永久删除
        if ($_POST['action'] === 'batch_permanent_delete' && isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_map('intval', $_POST['ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            try {
                $stmt = $conn->prepare("DELETE FROM urls WHERE id IN ($placeholders)");
                
                if (!$stmt) {
                    throw new Exception("准备批量永久删除语句失败: " . $conn->error);
                }
                
                // 动态生成参数类型字符串
                $types = str_repeat('i', count($ids));
                $stmt->bind_param($types, ...$ids);
                
                $result = $stmt->execute();
                $affectedRows = $stmt->affected_rows;
                $stmt->close();
                
                if ($result) {
                    $success = "已永久删除 $affectedRows 个链接";
                } else {
                    $error = "批量永久删除链接失败: " . $conn->error;
                }
            } catch (Exception $e) {
                $error = "批量永久删除操作失败: " . $e->getMessage();
            }
        }
    }
}

// 获取所有软删除的链接
try {
    $sql = "SELECT id, original_url, short_code, password, restriction, created_at, access_count, last_accessed 
            FROM urls 
            WHERE is_deleted = 1
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
    $error = "获取删除链接失败: " . $e->getMessage();
}

// 获取可用和已使用的链接ID统计
$stats = [];
try {
    // 总链接数
    $result = $conn->query("SELECT COUNT(*) as total FROM urls");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // 活跃链接数
    $result = $conn->query("SELECT COUNT(*) as active FROM urls WHERE is_deleted = 0");
    $stats['active'] = $result->fetch_assoc()['active'];
    
    // 已删除链接数
    $result = $conn->query("SELECT COUNT(*) as deleted FROM urls WHERE is_deleted = 1");
    $stats['deleted'] = $result->fetch_assoc()['deleted'];
    
    // 最大ID值 - 修改查询以处理可能的空表情况
    $result = $conn->query("SELECT COALESCE(MAX(id), 0) as max_id FROM urls");
    $stats['max_id'] = $result->fetch_assoc()['max_id'];
    
    // ID使用效率计算 - 避免除以零错误
    if ($stats['max_id'] > 0) {
        $stats['efficiency'] = round(($stats['total'] / $stats['max_id']) * 100, 2);
    } else {
        $stats['efficiency'] = 100; // 如果没有记录，则效率为100%
    }
    
} catch (Exception $e) {
    $error = "获取统计数据失败: " . $e->getMessage();
}
?>

<!-- HTML部分开始 -->
<div class="page-header">
    <h2>链接ID回收工具 - 管理员专用</h2>
    <div>
        <a href="index.php" class="btn btn-secondary">返回管理面板</a>
        <a href="../index.php" class="btn btn-secondary">返回首页</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="error-message"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-message"><?php echo $success; ?></div>
<?php endif; ?>

<!-- 统计信息 -->
<div class="stats-container">
    <h3>链接ID使用统计</h3>
    <div class="stats-grid">
        <div class="stats-card">
            <div class="stats-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stats-label">总链接数</div>
        </div>
        <div class="stats-card">
            <div class="stats-value"><?php echo number_format($stats['active']); ?></div>
            <div class="stats-label">活跃链接</div>
        </div>
        <div class="stats-card">
            <div class="stats-value"><?php echo number_format($stats['deleted']); ?></div>
            <div class="stats-label">已删除链接</div>
        </div>
        <div class="stats-card">
            <div class="stats-value"><?php echo number_format($stats['max_id']); ?></div>
            <div class="stats-label">最大ID值</div>
        </div>
        <div class="stats-card">
            <div class="stats-value"><?php echo $stats['efficiency']; ?>%</div>
            <div class="stats-label">ID使用效率</div>
        </div>
    </div>
    
    <!-- 移除批量恢复/删除所有链接的按钮 -->
</div>

<!-- 批量操作按钮 -->
<div class="batch-operations">
    <div>
        <button id="batchRestoreBtn" class="btn btn-primary" disabled>批量恢复</button>
        <button id="batchPermanentDeleteBtn" class="btn btn-danger" disabled>批量永久删除</button>
    </div>
    <span class="selection-count" id="selectionCount">已选择 0 项</span>
</div>

<!-- 软删除的链接列表 -->
<div class="deleted-links-container">
    <h3>已删除链接 (<?php echo count($links); ?>)</h3>
    
    <?php if (empty($links)): ?>
        <div class="empty-state">
            <p>没有找到已删除的链接。</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>短代码</th>
                        <th>原始链接</th>
                        <th>创建时间</th>
                        <th>访问次数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="linksList">
                    <?php foreach ($links as $link): ?>
                    <tr data-id="<?php echo $link['id']; ?>">
                        <td><input type="checkbox" class="link-select" data-id="<?php echo $link['id']; ?>"></td>
                        <td><?php echo $link['id']; ?></td>
                        <td><?php echo $link['short_code']; ?></td>
                        <td class="url-cell" title="<?php echo htmlspecialchars($link['original_url']); ?>">
                            <?php echo htmlspecialchars(truncateUrl($link['original_url'], 40)); ?>
                        </td>
                        <td><?php echo formatDate($link['created_at']); ?></td>
                        <td><?php echo $link['access_count']; ?></td>
                        <td class="actions">
                            <form method="post" class="inline-form">
                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                <input type="hidden" name="action" value="restore">
                                <button type="submit" class="btn btn-sm btn-primary">恢复</button>
                            </form>
                            <form method="post" class="inline-form" onsubmit="return confirm('确定要永久删除此链接吗？此操作无法撤销。');">
                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-sm btn-danger">永久删除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 获取DOM元素
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.link-select');
    const batchRestoreBtn = document.getElementById('batchRestoreBtn');
    const batchPermanentDeleteBtn = document.getElementById('batchPermanentDeleteBtn');
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
        batchRestoreBtn.disabled = selectedCount === 0;
        batchPermanentDeleteBtn.disabled = selectedCount === 0;
        selectionCount.textContent = `已选择 ${selectedCount} 项`;
    }
    
    // 批量恢复
    if (batchRestoreBtn) {
        batchRestoreBtn.addEventListener('click', handleBatchRestore);
    }
    
    // 批量永久删除
    if (batchPermanentDeleteBtn) {
        batchPermanentDeleteBtn.addEventListener('click', handleBatchPermanentDelete);
    }
    
    // 批量恢复处理函数
    function handleBatchRestore() {
        const selectedIds = getSelectedIds();
        if (selectedIds.length === 0) return;
        
        if (confirm(`确定要恢复选中的 ${selectedIds.length} 个链接吗？`)) {
            // 使用表单提交进行批量恢复
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // 添加action字段
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'batch_restore';
            form.appendChild(actionInput);
            
            // 添加选中的ID
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            // 添加表单到页面并提交
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // 批量永久删除处理函数
    function handleBatchPermanentDelete() {
        const selectedIds = getSelectedIds();
        if (selectedIds.length === 0) return;
        
        if (confirm(`警告：确定要永久删除选中的 ${selectedIds.length} 个链接吗？此操作无法撤销。`)) {
            // 使用表单提交进行批量永久删除
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // 添加action字段
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'batch_permanent_delete';
            form.appendChild(actionInput);
            
            // 添加选中的ID
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            // 添加表单到页面并提交
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // 获取选中的ID
    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.link-select:checked'))
            .map(checkbox => parseInt(checkbox.getAttribute('data-id')));
    }
});
</script>

<!-- CSS样式 -->
<style>
    .stats-container {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
        margin-bottom: 20px;
    }
    
    .stats-card {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    .stats-value {
        font-size: 24px;
        font-weight: bold;
        color: #4361ee;
        margin-bottom: 5px;
    }
    
    .stats-label {
        font-size: 14px;
        color: #6c757d;
    }
    
    .deleted-links-container {
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
    
    .bulk-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        border-left: 4px solid #28a745;
    }
</style>

<?php
// 关闭数据库连接并引入页脚
$conn->close();
include '../templates/admin_footer.php';
?>