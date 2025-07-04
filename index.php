<?php
/**
 * 移动端专属链接生成器 - 首页
 * 
 * 此文件提供了生成移动端专属链接的主界面
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @author GooShare <contact@gooshare.xyz>
 * @version 1.0.0
 */

session_start();
require_once 'includes/functions.php';

$pageTitle = '移动端专属链接生成器';
include 'templates/header.php';
?>

<!-- 选项卡导航 -->
<div class="tabs">
    <button class="tab-btn active" data-target="single-tab">单个网址</button>
    <button class="tab-btn" data-target="batch-tab">批量转换</button>
    <a href="history.php" class="history-link">查看历史记录</a>
</div>

<!-- 单个URL表单 -->
<div id="single-tab" class="tab-content active">
    <div class="form-container">
        <!-- 修改表单: 移除action属性，完全由JavaScript处理提交 -->
        <form id="urlForm" method="post">
            <div class="form-group">
                <label for="url">输入网址:</label>
                <input type="url" id="url" name="url" placeholder="https://example.com" required>
            </div>
            <div class="form-group">
                <label for="password">设置密码 (删除链接时需要):</label>
                <input type="password" id="password" name="password" placeholder="设置一个密码" required>
                <small class="form-text">请记住此密码，删除链接时需要验证</small>
            </div>
            <div class="form-group">
                <label>访问限制:</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="restriction" value="mobile_only" checked>
                        <span>仅限移动设备访问</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="restriction" value="any_device">
                        <span>任意设备均可访问</span>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn">生成专属链接</button>
        </form>
    </div>
    
    <div id="result" class="result-container" style="display: none;">
        <h2>生成的链接</h2>
        <div class="result-box">
            <p>分享此链接，该链接只能在移动设备上打开:</p>
            <div class="url-display">
                <input type="text" id="generatedUrl" readonly>
                <button id="copyBtn" class="btn-copy">复制</button>
            </div>
        </div>
    </div>
</div>

<!-- 批量URL表单 -->
<div id="batch-tab" class="tab-content">
    <div class="form-container">
        <form id="batchForm">
            <div class="form-group">
                <label for="batchUrls">输入多个网址 (每行一个，最多50个):</label>
                <textarea id="batchUrls" name="batchUrls" rows="6" placeholder="https://example1.com&#10;https://example2.com&#10;https://example3.com" required></textarea>
                <small class="form-text">每次批量转换最多支持50个链接</small>
            </div>
            <div class="form-group">
                <label for="batchPassword">设置统一密码 (删除链接时需要):</label>
                <input type="password" id="batchPassword" name="batchPassword" placeholder="设置一个密码" required>
                <small class="form-text">请记住此密码，删除链接时需要验证</small>
            </div>
            <div class="form-group">
                <label>访问限制:</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="batchRestriction" value="mobile_only" checked>
                        <span>仅限移动设备访问</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="batchRestriction" value="any_device">
                        <span>任意设备均可访问</span>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn">批量生成专属链接</button>
        </form>
    </div>
    
    <div id="batchResult" class="result-container" style="display: none;">
        <h2>批量生成结果</h2>
        <div class="result-box">
            <p>以下是生成的专属链接列表，只能在移动设备上打开:</p>
            <div class="batch-results-table">
                <table>
                    <thead>
                        <tr>
                            <th>原始网址</th>
                            <th>专属链接</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="batchResultsBody">
                        <!-- 结果将动态插入这里 -->
                    </tbody>
                </table>
            </div>
            <button id="copyAllBtn" class="btn btn-secondary mt-15">复制所有链接</button>
        </div>
    </div>
</div>

<?php if(isset($_SESSION['error'])): ?>
    <div class="error-message">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>
