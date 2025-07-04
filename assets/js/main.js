// 单个链接表单处理
document.addEventListener('DOMContentLoaded', function() {
    const urlForm = document.getElementById('urlForm');
    const batchForm = document.getElementById('batchForm');
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    // 切换选项卡
    if (tabBtns) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-target');
                
                // 切换按钮高亮
                tabBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // 切换内容
                const tabContents = document.querySelectorAll('.tab-content');
                tabContents.forEach(tab => {
                    tab.classList.remove('active');
                    if (tab.id === targetTab) {
                        tab.classList.add('active');
                    }
                });
            });
        });
    }
    
    // 单个链接表单提交
    if (urlForm) {
        urlForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const url = document.getElementById('url').value;
            const password = document.getElementById('password').value;
            const restriction = document.querySelector('input[name="restriction"]:checked').value;
            
            // 发送AJAX请求
            fetch('api/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'url': url,
                    'password': password,
                    'restriction': restriction
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 显示结果
                    document.getElementById('generatedUrl').value = data.shortUrl;
                    document.getElementById('result').style.display = 'block';
                    
                    // 清除之前的标签，然后添加新的限制类型标记
                    const resultBox = document.querySelector('.result-box p');
                    // 移除所有之前的标签
                    const existingTags = resultBox.querySelectorAll('.tag');
                    existingTags.forEach(tag => tag.remove());
                    
                    const restrictionTag = document.createElement('div');
                    restrictionTag.className = data.restriction === 'any_device' ? 'tag tag-success' : 'tag tag-primary';
                    restrictionTag.textContent = data.restriction === 'any_device' ? '全设备可访问' : '仅移动端可访问';
                    resultBox.appendChild(restrictionTag);
                    
                    // 3秒后触发消失动画，并在动画结束后移除标签
                    const fadeOutListener = (event) => {
                        // 确保是消失动画结束
                        if (event.animationName === 'tag-disappear') {
                            restrictionTag.remove();
                            // 移除事件监听器，避免内存泄漏
                            restrictionTag.removeEventListener('animationend', fadeOutListener);
                        }
                    };
                    restrictionTag.addEventListener('animationend', fadeOutListener);

                    setTimeout(() => {
                        if (restrictionTag && restrictionTag.parentNode) {
                            restrictionTag.classList.add('fade-out');
                        }
                    }, 3000);
                    
                    // 滚动到结果区域
                    document.getElementById('result').scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert(data.message || '生成链接时出错');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('请求出错，请稍后再试');
            });
        });
    }
    
    // 批量链接表单提交
    if (batchForm) {
        batchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const urls = document.getElementById('batchUrls').value.split('\n').filter(url => url.trim());
            const password = document.getElementById('batchPassword').value;
            const restriction = document.querySelector('input[name="batchRestriction"]:checked').value;
            
            if (urls.length === 0) {
                alert('请输入至少一个URL');
                return;
            }
            
            if (urls.length > 50) {
                alert('批量转换每次最多支持50个链接');
                return;
            }
            
            // 发送AJAX请求
            fetch('api/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    batch: true,
                    urls: urls,
                    password: password,
                    restriction: restriction
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 清空结果表格
                    const tbody = document.getElementById('batchResultsBody');
                    tbody.innerHTML = '';
                    
                    // 填充结果
                    data.results.forEach(result => {
                        const row = document.createElement('tr');
                        
                        if (result.success) {
                            const restrictionText = result.restriction === 'any_device' ? 
                                '<span class="tag tag-success">全设备</span>' : 
                                '<span class="tag tag-primary">仅移动</span>';
                                
                            row.innerHTML = `
                                <td class="url-cell">${result.originalUrl}</td>
                                <td class="url-cell"><span class="short-url-text">${result.shortUrl}</span> ${restrictionText}</td>
                                <td><button class="copy-cell-btn" data-url="${result.shortUrl}">复制</button></td>
                            `;
                        } else {
                            row.innerHTML = `
                                <td class="url-cell">${result.originalUrl}</td>
                                <td colspan="2" class="error-cell">${result.message}</td>
                            `;
                        }
                        
                        tbody.appendChild(row);
                    });
                    
                    // 为新添加的复制按钮添加事件
                    document.querySelectorAll('.copy-cell-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            copyToClipboard(this.getAttribute('data-url'), this);
                        });
                    });
                    
                    // 显示结果区域
                    document.getElementById('batchResult').style.display = 'block';
                    document.getElementById('batchResult').scrollIntoView({ behavior: 'smooth' });
                    
                    // 设置"复制所有"按钮事件
                    document.getElementById('copyAllBtn').onclick = function() {
                        const allUrls = data.results
                            .filter(r => r.success)
                            .map(r => r.shortUrl)
                            .join('\n');
                        
                        copyToClipboard(allUrls, this);
                    };
                } else {
                    alert(data.message || '批量生成链接时出错');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('请求出错，请稍后再试');
            });
        });
    }
    
    // 复制到剪贴板功能
    const copyBtn = document.getElementById('copyBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const url = document.getElementById('generatedUrl').value;
            copyToClipboard(url, this);
        });
    }
    
    // 通用复制到剪贴板函数
    function copyToClipboard(text, button) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        
        // 视觉反馈
        const originalText = button.textContent;
        button.textContent = '已复制!';
        button.style.backgroundColor = '#27ae60';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
        }, 2000);
    }
});
