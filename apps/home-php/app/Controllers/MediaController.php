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
            $databaseMessage = 'Nie udało się pobrać zdjęć strony: ' . $exception->getMessage();
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
            self::renderError($exception->getMessage());
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
            throw new RuntimeException('Nie wybrano pliku zdjęcia.');
        }

        $file = $_FILES['image_file'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Nie udało się przesłać zdjęcia.');
        }

        $temporaryPath = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('Nieprawidłowy plik tymczasowy.');
        }

        $fileSize = isset($file['size']) ? (int) $file['size'] : 0;

        if ($fileSize < 1) {
            throw new RuntimeException('Plik jest pusty.');
        }

        if ($fileSize > 8 * 1024 * 1024) {
            throw new RuntimeException('Zdjęcie jest za duże. Maksymalny rozmiar to 8 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($temporaryPath);

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => '',
        };

        if ($extension === '') {
            throw new RuntimeException('Nieobsługiwany format zdjęcia. Użyj JPG, PNG albo WEBP.');
        }

        $uploadDirectory = dirname(__DIR__) . '/../public/uploads/site';

        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Nie udało się utworzyć katalogu uploadu.');
        }

        $imageType = SiteImageRepository::normalizeType(isset($_POST['image_type']) ? (string) $_POST['image_type'] : 'GALLERY');
        $sortOrder = isset($_POST['sort_order']) && is_numeric($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        $altText = isset($_POST['alt_text']) ? trim((string) $_POST['alt_text']) : '';
        $isMain = isset($_POST['is_main']) && (string) $_POST['is_main'] === '1' ? 1 : 0;

        $fileName = 'site-' . strtolower($imageType) . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDirectory . '/' . $fileName;
        $publicPath = '/uploads/site/' . $fileName;

        if (!move_uploaded_file($temporaryPath, $targetPath)) {
            throw new RuntimeException('Nie udało się zapisać zdjęcia na serwerze.');
        }

        SiteImageRepository::create([
            'image_url' => $publicPath,
            'alt_text' => $altText !== '' ? $altText : null,
            'image_type' => $imageType,
            'sort_order' => $sortOrder,
            'is_main' => $isMain,
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
            $databaseMessage = 'Nie udało się pobrać zdjęć strony: ' . $databaseException->getMessage();
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