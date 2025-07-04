/**
 * 主JavaScript功能文件
 * 
 * 处理表单提交、复制功能、UI交互等
 * 
 * @copyright Copyright (c) 2023 GooShare (https://gooshare.xyz/)
 * @author GooShare <contact@gooshare.xyz>
 * @version 1.2.0
 */

document.addEventListener('DOMContentLoaded', function() {
    // 获取表单和结果元素
    const urlForm = document.getElementById('urlForm');
    const batchForm = document.getElementById('batchForm');
    const resultDiv = document.getElementById('result');
    const batchResultDiv = document.getElementById('batchResult');
    const generatedUrlInput = document.getElementById('generatedUrl');
    const copyBtn = document.getElementById('copyBtn');
    const copyAllBtn = document.getElementById('copyAllBtn');
    const batchResultsBody = document.getElementById('batchResultsBody');
    
    // 选项卡切换
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // 移除所有活动状态
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // 添加活动状态到当前选项卡
            this.classList.add('active');
            document.getElementById(this.dataset.target).classList.add('active');
            
            // 隐藏结果区域
            resultDiv.style.display = 'none';
            batchResultDiv.style.display = 'none';
        });
    });
    
    // 单个URL表单提交处理
    if (urlForm) {
        urlForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const urlInput = document.getElementById('url');
            const passwordInput = document.getElementById('password');
            const url = urlInput.value.trim();
            const password = passwordInput.value.trim();
            
            if (!url) {
                showError('请输入有效的网址');
                return;
            }
            
            if (!password) {
                showError('请设置密码用于链接管理');
                return;
            }
            
            // 显示加载状态
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading"></span> 处理中';
            submitBtn.disabled = true;
            
            // 添加超时保护
            const timeoutId = setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                showError('请求超时，请检查网络连接');
            }, 15000);
            
            // 记录请求参数用于调试
            console.log('准备提交数据:', { url, password });
            
            // 构建标准的URL编码数据而不是使用FormData
            const formData = new URLSearchParams();
            formData.append('url', url);
            formData.append('password', password);
            
            console.log('提交的数据:', formData.toString());
            
            fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => {
                clearTimeout(timeoutId);
                console.log('API响应状态:', response.status);
                
                // 记录原始响应用于调试
                response.clone().text().then(text => {
                    console.log('API原始响应:', text);
                    // 尝试解析JSON，如果失败就显示原始文本
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON解析错误:', e);
                        throw new Error('服务器响应不是有效的JSON格式');
                    }
                }).catch(err => {
                    console.error('响应处理错误:', err);
                    showError('服务器返回了无效响应，请查看控制台');
                });
                
                if (!response.ok) {
                    throw new Error('网络响应不正常: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('API响应数据:', data);
                if (data.success) {
                    showResult(data.shortUrl);
                } else {
                    showError(data.message || '生成链接时出错');
                }
            })
            .catch(error => {
                clearTimeout(timeoutId); // 清除超时
                console.error('API请求错误:', error);
                showError('服务器错误，请检查控制台获取更多信息');
            })
            .finally(() => {
                // 确保按钮状态恢复
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    // 批量URL表单提交处理
    if (batchForm) {
        batchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const batchUrlsInput = document.getElementById('batchUrls');
            const batchPasswordInput = document.getElementById('batchPassword');
            const batchUrls = batchUrlsInput.value.trim();
            const password = batchPasswordInput.value.trim();
            
            if (!batchUrls) {
                showError('请输入至少一个有效的网址');
                return;
            }
            
            if (!password) {
                showError('请设置密码用于链接管理');
                return;
            }
            
            // 分割URL并清理
            const urls = batchUrls.split('\n')
                .map(url => url.trim())
                .filter(url => url.length > 0);
            
            if (urls.length === 0) {
                showError('请输入至少一个有效的网址');
                return;
            }
            
            // 清空之前的结果
            batchResultsBody.innerHTML = '';
            
            // 显示加载中行
            batchResultsBody.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center;">处理中，请稍候...</td>
                </tr>
            `;
            batchResultDiv.style.display = 'block';
            
            // 显示加载状态
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading"></span> 处理中';
            submitBtn.disabled = true;
            
            // 添加超时保护
            const timeoutId = setTimeout(() => {
                submitBtn.innerHTML = originalHTML;
                submitBtn.disabled = false;
                batchResultsBody.innerHTML = '';
                showError('请求超时，请检查网络连接');
            }, 20000); // 20秒超时
            
            // 发送批量请求
            fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    batch: true,
                    urls: urls,
                    password: password
                })
            })
            .then(response => {
                clearTimeout(timeoutId); // 清除超时
                return response.json();
            })
            .then(data => {
                // 清空加载中
                batchResultsBody.innerHTML = '';
                
                if (data.success) {
                    // 添加每个结果到表格
                    data.results.forEach(result => {
                        addBatchResultRow(result.originalUrl, result.success ? result.shortUrl : result.message);
                    });
                    
                    // 滚动到结果区域
                    batchResultDiv.scrollIntoView({ behavior: 'smooth' });
                } else {
                    showError(data.message || '批量生成链接时出错');
                }
            })
            .catch(error => {
                clearTimeout(timeoutId); // 清除超时
                console.error('Error:', error);
                batchResultsBody.innerHTML = '';
                showError('服务器错误，请稍后再试');
            })
            .finally(() => {
                // 确保按钮状态恢复
                submitBtn.innerHTML = originalHTML;
                submitBtn.disabled = false;
            });
        });
    }
    
    // 复制按钮功能
    copyBtn.addEventListener('click', function() {
        generatedUrlInput.select();
        document.execCommand('copy');
        
        showCopySuccess(this);
    });
    
    // 复制所有按钮功能
    copyAllBtn.addEventListener('click', function() {
        const allUrls = Array.from(document.querySelectorAll('.short-url-text'))
            .map(el => el.textContent)
            .join('\n');
        
        const tempTextarea = document.createElement('textarea');
        tempTextarea.value = allUrls;
        document.body.appendChild(tempTextarea);
        tempTextarea.select();
        document.execCommand('copy');
        document.body.removeChild(tempTextarea);
        
        showCopySuccess(this);
    });
    
    // 显示生成的链接
    function showResult(url) {
        generatedUrlInput.value = url;
        resultDiv.style.display = 'block';
        
        // 平滑滚动到结果区域
        resultDiv.scrollIntoView({ behavior: 'smooth' });
    }
    
    // 添加批量结果行
    function addBatchResultRow(originalUrl, shortUrl) {
        const row = document.createElement('tr');
        
        // 检查是否成功
        const isSuccess = !shortUrl.includes('错误');
        
        row.innerHTML = `
            <td class="url-cell" title="${originalUrl}">${originalUrl}</td>
            <td class="url-cell">
                ${isSuccess 
                    ? `<span class="short-url-text">${shortUrl}</span>` 
                    : `<span class="error-text">${shortUrl}</span>`
                }
            </td>
            <td>
                ${isSuccess 
                    ? `<button class="copy-cell-btn" data-url="${shortUrl}">复制</button>` 
                    : '-'
                }
            </td>
        `;
        
        batchResultsBody.appendChild(row);
        
        // 添加复制按钮事件
        if (isSuccess) {
            const copyBtn = row.querySelector('.copy-cell-btn');
            copyBtn.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const tempInput = document.createElement('input');
                tempInput.value = url;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                showCopySuccess(this);
            });
        }
    }
    
    // 显示复制成功提示
    function showCopySuccess(button) {
        const originalText = button.textContent;
        button.textContent = '已复制!';
        button.style.backgroundColor = '#27ae60';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
        }, 2000);
    }
    
    // 显示错误信息
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        // 移除之前的错误信息
        const existingError = document.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // 在表单后插入错误信息
        const activeTab = document.querySelector('.tab-content.active');
        const form = activeTab.querySelector('form');
        form.parentNode.insertBefore(errorDiv, form.nextSibling);
        
        // 2秒后自动消失
        setTimeout(() => {
            errorDiv.style.opacity = '0';
            setTimeout(() => errorDiv.remove(), 500);
        }, 3000);
    }
    
    // 添加表单焦点效果
    const formContainers = document.querySelectorAll('.form-container');
    const formInputs = document.querySelectorAll('input, textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.form-container').classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.closest('.form-container').classList.remove('focused');
            }
        });
    });
    
    // 改进通知显示
    function showNotification(message, type) {
        const notificationEl = document.createElement('div');
        notificationEl.className = `notification ${type}`;
        notificationEl.textContent = message;
        
        // 先移除所有现有通知
        document.querySelectorAll('.notification').forEach(el => el.remove());
        
        // 添加新通知
        document.body.appendChild(notificationEl);
        
        // 5秒后移除
        setTimeout(() => {
            notificationEl.remove();
        }, 5000);
    }
    
    // 为表格添加行悬停效果
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.classList.add('row-hover');
        });
        
        row.addEventListener('mouseleave', function() {
            this.classList.remove('row-hover');
        });
    });
});