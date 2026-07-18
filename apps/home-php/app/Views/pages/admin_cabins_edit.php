<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var int $id
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var string|null $databaseMessage
 * @var bool $canSave
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'cabins']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Domki</p>

                            <h1>Edytuj domek</h1>

                            <p>
                                Zmień dane domku, ceny, widoczność i kolejność wyświetlania.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <?php if (
                                trim(
                                    (string) (
                                        $form['ical_url']
                                        ?? ''
                                    )
                                ) !== ''
                            ): ?>
                                <a
                                    class="button button--primary"
                                    href="/admin/domki/ical-podglad?id=<?= htmlspecialchars(
                                        (string) $id,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >
                                    Podgląd iCal
                                </a>
                            <?php endif; ?>

                            <a class="button button--secondary" href="/admin/domki">
                                Wróć do listy
                            </a>
                        </div>
                    </div>

                    <?php if (isset($databaseMessage) && is_string($databaseMessage) && $databaseMessage !== ''): ?>
                        <div class="alert alert--warning">
                            <?= htmlspecialchars($databaseMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errors !== []): ?>
                        <div class="alert alert--danger">
                            Popraw błędy w formularzu.
                        </div>
                    <?php endif; ?>

                    <?php
                    $exportToken = isset(
                        $icalExportToken
                    ) && is_string(
                        $icalExportToken
                    )
                        ? trim(
                            $icalExportToken
                        )
                        : '';

                    $icalExportUrl = '';

                    if ($exportToken !== '') {
                        $isHttps = (
                            isset($_SERVER['HTTPS'])
                            && $_SERVER['HTTPS'] !== ''
                            && $_SERVER['HTTPS'] !== 'off'
                        );

                        $scheme = $isHttps
                            ? 'https'
                            : 'http';

                        $host = (string) (
                            $_SERVER['HTTP_HOST']
                            ?? ''
                        );

                        if ($host !== '') {
                            $icalExportUrl =
                                $scheme
                                . '://'
                                . $host
                                . '/ical/domek?id='
                                . rawurlencode(
                                    (string) $id
                                )
                                . '&token='
                                . rawurlencode(
                                    $exportToken
                                );
                        }
                    }
                    ?>

                    <?php if ($icalExportUrl !== ''): ?>
                        <div
                            style="
                                margin-bottom: 20px;
                                padding: 16px;
                                border: 1px solid #e5e7eb;
                                border-radius: 10px;
                                background: #f8fafc;
                            "
                        >
                            <strong>
                                Adres eksportu iCal PMS
                            </strong>

                            <p
                                style="
                                    margin: 6px 0 10px;
                                    color: #6b7280;
                                    font-size: 13px;
                                "
                            >
                                Ten adres będzie można dodać
                                do Booking.com lub innego systemu
                                jako kalendarz importowany.
                                Nie udostępniaj go publicznie.
                            </p>

                            <input
                                type="text"
                                readonly
                                value="<?= htmlspecialchars(
                                    $icalExportUrl,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                style="width: 100%;"
                                onclick="this.select()"
                            >

                            <div style="margin-top: 10px;">
                                <a
                                    class="button button--secondary"
                                    href="<?= htmlspecialchars(
                                        $icalExportUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Otwórz kalendarz iCal
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    View::partial('partials/cabin_form', [
                        'form' => $form,
                        'errors' => $errors,
                        'canSave' => $canSave,
                        'action' => '/admin/domki/edytuj?id=' . $id,
                        'submitLabel' => 'Zapisz zmiany',
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>
