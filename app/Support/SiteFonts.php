<?php

namespace App\Support;

class SiteFonts
{
    /**
     * Curated Google Fonts available in Admin → Settings.
     *
     * @return array<string, array{label: string, weights: string}>
     */
    public static function catalog(): array
    {
        return [
            'Source Sans 3' => ['label' => 'Source Sans 3 (default body)', 'weights' => '300;400;500;600;700'],
            'Playfair Display' => ['label' => 'Playfair Display (classic headings)', 'weights' => '600;700'],
            'Poppins' => ['label' => 'Poppins (clean, modern)', 'weights' => '400;500;600;700'],
            'Montserrat' => ['label' => 'Montserrat (strong headings)', 'weights' => '400;500;600;700'],
            'Open Sans' => ['label' => 'Open Sans (classic web)', 'weights' => '400;500;600;700'],
            'Lato' => ['label' => 'Lato (friendly, readable)', 'weights' => '400;700'],
            'Nunito' => ['label' => 'Nunito (soft, rounded)', 'weights' => '400;500;600;700'],
            'Quicksand' => ['label' => 'Quicksand (soft, rounded)', 'weights' => '400;500;600;700'],
            'Baloo 2' => ['label' => 'Baloo 2 (playful)', 'weights' => '400;500;600;700'],
            'Fredoka' => ['label' => 'Fredoka (friendly, bold)', 'weights' => '400;500;600;700'],
            'Comic Neue' => ['label' => 'Comic Neue (handwritten style)', 'weights' => '400;700'],
            'Roboto' => ['label' => 'Roboto (neutral UI)', 'weights' => '400;500;700'],
            'Inter' => ['label' => 'Inter (modern UI)', 'weights' => '400;500;600;700'],
            'Raleway' => ['label' => 'Raleway (elegant sans)', 'weights' => '400;500;600;700'],
            'Merriweather' => ['label' => 'Merriweather (readable serif)', 'weights' => '400;700'],
            'Lora' => ['label' => 'Lora (editorial serif)', 'weights' => '400;600;700'],
            'PT Serif' => ['label' => 'PT Serif (classic serif)', 'weights' => '400;700'],
            'DM Sans' => ['label' => 'DM Sans (contemporary)', 'weights' => '400;500;700'],
            'Outfit' => ['label' => 'Outfit (geometric modern)', 'weights' => '400;500;600;700'],
            'Work Sans' => ['label' => 'Work Sans (clean geometric)', 'weights' => '400;500;600;700'],
        ];
    }

    public static function defaultFamily(): string
    {
        return 'Source Sans 3';
    }

    public static function cssUrlFor(string $family, ?string $weights = null): string
    {
        $family = trim($family);
        $catalog = self::catalog();
        $weights = $weights ?: ($catalog[$family]['weights'] ?? '400;500;600;700');
        $slug = rawurlencode($family);
        // Google Fonts CSS2 uses + for spaces in the family query param
        $slug = str_replace('%20', '+', $slug);

        return "https://fonts.googleapis.com/css2?family={$slug}:wght@{$weights}&display=swap";
    }

    /**
     * Resolve the active font for the public site.
     *
     * @return array{family: string, href: string, is_custom: bool}
     */
    public static function resolve(?string $family, ?string $cssUrl): array
    {
        $family = trim((string) $family);
        $cssUrl = trim((string) $cssUrl);

        if ($cssUrl !== '' && self::isAllowedGoogleFontsUrl($cssUrl)) {
            $resolvedFamily = $family !== '' ? $family : self::familyFromCssUrl($cssUrl);

            return [
                'family' => $resolvedFamily ?: self::defaultFamily(),
                'href' => $cssUrl,
                'is_custom' => true,
            ];
        }

        if ($family === '' || ! array_key_exists($family, self::catalog())) {
            $family = self::defaultFamily();
        }

        return [
            'family' => $family,
            'href' => self::cssUrlFor($family),
            'is_custom' => false,
        ];
    }

    public static function isAllowedGoogleFontsUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, ['fonts.googleapis.com', 'fonts.gstatic.com'], true);
    }

    public static function familyFromCssUrl(string $url): ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $params);
        $family = $params['family'] ?? null;
        if (! is_string($family) || $family === '') {
            return null;
        }

        // family=Playfair+Display:wght@600;700
        $name = explode(':', $family)[0] ?? '';

        return trim(str_replace('+', ' ', $name)) ?: null;
    }
}
