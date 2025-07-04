/**
 * 管理员界面JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // 获取DOM元素
    const sidebarToggleBtn = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const adminContent = document.querySelector('.admin-content');
    
    // 通知提示函数
    window.showNotification = function(message, type = 'info') {
        const notification = document.getElementById('notification') || createNotificationElement();
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
    };
    
    // 创建通知元素
    function createNotificationElement() {
        const notification = document.createElement('div');
        notification.id = 'notification';
        notification.className = 'notification';
        notification.style.display = 'none';
        document.body.appendChild(notification);
        return notification;
    }
    
    // 检查屏幕宽度并适配布局
    function checkScreenWidth() {
        const sidebarToggleBtn = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const adminContent = document.querySelector('.admin-content');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (!sidebar || !adminContent) return;
        
        if (window.innerWidth <= 992) {
            // 移动端布局
            if (sidebarToggleBtn) sidebarToggleBtn.style.display = 'block';
            sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            adminContent.style.marginLeft = '0';
            adminContent.style.width = '100%';
        } else {
            // 桌面端布局
            if (sidebarToggleBtn) sidebarToggleBtn.style.display = 'none';
            adminContent.style.marginLeft = 'var(--admin-sidebar-width)';
            adminContent.style.width = 'calc(100% - var(--admin-sidebar-width))';
        }
    }
    
    // 立即执行一次检查
    checkScreenWidth();
    
    // 窗口大小变化时重新检查
    window.addEventListener('resize', checkScreenWidth);
    
    // 添加移动端菜单切换
    if (sidebarToggleBtn && sidebar && overlay) {
        // 切换侧边栏显示状态
        sidebarToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        // 点击遮罩层关闭侧边栏
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // 修复dashboard页面的工具卡片显示问题
    const toolsGrid = document.querySelector('.tools-grid');
    if (toolsGrid) {
        const emptyGrid = toolsGrid.children.length === 0;
        if (emptyGrid) {
            const toolCards = document.querySelectorAll('.tool-card');
            toolCards.forEach(card => {
                // 避免重复添加
                if (card.parentNode !== toolsGrid) {
                    toolsGrid.appendChild(card);
                }
            });
        }
    }
    
    // 增加初始化完成标记
    console.log('Admin JS initialized');
});
