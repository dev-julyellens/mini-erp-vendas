<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\FileUploadValidator;

final class AvatarStorageService
{
    private const MAX_BYTES = 2_097_152;

    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function storageDir(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'avatars';
    }

    /**
     * @return string caminho relativo salvo no banco (ex: avatars/u_1_abc.jpg)
     */
    public function store(int $userId, string $fieldName = 'avatar'): string
    {
        $file = FileUploadValidator::validateUploadedFile(
            $fieldName,
            self::MAX_BYTES,
            self::ALLOWED_EXTENSIONS,
            self::ALLOWED_MIMES
        );

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension === 'jpeg')
        {
            $extension = 'jpg';
        }

        $dir = $this->storageDir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir))
        {
            throw new \RuntimeException('Não foi possível criar diretório de avatares.');
        }

        $filename = sprintf('u_%d_%s.%s', $userId, bin2hex(random_bytes(8)), $extension);
        $absolute = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $absolute))
        {
            throw new ValidationException(['avatar' => 'Não foi possível salvar a imagem.']);
        }

        return 'avatars/' . $filename;
    }

    public function absolutePath(?string $relativePath): ?string
    {
        if ($relativePath === null || trim($relativePath) === '')
        {
            return null;
        }

        $relative = str_replace(['\\', '..'], ['/', ''], trim($relativePath));
        if (!str_starts_with($relative, 'avatars/'))
        {
            return null;
        }

        $base = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage');
        if ($base === false)
        {
            return null;
        }

        $full = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, substr($relative, strlen('avatars/')));
        $real = realpath($full);
        if ($real === false || !is_file($real))
        {
            return null;
        }

        if (!str_starts_with($real, $base . DIRECTORY_SEPARATOR . 'avatars'))
        {
            return null;
        }

        return $real;
    }

    public function deleteIfExists(?string $relativePath): void
    {
        $absolute = $this->absolutePath($relativePath);
        if ($absolute !== null && is_file($absolute))
        {
            @unlink($absolute);
        }
    }
}
