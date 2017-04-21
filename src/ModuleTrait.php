<?php
namespace dvizh\gallery;


use yii\base\Exception;

trait ModuleTrait
{
    private $_module;

    protected function getModule()
    {
        if ($this->_module == null) {
            $this->_module = \Yii::$app->getModule('gallery');
        }

        if(!$this->_module){
            throw new Exception("\n\n\n\n\nGallery module not found, may be you didn't add it to your config?\n\n\n\n");
        }

        return $this->_module;
    }
}