<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var int $id
 * @var array<string, string> $form
 * @var array<string, string> $errors
 * @var array<int, array{
 *     id: int,
 *     name: string,
 *     short_name: string|null,
 *     max_guests: int,
 *     bedrooms: int,
 *     bathrooms: int,
 *     price_per_night: int,
 *     price_one_night: int,
 *     price_two_nights: int,
 *     price_three_nights: int,
 *     price_four_nights: int,
 *     price_five_nights: int,
 *     price_six_nights: int,
 *     price_seven_plus_nights: int,
 *     is_active: int,
 *     sort_order: int,
 *     created_at: string
 * }> $cabins
 * @var string|null $databaseMessage
 * @var bool $canSave
 * @var int|null $calculatedNights
 * @var int|null $calculatedTotalPrice
 */
?>
<section class="page-section">
    <div class="container">
        <div class="admin-shell">
            <?php View::partial('partials/admin_sidebar', ['active' => 'reservations']); ?>

            <div class="admin-content">
                <div class="panel">
                    <div class="page-header">
                        <div>
                            <p class="eyebrow">Rezerwacje</p>

                            <h1>Edytuj rezerwację</h1>

                            <p>
                                Zmień termin, domek, dane gościa, status oraz płatność.
                                System ponownie sprawdzi kolizje terminów.
                            </p>
                        </div>

                        <div class="page-header__actions">
                            <a
                                class="button button--secondary"
                                href="/admin/rezerwacje/pokaz?id=<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Szczegóły
                            </a>

                            <a class="button button--secondary" href="/admin/rezerwacje">
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

                    <?php if ($calculatedNights !== null && $calculatedTotalPrice !== null): ?>
                        <div class="alert alert--success">
                            Wyliczenie:
                            <?= htmlspecialchars((string) $calculatedNights, ENT_QUOTES, 'UTF-8') ?>
                            noc. /
                            <?= htmlspecialchars(number_format($calculatedTotalPrice, 0, ',', ' '), ENT_QUOTES, 'UTF-8') ?>
                            zł
                        </div>
                    <?php endif; ?>

                    <?php
                    View::partial('partials/reservation_form', [
                        'form' => $form,
                        'errors' => $errors,
                        'cabins' => $cabins,
                        'canSave' => $canSave,
                        'action' => '/admin/rezerwacje/edytuj?id=' . $id,
                        'submitLabel' => 'Zapisz zmiany',
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>