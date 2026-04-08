<nav class="navbar">
    <div class="navbar-logo">
        <i class="fas fa-leaf"></i> EcoT
    </div>
    <div class="navbar-spacer"></div>
    <div class="navbar-actions">
        <ul class="navbar-links">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="#about"><i class="fas fa-info-circle"></i> About</a></li>
            <li><a href="#contact"><i class="fas fa-envelope"></i> Contact</a></li>
        </ul>
        <a href="pages/login.php" class="navbar-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
    </div>
</nav>
<style>
    .navbar {
        width: 100vw;
        left: 0;
        right: 0;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0, 195, 195, 0.07);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 0;
        height: 64px;
        position: fixed;
        top: 0;
        z-index: 10;
    }
    .navbar-logo {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d7d46;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-left: 32px;
    }
    .navbar-spacer {
        flex: 1;
    }
    .navbar-links {
        list-style: none;
        display: flex;
        gap: 28px;
        margin: 0;
        padding: 0;
    }
    .navbar-links li a {
        color: #42c7d9;
        text-decoration: none;
        font-weight: 500;
        font-size: 1rem;
        transition: color 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 8px;
        height: 64px;
        line-height: 64px;
    }
    .navbar-links li a:hover {
        color: #2d7d46;
        text-decoration: underline;
        background: rgba(66, 199, 217, 0.07);
    }
    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 24px;
        padding-right: 32px;
    }
    .navbar-btn {
        background: #42c7d9;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 22px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(66, 199, 217, 0.08);
        height: 40px;
        margin-top: 12px;
        margin-bottom: 12px;
    }
    .navbar-btn:hover {
        background: #36b0c2;
    }
    @media (max-width: 700px) {
        .navbar {
            flex-direction: column;
            height: auto;
            padding: 0 0;
        }
        .navbar-logo, .navbar-actions {
            padding-left: 8px;
            padding-right: 8px;
        }
        .navbar-links {
            gap: 12px;
        }
    }
</style>
