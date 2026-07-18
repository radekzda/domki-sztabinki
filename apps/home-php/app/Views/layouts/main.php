<?php

declare(strict_types=1);

/**
 * @var string $content
 * @var string|null $title
 * @var string|null $metaDescription
 * @var string|null $canonicalUrl
 * @var string|null $metaRobots
 * @var string|null $ogImage
 */

$isHomePage = ($title ?? '') === 'Strona główna';

$pageTitle = $isHomePage
    ? 'Domki Sztabinki — domki nad jeziorem koło Sejn'
    : (
        isset($title)
        && is_string($title)
        && $title !== ''
            ? $title . ' — Domki Sztabinki'
            : 'Domki Sztabinki'
    );

$defaultDescription = 'Domki Sztabinki — komfortowy wypoczynek nad jeziorem w okolicy Sejn. Spokojne miejsce, dostęp do jeziora i sprzętu wodnego.';

$pageDescription = isset($metaDescription)
    && is_string($metaDescription)
    && trim($metaDescription) !== ''
        ? trim($metaDescription)
        : $defaultDescription;

$requestUri = (string) (
    $_SERVER['REQUEST_URI']
    ?? '/'
);

$requestPath = parse_url(
    $requestUri,
    PHP_URL_PATH
);

if (
    !is_string($requestPath)
    || $requestPath === ''
) {
    $requestPath = '/';
}

$isPrivatePage =
    $requestPath === '/logowanie'
    || $requestPath === '/admin'
    || str_starts_with(
        $requestPath,
        '/admin/'
    );

$scheme = (
    isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== ''
    && $_SERVER['HTTPS'] !== 'off'
)
    ? 'https'
    : 'http';

$host = trim(
    (string) (
        $_SERVER['HTTP_HOST']
        ?? ''
    )
);

if ($host === '') {
    $host = 'domkisztabinki.pl';
}

$generatedCanonicalUrl =
    $scheme
    . '://'
    . $host
    . $requestPath;

$pageCanonicalUrl = isset($canonicalUrl)
    && is_string($canonicalUrl)
    && trim($canonicalUrl) !== ''
        ? trim($canonicalUrl)
        : $generatedCanonicalUrl;

$pageRobots = isset($metaRobots)
    && is_string($metaRobots)
    && trim($metaRobots) !== ''
        ? trim($metaRobots)
        : (
            $isPrivatePage
                ? 'noindex, nofollow, noarchive'
                : 'index, follow'
        );

$pageOgImage = isset($ogImage)
    && is_string($ogImage)
    && trim($ogImage) !== ''
        ? trim($ogImage)
        : null;

$isLoggedIn =
    class_exists('Auth')
    && Auth::check();

$adminEmail = $isLoggedIn
    ? Auth::adminEmail()
    : '';
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= htmlspecialchars(
        $pageTitle,
        ENT_QUOTES,
        'UTF-8'
    ) ?></title>

    <meta
        name="description"
        content="<?= htmlspecialchars(
            $pageDescription,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <meta
        name="robots"
        content="<?= htmlspecialchars(
            $pageRobots,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <?php if (!$isPrivatePage): ?>
        <link
            rel="canonical"
            href="<?= htmlspecialchars(
                $pageCanonicalUrl,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <meta
            property="og:type"
            content="website"
        >

        <meta
            property="og:locale"
            content="pl_PL"
        >

        <meta
            property="og:site_name"
            content="Domki Sztabinki"
        >

        <meta
            property="og:title"
            content="<?= htmlspecialchars(
                $pageTitle,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <meta
            property="og:description"
            content="<?= htmlspecialchars(
                $pageDescription,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <meta
            property="og:url"
            content="<?= htmlspecialchars(
                $pageCanonicalUrl,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <?php if ($pageOgImage !== null): ?>
            <meta
                property="og:image"
                content="<?= htmlspecialchars(
                    $pageOgImage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
        <?php endif; ?>

        <meta
            name="twitter:card"
            content="summary"
        >

        <meta
            name="twitter:title"
            content="<?= htmlspecialchars(
                $pageTitle,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <meta
            name="twitter:description"
            content="<?= htmlspecialchars(
                $pageDescription,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    <?php endif; ?>

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>

<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a
                class="site-logo"
                href="/"
            >
                Domki Sztabinki
            </a>

            <nav
                class="site-nav"
                aria-label="Nawigacja główna"
            >
                <?php /* M13.93.25 — ukryj na stronie głównej */ ?>
                <?php if (($title ?? '') !== 'Strona główna'): ?>
                    <a href="/">
                        Strona główna
                    </a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <a href="/admin">
                        Panel
                    </a>

                    <span class="site-nav__user">
                        <?= htmlspecialchars(
                            $adminEmail,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <form
                        class="site-nav__form"
                        method="post"
                        action="/wyloguj"
                    >
                        <?= csrfField() ?>

                        <button type="submit">
                            Wyloguj
                        </button>
                    </form>
                <?php else: ?>
                    <?php /* M13.93.25 — ukryj na stronie głównej */ ?>
                    <?php if (($title ?? '') !== 'Strona główna'): ?>
                        <a href="/logowanie">
                            Logowanie
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>
                © <?= date('Y') ?> Domki Sztabinki
            </p>

            <p>
                <a href="/polityka-prywatnosci">
                    Polityka prywatności
                </a>
            </p>
        </div>
    </footer>
</body>
</html>
