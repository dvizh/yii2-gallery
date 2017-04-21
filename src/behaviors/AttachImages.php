<?php
namespace dvizh\gallery\behaviors;

use yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use dvizh\gallery\models;
use yii\helpers\BaseFileHelper;
use dvizh\gallery\ModuleTrait;
use dvizh\gallery\models\Image;
use dvizh\gallery\models\PlaceHolder;

class AttachImages extends Behavior
{
    use ModuleTrait;
    
    public $createAliasMethod = false;
    public $modelClass = null;
    public $uploadsPath = '';
    public $mode = 'gallery';
    public $webUploadsPath = '/uploads';
    public $allowExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    public $inputName = 'galleryFiles';
    private $doResetImages = true;
    public $quality = false;
    public $galleryId = null;

    public function init()
    {
        if (empty($this->uploadsPath)) {
            $this->uploadsPath = yii::$app->getModule('gallery')->imagesStorePath;
        }

        if ($this->quality > 100) {
            $this->quality = 100;
        } elseif ($this->quality < 0) {
            $this->quality = 0;
        }
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'setImages',
            ActiveRecord::EVENT_AFTER_INSERT => 'setImages',
            ActiveRecord::EVENT_BEFORE_DELETE => 'removeImages',
        ];
    }

    private static function resizePhoto($path, $tmp_name, $quality){
        $type = pathinfo($path, PATHINFO_EXTENSION);

        switch($type){
            case 'jpeg':
            case 'jpg': {
                $source = imagecreatefromjpeg($tmp_name);
                $result = imagejpeg($source, $path, $quality);
                break;
            }
            case 'png': {
                $source = imagecreatefrompng($tmp_name);
                imagesavealpha($source, true);
                $quality = (int) (100 - $quality) / 10 - 1;
                $result = imagepng($source, $path, $quality);
                break;

            }
            case 'gif': {
                $source = imagecreatefromgif($tmp_name);
                $result = imagegif($source, $path);
                break;
            }

            default: return false;
        }

        imagedestroy($source);

        return $result;
    }

    public function attachImage($absolutePath, $isMain = false)
    {
        if(!preg_match('#http#', $absolutePath)){
            if (!file_exists($absolutePath)) {
                throw new \Exception('File not exist! :'.$absolutePath);
            }
        }
        
        if (!$this->owner->id) {
            throw new \Exception('Owner must have id when you attach image!');
        }

        $pictureFileName =
            substr(md5(microtime(true)
            . $absolutePath), 4, 6)
            . '.'
            . pathinfo($absolutePath, PATHINFO_EXTENSION);

        $pictureSubDir = $this->getModule()->getModelSubDir($this->owner);
        $storePath = $this->getModule()->getStorePath($this->owner);

        $newAbsolutePath = $storePath
            . DIRECTORY_SEPARATOR
            . $pictureSubDir
            . DIRECTORY_SEPARATOR
            . $pictureFileName;

        BaseFileHelper::createDirectory($storePath . DIRECTORY_SEPARATOR . $pictureSubDir, 0775, true);

        if ( $this->quality !== false){
            self::resizePhoto($newAbsolutePath, $absolutePath, $this->quality);
        } else {
            copy($absolutePath, $newAbsolutePath);
        }
        
        if (!file_exists($absolutePath)) {
            throw new \Exception('Cant copy file! ' . $absolutePath . ' to ' . $newAbsolutePath);
        }

        if($this->modelClass === null) {
            $image = new models\Image;
        }else{
            $image = new ${$this->modelClass}();
        }

        $image->itemId = $this->owner->id;
        $image->filePath = $pictureSubDir . '/' . $pictureFileName;
        $image->modelName = $this->getModule()->getShortClass($this->owner);
        $image->urlAlias = $this->getAlias($image);
        $image->gallery_id = $this->galleryId;

        if(!$image->save()){
            return false;
        }

        if (count($image->getErrors()) > 0) {
            $ar = array_shift($image->getErrors());

            unlink($newAbsolutePath);
            throw new \Exception(array_shift($ar));
        }
        $img = $this->owner->getImage();

        if ( is_object($img) && get_class($img)=='dvizh\gallery\models\PlaceHolder' or $img == null or $isMain) {
            $this->setMainImage($image);
        }

        return $image;
    }
    
    public function setMainImage($img)
    {
        if ($this->owner->id != $img->itemId) {
            throw new \Exception('Image must belong to this model');
        }

        $counter = 1;
        $img->setMain(true);
        $img->urlAlias = $this->getAliasString() . '-' . $counter;
        $img->save();

        $images = $this->owner->getImages();

        foreach ($images as $allImg) {
            if ($allImg->id == $img->id) {
                continue;
            } else {
                $counter++;
            }

            $allImg->setMain(false);
            $allImg->urlAlias = $this->getAliasString() . '-' . $counter;
            $allImg->save();
        }

        $this->owner->clearImagesCache();
    }
    
    public function clearImagesCache()
    {
        $cachePath = $this->getModule()->getCachePath();
        $subdir = $this->getModule()->getModelSubDir($this->owner);
        $dirToRemove = $cachePath . '/' . $subdir;

        if (preg_match('/' . preg_quote($cachePath, '/') . '/', $dirToRemove)) {
            BaseFileHelper::removeDirectory($dirToRemove);

            return true;
        } else {
            return false;
        }
    }

    public function getImages()
    {
        $finder = $this->getImagesFinder();
        $imageQuery = Image::find()->where($finder);
        $imageQuery->orderBy(['isMain' => SORT_DESC,'sort' => SORT_DESC, 'id' => SORT_ASC]);
        $imageRecords = $imageQuery->all();
        if(!$imageRecords){
            return [$this->getModule()->getPlaceHolder()];
        }

        return $imageRecords;
    }

    public function getImage()
    {
        $finder = $this->getImagesFinder();
        $imageQuery = Image::find()->where($finder);
        $imageQuery->orderBy(['isMain' => SORT_DESC,'sort' => SORT_DESC, 'id' => SORT_ASC]);
        $img = $imageQuery->one();

        if(!$img){
            return $this->getModule()->getPlaceHolder();
        }

        return $img;
    }

    public function getImageByName($name)
    {
        if ($this->getModule()->className === null) {
            $imageQuery = Image::find();
        } else {
            $class = $this->getModule()->className;
            $imageQuery = $class::find();
        }

        $finder = $this->getImagesFinder(['name' => $name]);
        $imageQuery->where($finder);
        $imageQuery->orderBy(['isMain' => SORT_DESC, 'id' => SORT_ASC]);
        $img = $imageQuery->one();

        if(!$img){
            return $this->getModule()->getPlaceHolder();
        }

        return $img;
    }

    public function removeImages()
    {
        $images = $this->owner->getImages();

        if (count($images) < 1) {
            return true;
        } else {
            foreach ($images as $image) {
                $this->owner->removeImage($image);
            }
        }
    }
    
    public function removeImage(Image $img)
    {
        $img->clearCache();

        $storePath = $this->getModule()->getStorePath();
        $fileToRemove = $storePath . DIRECTORY_SEPARATOR . $img->filePath;

        if (preg_match('@\.@', $fileToRemove) and is_file($fileToRemove)) {
            unlink($fileToRemove);
        }

        $img->delete();
    }

    private function getImagesFinder($additionWhere = false)
    {
        $base = [
            'itemId' => $this->owner->id,
            'modelName' => $this->getModule()->getShortClass($this->owner),
			'gallery_id' => $this->galleryId
        ];

        if ($additionWhere) {
            $base = \yii\helpers\BaseArrayHelper::merge($base, $additionWhere);
        }

        return $base;
    }

    private function getAliasString()
    {
        if ($this->createAliasMethod) {
            $string = $this->owner->{$this->createAliasMethod}();
            if (!is_string($string)) {
                throw new \Exception("Image's url must be string!");
            } else {
                return $string;
            }

        } else {
            return substr(md5(microtime()), 0, 10);
        }
    }

    private function getAlias()
    {
        $aliasWords = $this->getAliasString();
        $imagesCount = count($this->owner->getImages());

        return $aliasWords . '-' . intval($imagesCount + 1);
    }
    
    public function getGalleryMode()
    {
        return $this->mode;
    }

    public function getInputName()
    {
        return $this->inputName;
    }
    
    public function setImages($event)
    {
        $userImages = UploadedFile::getInstancesByName($this->getInputName());

        if ($userImages && $this->doResetImages) {
            foreach ($userImages as $file) {
                if(in_array(strtolower($file->extension), $this->allowExtensions)) {

                    if (!file_exists($this->uploadsPath)){
                        mkdir($this->uploadsPath, 0777, true);
                    }
                    
                    $file->saveAs("{$this->uploadsPath}/{$file->baseName}.{$file->extension}");

                    if($this->owner->getGalleryMode() == 'single') {
                        foreach($this->owner->getImages() as $image) {
                            $image->delete();
                        }
                    }

                    $this->attachImage("{$this->uploadsPath}/{$file->baseName}.{$file->extension}");
                }
            }

            $this->doResetImages = false;
        }

        return $this;
    }

    public function hasImage()
    {
        return ($this->getImage() instanceof PlaceHolder) ? false : true;
    }
}