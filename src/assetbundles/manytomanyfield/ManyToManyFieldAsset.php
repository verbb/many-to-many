<?php

namespace Page8\ManyToMany\assetbundles\manytomanyfield;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ManyToManyFieldAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
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
