<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>


<?php $form = ActiveForm::begin( ['action' => ['/gallery/default/write', 'id' => $model->id], 'options' => ['id' => 'noctua-gallery-form']]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($model, 'alt')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 5]) ?>

    <?= $form->field($model, 'sort')->textInput() ?>

    <?= Html::hiddenInput('model', $post['model']) ?>

    <?= Html::hiddenInput('id', $post['id']) ?>

    <?= Html::hiddenInput('image', $post['image']) ?>

    <div class="buttonSet text-right button-container">
        <?= Html::submitButton(yii::t('gallery', 'Send'), ['class' => 'btn btn-success']) ?>
    </div>

<?php ActiveForm::end(); ?>