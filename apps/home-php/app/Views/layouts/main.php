<?php

declare(strict_types=1);

/**
 * @var string $content
 * @var string|null $title
 */

$pageTitle = isset($title) && is_string($title) && $title !== ''
    ? $title . ' — Domki Sztabinki'
    : 'Domki Sztabinki';
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="Domki Sztabinki — wypoczynek nad jeziorem w okolicy Sejn.">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="site-logo" href="/">
                Domki Sztabinki
            </a>

            <nav class="site-nav" aria-label="Nawigacja główna">
                <a href="/">Strona główna</a>
                <a href="/admin">Panel</a>
            </nav>
        </div>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>© <?= date('Y') ?> Domki Sztabinki</p>
        </div>
    </footer>
</body>
</html>