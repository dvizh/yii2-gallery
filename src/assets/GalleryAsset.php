<?php
namespace dvizh\gallery\assets;

use yii\web\AssetBundle;

class GalleryAsset extends AssetBundle
{
    public $depends = [
        'yii\web\JqueryAsset',
    ];
    
    public $js = [
        'js/scripts.js',
    ];

    public $css = [
        'css/styles.css',
    ];
    
    public function init()
    {
        $this->sourcePath = dirname(__DIR__).'/web';
        parent::init();
    }
}
