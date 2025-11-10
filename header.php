<!DOCTYPE html>
<nav class="main-nav">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="/">Last War Server 1586</a>
        </div>
        <div class="nav-links">
            <a href="alliance-profile.php">🛡️ Alliance Profile</a>
            <a href="admin/dashboard.php">🔐 Admin Login</a>
        </div>
    </div>
</nav>

<style>
    .main-nav {
        background: rgba(30, 30, 40, 0.95);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 15px 0;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .nav-brand a {
        color: white;
        font-size: 20px;
        font-weight: 700;
        text-decoration: none;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .nav-links {
        display: flex;
        gap: 20px;
    }

    .nav-links a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s;
    }

    .nav-links a:hover {
        color: #667eea;
    }
</style>
