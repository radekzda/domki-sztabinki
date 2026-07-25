<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $token
 * @var array<string, string> $errors
 * @var bool $invalidToken
 */

$loginPath = applicationPath('/logowanie');
$forgotPasswordPath = applicationPath(
    '/nie-pamietam-hasla'
);
$resetPasswordPath = applicationPath(
    '/odzyskaj-haslo'
);
?>
<section class="page-section">
    <div class="container container--narrow">
        <div class="panel">
            <p class="eyebrow">
                Bezpieczeństwo konta
            </p>

            <h1>Ustaw nowe hasło</h1>

            <?php if ($invalidToken): ?>
                <div class="alert alert--danger">
                    Link jest nieprawidłowy, wygasł albo
                    został już wykorzystany.
                </div>

                <div class="form-actions">
                    <a
                        class="button button--primary"
                        href="<?= htmlspecialchars(
                            $forgotPasswordPath,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        Wyślij nowy link
                    </a>

                    <a
                        class="button button--secondary"
                        href="<?= htmlspecialchars(
                            $loginPath,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        Wróć do logowania
                    </a>
                </div>
            <?php else: ?>
                <p>
                    Nowe hasło musi mieć co najmniej
                    12 znaków.
                </p>

                <form
                    class="form"
                    method="post"
                    action="<?= htmlspecialchars(
                        $resetPasswordPath,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    <?= csrfField() ?>

                    <input
                        name="token"
                        type="hidden"
                        value="<?= htmlspecialchars(
                            $token,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <div class="form-field">
                        <label for="password">
                            Nowe hasło
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            minlength="12"
                            required
                        >

                        <?php if (
                            isset($errors['password'])
                        ): ?>
                            <p class="form-error">
                                <?= htmlspecialchars(
                                    $errors['password'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="password_confirmation">
                            Powtórz nowe hasło
                        </label>

                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            minlength="12"
                            required
                        >

                        <?php if (
                            isset(
                                $errors[
                                    'password_confirmation'
                                ]
                            )
                        ): ?>
                            <p class="form-error">
                                <?= htmlspecialchars(
                                    $errors[
                                        'password_confirmation'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button
                            class="button button--primary"
                            type="submit"
                        >
                            Ustaw nowe hasło
                        </button>

                        <a
                            class="button button--secondary"
                            href="<?= htmlspecialchars(
                            $loginPath,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        >
                            Anuluj
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
