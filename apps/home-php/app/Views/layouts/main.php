<?php

declare(strict_types=1);

/**
 * @var string $content
 * @var string|null $title
 */

$pageTitle = isset($title) && is_string($title) && $title !== ''
    ? $title . ' — Domki Sztabinki'
    : 'Domki Sztabinki';

$isLoggedIn = class_exists('Auth') && Auth::check();
$adminEmail = $isLoggedIn ? Auth::adminEmail() : '';
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

                <?php if ($isLoggedIn): ?>
                    <a href="/admin">Panel</a>
                    <span class="site-nav__user">
                        <?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') ?>
                    </span>

                    <form class="site-nav__form" method="post" action="/wyloguj">
                        <button type="submit">
                            Wyloguj
                        </button>
                    </form>
                <?php else: ?>
                    <a href="/logowanie">Logowanie</a>
                <?php endif; ?>
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