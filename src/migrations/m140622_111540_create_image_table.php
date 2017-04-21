<?php

use yii\db\Schema;

class m140622_111540_create_image_table extends \yii\db\Migration
{
    public function up()
    {
        $this->createTable('image', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255),
            'alt' => $this->string(255),
            'filePath' => $this->string(400)->notNull(),
            'itemId' => $this->integer(20)->notNull(),
            'isMain' => $this->boolean(),
            'modelName' => $this->string(150)->notNull(),
            'urlAlias' => $this->string(400)->notNull(),
            'description' => $this->text(),
            'gallery_id' => $this->string(150),
            'sort' => $this->integer(15),
        ]);
    }

    public function down()
    {
        echo "m140622_111540_create_image_table cannot be reverted.\n";

        return false;
    }
}
