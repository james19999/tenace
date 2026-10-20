<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppThemeSetting extends Model
{
    protected $fillable = [
        'primary_color', 'secondary_color', 'accent_color',
        'background_color', 'surface_color', 'text_color', 'corner_style',
        'appearance_mode',
    ];

    public static function currentTheme(): array
    {
        $theme = [
            'primary_color' => '#7e1615', 'secondary_color' => '#343a40',
            'accent_color' => '#5fb49c', 'background_color' => '#f8f9fa',
            'surface_color' => '#ffffff', 'text_color' => '#292d38', 'corner_style' => 'soft', 'appearance_mode' => 'light',
        ];

        if (! Schema::hasTable('app_theme_settings')) {
            return $theme;
        }

        $stored = static::query()->find(1)?->getAttributes() ?? [];
        foreach (array_keys($theme) as $key) {
            if (! array_key_exists($key, $stored)) {
                continue;
            }
            if ($key === 'corner_style') {
                if (in_array($stored[$key], ['square', 'soft', 'rounded'], true)) {
                    $theme[$key] = $stored[$key];
                }
            } elseif ($key === 'appearance_mode') {
                if (in_array($stored[$key], ['light', 'dark'], true)) {
                    $theme[$key] = $stored[$key];
                }
            } elseif (is_string($stored[$key]) && preg_match('/^#[0-9a-fA-F]{6}$/', $stored[$key])) {
                $theme[$key] = $stored[$key];
            }
        }

        $theme['primary_contrast_color'] = static::contrastColor($theme['primary_color']);
        $theme['accent_contrast_color'] = static::contrastColor($theme['accent_color']);

        return $theme;
    }

    private static function contrastColor(string $hex): string
    {
        $rgb = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
        $channels = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb ?: [126, 22, 21]);
        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

        return $luminance > 0.42 ? '#20242c' : '#ffffff';
    }
}
