<?php
namespace verbb\manytomany\base;

use verbb\manytomany\ManyToMany;
use verbb\manytomany\services\Service;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?ManyToMany $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;


    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('manytomany');

        return [
            'components' => [
                'service' => Service::class,
            ],
        ];
    }


    // Public Methods
    // =========================================================================

    public function getService(): Service
    {
        return $this->get('service');
    }

}