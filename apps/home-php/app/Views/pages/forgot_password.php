<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $email
 * @var array<string, string> $errors
 * @var bool $requestSent
 */
?>
<section class="page-section">
    <div class="container container--narrow">
        <div class="panel">
            <p class="eyebrow">
                Bezpieczeństwo konta
            </p>

            <h1>Odzyskiwanie hasła</h1>

            <?php if ($requestSent): ?>
                <div class="alert alert--success">
                    Jeżeli aktywne konto z podanym adresem
                    e-mail istnieje, wysłaliśmy wiadomość
                    z jednorazowym linkiem do ustawienia
                    nowego hasła.
                </div>

                <p>
                    Link jest ważny przez
                    <?= PasswordResetService::ttlMinutes() ?>
                    minut. Sprawdź również folder Spam.
                </p>

                <div class="form-actions">
                    <a
                        class="button button--primary"
                        href="/logowanie"
                    >
                        Wróć do logowania
                    </a>
                </div>
            <?php else: ?>
                <p>
                    Podaj adres e-mail przypisany do konta
                    użytkownika panelu.
                </p>

                <form
                    class="form"
                    method="post"
                    action="/nie-pamietam-hasla"
                >
                    <?= csrfField() ?>

                    <div class="form-field">
                        <label for="email">
                            Adres e-mail
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            value="<?= htmlspecialchars(
                                $email,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >

                        <?php if (
                            isset($errors['email'])
                        ): ?>
                            <p class="form-error">
                                <?= htmlspecialchars(
                                    $errors['email'],
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
                            Wyślij link
                        </button>

                        <a
                            class="button button--secondary"
                            href="/logowanie"
                        >
                            Anuluj
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
