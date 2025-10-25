<?php
// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>MTC Admin</span>
    </div>
    
    <nav class="sidebar-nav">
        <a href="sales.php" class="nav-item <?php echo ($current_page == 'sales.php') ? 'active' : ''; ?>">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Sales</span>
        </a>
        
        <a href="orders.php" class="nav-item <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>">
            <i class="bi bi-bag-check"></i>
            <span>Orders</span>
        </a>
        
        <a href="product.php" class="nav-item <?php echo ($current_page == 'product.php') ? 'active' : ''; ?>">
            <i class="bi bi-box-seam"></i>
            <span>Products</span>
        </a>
        
        <a href="customization_cms.php" class="nav-item <?php echo ($current_page == 'customization_cms.php') ? 'active' : ''; ?>">
            <i class="bi bi-palette"></i>
            <span>Customization</span>
        </a>
        
        <a href="feedback.php" class="nav-item <?php echo ($current_page == 'feedback.php') ? 'active' : ''; ?>">
            <i class="bi bi-chat-heart"></i>
            <span>Feedback</span>
        </a>
        
        <a href="customers.php" class="nav-item <?php echo ($current_page == 'customers.php') ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            <span>Users</span>
        </a>
        
        <a href="admin_chat.php" class="nav-item <?php echo ($current_page == 'admin_chat.php') ? 'active' : ''; ?>">
            <i class="bi bi-chat-dots"></i>
            <span>Customer Chat</span>
        </a>
        
        <a href="cms.php" class="nav-item <?php echo ($current_page == 'cms.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i>
            <span>CMS</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <button class="logout-btn" id="sidebar-logout-btn" type="button">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </button>
    </div>
</div>

<script>
document.getElementById('sidebar-logout-btn').onclick = function() {
    if (confirm('Sigurado ka bang gusto mong mag-logout?')) {
        fetch('../logout.php', {method: 'POST'})
            .finally(() => {
                alert('Logout successful.');
                window.location.href = 'login.php';
            });
    }
};
</script>
