<?php namespace PalPalych\AutoInterlinking;

use Backend;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use PalPalych\AutoInterlinking\Models\Settings;
use PalPalych\AutoInterlinking\Classes\Interlinking;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Auto Interlinking',
            'description' => 'Automatically creates internal links for keywords.',
            'author'      => 'PalPalych',
            'icon'        => 'icon-link'
        ];
    }

    /**
     * registerMarkupTags registers any custom filters or functions for Twig.
     */
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'keywords' => function($content) {
                    return (new Interlinking($content))->render();
                }
            ]
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     */
    public function registerPermissions()
    {
        return [
            'palpalych.autointerlinking.manage_settings' => [
                'tab' => 'Auto Interlinking',
                'label' => 'Manage auto interlinking settings'
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'keywords' => [
                'label'       => 'Ключевые слова',
                'description' => 'Управление ключевыми словами для автоматической перелинковки.',
                'category'    => SettingsManager::CATEGORY_CMS,
                'icon'        => 'icon-key',
                'url'         => Backend::url('palpalych/autointerlinking/keywords'),
                'order'       => 500,
                'keywords'    => 'interlinking keywords',
                'permissions' => ['palpalych.autointerlinking.manage_settings']
            ],
            'settings' => [
                'label' => "Ключевые слова - настройки",
                'description' => "Настройки автоперелинковки",
                'category'    => SettingsManager::CATEGORY_CMS,
                'icon' => 'icon-link',
                'class' => Settings::class,
                'order' => 500,
            ],
        ];
    }
}
