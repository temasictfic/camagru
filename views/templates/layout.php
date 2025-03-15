<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Camagru' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
    <?php if (isset($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <div class="navbar">
                    <div class="logo">
                        <a href="/">
                            <h1>Camagru</h1>
                        </a>
                    </div>
                    <div class="menu">
                        <ul>
                            <li><a href="/gallery">Gallery</a></li>
                            <?php if (isLoggedIn()): ?>
                                <li><a href="/editor">Editor</a></li>
                                <li><a href="/profile">Profile</a></li>
                                <li><a href="/logout">Logout</a></li>
                            <?php else: ?>
                                <li><a href="/login">Login</a></li>
                                <li><a href="/register">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="hamburger">
                        <span class="bar"></span>
                        <span class="bar"></span>
                        <span class="bar"></span>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main>
        <div class="container">
            <?php
            $flash = getFlash();
            if ($flash): 
            ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['message'] ?>
                </div>
            <?php endif; ?>
            
            <?= $content ?>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Camagru. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="/js/script.js"></script>
    <?php if (isset($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>