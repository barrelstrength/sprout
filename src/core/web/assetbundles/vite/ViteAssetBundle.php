<?php

namespace BarrelStrength\Sprout\core\web\assetbundles\vite;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ViteAssetBundle extends AssetBundle
{
    public function init(): void
    {
        parent::init();

        $this->sourcePath = '@Sprout/Assets/dist';

        $this->depends = [
            CpAsset::class,
        ];
    }
}
