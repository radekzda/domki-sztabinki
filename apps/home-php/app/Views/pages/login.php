<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $email
 * @var string|null $error
 * @var bool $isAuthConfigured
 */
?>
<section class="page-section">
    <div class="container container--narrow">
        <div class="panel">
            <p class="eyebrow">Logowanie</p>

            <h1>Panel administratora</h1>

            <p>
                Zaloguj się, aby przejść do panelu Domki Sztabinki PMS.
            </p>

            <?php if (!$isAuthConfigured): ?>
                <div class="alert alert--warning">
                    Logowanie nie jest jeszcze skonfigurowane. Ustaw
                    <strong>ADMIN_EMAIL</strong> oraz <strong>ADMIN_PASSWORD</strong>
                    w lokalnym pliku <strong>.env</strong>.
                </div>
            <?php endif; ?>

            <?php if (isset($error) && is_string($error) && $error !== ''): ?>
                <div class="alert alert--danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form class="form" method="post" action="/logowanie">
    <?= csrfField() ?>
                <div class="form-field">
                    <label for="email">Adres e-mail</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="username"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-field">
                    <label for="password">Hasło</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button class="button button--primary" type="submit">
                        Zaloguj
                    </button>

                    <a class="button button--secondary" href="/">
                        Wróć na stronę
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>