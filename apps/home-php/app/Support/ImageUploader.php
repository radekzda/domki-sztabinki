<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class ImageUploader
{
    public const MAX_SIZE_BYTES = 8 * 1024 * 1024;

    /**
     * @return array{
     *     file_name: string,
     *     target_path: string,
     *     public_path: string,
     *     mime: string,
     *     extension: string,
     *     size: int
     * }
     */
    public static function upload(array $file, string $uploadDirectory, string $publicDirectory, string $prefix): array
    {
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Nie udało się wysłać pliku. Kod błędu: ' . $uploadError . '.');
        }

        $temporaryPath = isset($file['tmp_name']) && is_string($file['tmp_name'])
            ? $file['tmp_name']
            : '';

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('Nieprawidłowy plik uploadu.');
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0) {
            throw new RuntimeException('Plik jest pusty.');
        }

        if ($size > self::MAX_SIZE_BYTES) {
            throw new RuntimeException('Plik jest za duży. Maksymalny rozmiar zdjęcia to 8 MB.');
        }

        $originalName = isset($file['name']) && is_string($file['name'])
            ? $file['name']
            : '';

        $originalExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($originalExtension === 'jpeg') {
            $originalExtension = 'jpg';
        }

        if (!in_array($originalExtension, ['jpg', 'jfif', 'png', 'webp'], true)) {
            throw new RuntimeException('Dozwolone formaty zdjęć: JPG, JFIF, PNG i WEBP.');
        }

        $imageSize = @getimagesize($temporaryPath);

        if ($imageSize === false || !isset($imageSize['mime'])) {
            throw new RuntimeException('Przesłany plik nie jest prawidłowym zdjęciem.');
        }

        $mime = (string) $imageSize['mime'];

        $extension = match ($mime) {
            'image/jpeg' => $originalExtension === 'jfif' ? 'jfif' : 'jpg',
            'image/jfif' => 'jfif',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => '',
        };

        if ($extension === '') {
            throw new RuntimeException('Dozwolone formaty zdjęć: JPG, JFIF, PNG i WEBP.');
        }

        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('Nie udało się utworzyć katalogu uploadu.');
        }

        $safePrefix = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $prefix) ?? 'image');
        $safePrefix = trim($safePrefix, '-_');

        if ($safePrefix === '') {
            $safePrefix = 'image';
        }

        $fileName = $safePrefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $targetPath = rtrim($uploadDirectory, '/\\') . DIRECTORY_SEPARATOR . $fileName;
        $publicPath = rtrim($publicDirectory, '/') . '/' . $fileName;

        if (!move_uploaded_file($temporaryPath, $targetPath)) {
            throw new RuntimeException('Nie udało się zapisać przesłanego pliku.');
        }

        return [
            'file_name' => $fileName,
            'target_path' => $targetPath,
            'public_path' => $publicPath,
            'mime' => $mime,
            'extension' => $extension,
            'size' => $size,
        ];
    }
}
