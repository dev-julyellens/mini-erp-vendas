<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Classes e helpers para botões de ação padronizados.
 *
 * Variantes: primary, secondary, outline, destructive, warning, ghost
 * Tamanhos: sm, md, lg
 */
final class ActionButton
{
    public const VARIANTS = ['primary', 'secondary', 'outline', 'destructive', 'warning', 'ghost'];

    public const SIZES = ['sm', 'md', 'lg'];

    public static function classes(string $variant = 'secondary', string $size = 'md', string $extra = ''): string
    {
        $variant = in_array($variant, self::VARIANTS, true) ? $variant : 'secondary';
        $size = in_array($size, self::SIZES, true) ? $size : 'md';

        $map = [
            'primary' => 'btn btn-primary',
            'secondary' => 'btn btn-secondary',
            'outline' => 'btn btn-outline',
            'destructive' => 'btn btn-destructive',
            'warning' => 'btn btn-warning',
            'ghost' => 'btn btn-ghost',
        ];

        $classes = [$map[$variant]];
        if ($size !== 'md') {
            $classes[] = 'btn-' . $size;
        }
        if ($extra !== '') {
            $classes[] = trim($extra);
        }

        return implode(' ', $classes);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public static function link(string $href, string $label, string $variant = 'secondary', string $size = 'md', array $attrs = []): string
    {
        $class = self::classes($variant, $size, (string) ($attrs['class'] ?? ''));
        unset($attrs['class']);
        $attrStr = self::attrsToString($attrs);

        return sprintf(
            '<a class="%s" href="%s"%s>%s</a>',
            htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($href, ENT_QUOTES, 'UTF-8'),
            $attrStr,
            $label
        );
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public static function button(string $label, string $variant = 'primary', string $size = 'md', string $type = 'submit', array $attrs = []): string
    {
        $class = self::classes($variant, $size, (string) ($attrs['class'] ?? ''));
        unset($attrs['class']);
        $attrStr = self::attrsToString($attrs);

        return sprintf(
            '<button type="%s" class="%s"%s>%s</button>',
            htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
            $attrStr,
            $label
        );
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private static function attrsToString(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $out .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
                continue;
            }
            $out .= sprintf(
                ' %s="%s"',
                htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
            );
        }

        return $out;
    }
}
