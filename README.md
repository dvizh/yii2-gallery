Yii2-gallery
==========
Это модуль был создан, чтобы дать возможность быстро загружать в админке картинки, добавлять титульник, описание, альтернативный текст, а также задать положение (чем выше значение тем выше в списке будет изображение).

Установка
---------------------------------
Выполнить команду

```
php composer require dvizh/yii2-gallery "@dev"
```

Или добавить в composer.json

```
"dvizh/yii2-gallery": "@dev",
```

И выполнить

```
php composer update
```

Миграция

```
php yii migrate/up --migrationPath=@vendor/dvizh/yii2-gallery/src/migrations
```

Подключение и настройка
---------------------------------
В конфигурационный файл приложения добавить модуль gallery
```php
    'modules' => [
        'gallery' => [
            'class' => 'dvizh\gallery\Module',
            'imagesStorePath' => dirname(dirname(__DIR__)).'/frontend/web/images/store', //path to origin images
            'imagesCachePath' => dirname(dirname(__DIR__)).'/frontend/web/images/cache', //path to resized copies
            'graphicsLibrary' => 'GD',
            'placeHolderPath' => '@webroot/images/placeHolder.png',
            'adminRoles' => ['administrator', 'admin', 'superadmin'],
        ],
        //...
    ]
```

К модели, к которой необходимо аттачить загружаемые картинки, добавляем поведение:

```php
    function behaviors()
    {
        return [
            'images' => [
                'class' => 'dvizh\gallery\behaviors\AttachImages',
                'mode' => 'gallery',
                'quality' => 60,
                'galleryId' => 'picture'
            ],
        ];
    }
```

*mode - тип загрузки. gallery - массовая загрузка, single - одиночное поле, если вам необходимо сжатие то установите quality (0 - 100) где  0 - максимальное сжатие, 100 - минимальное сжатие. galleryId - идентификатор галереи, если у вас возникает конфликт при одинаковых имён класса

Использование
---------------------------------
Использовать можно также, как напрямую:

```php
$images = $model->getImages();
foreach($images as $img) {
    //retun url to full image
    echo $img->getUrl();

    //return url to proportionally resized image by width
    echo $img->getUrl('300x');

    //return url to proportionally resized image by height
    echo $img->getUrl('x300');

    //return url to resized and cropped (center) image by width and height
    echo $img->getUrl('200x300');

    //return alt text to image
    $img->alt

    //return title to image
    $img->title
    
    //return description image
    $img->description
}
```

Виджеты
---------------------------------
Загрузка картинок осуществляется через виджет. Добавьте в _form.php внутри формы CRUDа вашей модели.
Виджету передаются следующие параметры:
model => Модель к которой будут привязаны картинки, по умолчанию null;
previewSize => размер превью загруженных изображений, по умолчанию '140x140';
label => метка для виджета по умолчанию 'Изображение';
fileInputPluginLoading => нужно ли показывать индикатор загрузки прогресса в месте ввода, по умолчанию true;
fileInputPluginOptions => массив свойств виджета [kartik/file/fileInput](http://demos.krajee.com/widget-details/fileinput), по умолчанию [];


Не забудьте
```php<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>```
для формы.

```php
<?=\dvizh\gallery\widgets\Gallery::widget(
    [
        'model' => $model,
        'previewSize' => '50x50',
        'fileInputPluginLoading' => true,
        'fileInputPluginOptions' => []
    ]
); ?>
```
