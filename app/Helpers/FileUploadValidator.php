<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\ValidationException;

final class FileUploadValidator
{
    /**
     * @param list<string> $allowedExtensions extensões sem ponto, ex: ['csv', 'sql']
     * @return array{tmp_name: string, name: string, size: int, type: string}
     */
    public static function validateUploadedFile(
        string $fieldName,
        int $maxBytes,
        array $allowedExtensions,
        array $allowedMimeTypes = []
    ): array
    {
        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]))
        {
            throw new ValidationException([$fieldName => 'Nenhum arquivo enviado.']);
        }

        $file = $_FILES[$fieldName];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE)
        {
            throw new ValidationException([$fieldName => 'Nenhum arquivo enviado.']);
        }

        if ($error !== UPLOAD_ERR_OK)
        {
            throw new ValidationException([$fieldName => 'Falha no upload do arquivo.']);
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = basename((string) ($file['name'] ?? ''));
        $size = (int) ($file['size'] ?? 0);
        $mime = (string) ($file['type'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName))
        {
            throw new ValidationException([$fieldName => 'Arquivo inválido.']);
        }

        if ($size <= 0 || $size > $maxBytes)
        {
            throw new ValidationException([$fieldName => 'Tamanho de arquivo inválido.']);
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $normalizedExtensions = array_map(
            static fn(string $ext): string => strtolower(ltrim($ext, '.')),
            $allowedExtensions
        );

        if ($extension === '' || !in_array($extension, $normalizedExtensions, true))
        {
            throw new ValidationException([$fieldName => 'Tipo de arquivo não permitido.']);
        }

        if ($allowedMimeTypes !== [] && $mime !== '' && !in_array($mime, $allowedMimeTypes, true))
        {
            throw new ValidationException([$fieldName => 'Tipo MIME não permitido.']);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected = $finfo !== false ? finfo_file($finfo, $tmpName) : false;
        if ($finfo !== false)
        {
            finfo_close($finfo);
        }

        if (
            $allowedMimeTypes !== []
            && is_string($detected)
            && $detected !== ''
            && !in_array($detected, $allowedMimeTypes, true)
        )
        {
            throw new ValidationException([$fieldName => 'Conteúdo do arquivo não permitido.']);
        }

        return [
            'tmp_name' => $tmpName,
            'name' => $originalName,
            'size' => $size,
            'type' => $mime,
        ];
    }
}
