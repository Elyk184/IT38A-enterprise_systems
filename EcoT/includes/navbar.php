<nav class="navbar">
    <div class="navbar-logo">
        <i class="fas fa-leaf"></i> EcoT
    </div>
    <ul class="navbar-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#contact">Contact</a></li>
    </ul>
    <div class="navbar-actions">
        <a href="pages/login.php" class="navbar-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
    </div>
</nav>
<style>
    .navbar {
        width: 100vw;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0, 195, 195, 0.07);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 32px;
        height: 64px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 10;
    }
    .navbar-logo {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d7d46;
        display: flex;
        align-items: center;
        gap: 8px;
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
    }
    .navbar-links li a:hover {
        color: #2d7d46;
        text-decoration: underline;
    }
    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 12px;
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
    }
    .navbar-btn:hover {
        background: #36b0c2;
    }
    @media (max-width: 700px) {
        .navbar {
            flex-direction: column;
            height: auto;
            padding: 0 8px;
        }
        .navbar-links {
            gap: 12px;
        }
    }
</style>
