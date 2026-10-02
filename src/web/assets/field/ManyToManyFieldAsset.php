<?php
namespace verbb\manytomany\web\assets\field;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ManyToManyFieldAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/manytomany/web/assets/field/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'many-to-many.js',
        ];

        parent::init();
    }
}
