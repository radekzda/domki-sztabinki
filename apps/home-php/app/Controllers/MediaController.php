<?php

declare(strict_types=1);

final class MediaController
{
    public static function index(): void
    {
        $successMessage = null;

        if (isset($_GET['uploaded'])) {
            $successMessage = 'Zdjęcie zostało dodane.';
        } elseif (isset($_GET['main_changed'])) {
            $successMessage = 'Zdjęcie główne zostało zmienione.';
        } elseif (isset($_GET['deleted'])) {
            $successMessage = 'Zdjęcie zostało usunięte.';
        }

        try {
            $images = SiteImageRepository::all();
            $databaseMessage = null;
        } catch (Throwable $exception) {
            $images = [];
            $databaseMessage = 'Nie udało się pobrać zdjęć strony: ' . AppErrorHandler::safeMessage($exception);
        }

        Response::html(View::render('pages/admin_media', [
            'title' => 'Media i galeria',
            'images' => $images,
            'typeLabels' => SiteImageRepository::typeLabels(),
            'databaseMessage' => $databaseMessage,
            'successMessage' => $successMessage,
            'errorMessage' => null,
        ]));
    }

    public static function handle(): void
    {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

        try {
            if ($action === 'set_main') {
                self::setMain();
                return;
            }

            if ($action === 'delete') {
                self::delete();
                return;
            }

            if ($action === 'upload') {
                self::upload();
                return;
            }

            throw new RuntimeException('Nieznana akcja.');
        } catch (Throwable $exception) {
            self::renderError(AppErrorHandler::safeMessage($exception));
        }
    }

    private static function setMain(): void
    {
        $id = isset($_POST['id']) && is_numeric($_POST['id'])
            ? (int) $_POST['id']
            : 0;

        if ($id > 0) {
            SiteImageRepository::setMain($id);
        }

        Response::redirect('/admin/media?main_changed=1');
    }

    private static function delete(): void
    {
        $id = isset($_POST['id']) && is_numeric($_POST['id'])
            ? (int) $_POST['id']
            : 0;

        if ($id > 0) {
            $imageUrl = SiteImageRepository::delete($id);

            if (is_string($imageUrl) && str_starts_with($imageUrl, '/uploads/site/')) {
                $filePath = dirname(__DIR__) . '/../public' . $imageUrl;

                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
        }

        Response::redirect('/admin/media?deleted=1');
    }

    private static function upload(): void
    {
        if (!isset($_FILES['image_file']) || !is_array($_FILES['image_file'])) {
            self::renderWithError('Nie wybrano pliku zdjęcia.');

            return;
        }

        $imageType = SiteImageRepository::normalizeType(isset($_POST['image_type']) ? (string) $_POST['image_type'] : 'GALLERY');
        $uploadDirectory = dirname(__DIR__) . '/../public/uploads/site';

        try {
            $uploadedImage = \App\Support\ImageUploader::upload(
                $_FILES['image_file'],
                $uploadDirectory,
                '/uploads/site',
                'site-' . strtolower($imageType)
            );
        } catch (RuntimeException $exception) {
            self::renderWithError(AppErrorHandler::safeMessage($exception));

            return;
        }

        $altText = trim((string) ($_POST['alt_text'] ?? ''));
        $isMain = (int) ($_POST['is_main'] ?? 0) === 1;
        $sortOrder = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);

        if (!is_int($sortOrder)) {
            $sortOrder = 0;
        }

        SiteImageRepository::create([
            'image_url' => $uploadedImage['public_path'],
            'alt_text' => $altText,
            'image_type' => $imageType,
            'is_main' => $isMain ? 1 : 0,
            'sort_order' => $sortOrder,
        ]);

        Response::redirect('/admin/media?uploaded=1');
    }

    private static function renderError(string $message): void
    {
        try {
            $images = SiteImageRepository::all();
            $databaseMessage = null;
        } catch (Throwable $databaseException) {
            $images = [];
            $databaseMessage = 'Nie udało się pobrać zdjęć strony: ' . AppErrorHandler::safeMessage($databaseException);
        }

        Response::html(View::render('pages/admin_media', [
            'title' => 'Media i galeria',
            'images' => $images,
            'typeLabels' => SiteImageRepository::typeLabels(),
            'databaseMessage' => $databaseMessage,
            'successMessage' => null,
            'errorMessage' => $message,
        ]), 422);
    }
}