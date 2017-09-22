<?php

namespace OberonAmsterdam\ManyToMany\assetbundles\manytomanyfield;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ManyToManyFieldAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
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
