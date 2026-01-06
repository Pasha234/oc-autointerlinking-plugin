<?php

namespace PalPalych\AutoInterlinking\Models;

use Model;

/**
 * Settings Model
 *
 * @property array $excluded_html_tags
 * @property array $excluded_pages
 * @property bool $cache_enabled
 * @property int $cache_lifetime
 */
class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    /**
     * @var string A unique code
     */
    public $settingsCode = 'palpalych_autointerlinking_settings';

    /**
     * @var string Reference to field configuration
     */
    public $settingsFields = 'fields.yaml';
}
