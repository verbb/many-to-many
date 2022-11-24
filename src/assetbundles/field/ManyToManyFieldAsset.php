<?php
namespace verbb\manytomany\assetbundles\field;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ManyToManyFieldAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'input.js',
        ];

        parent::init();
    }
}
