<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ParametresSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="parametres-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'parametre_id') ?>

    <?= $form->field($model, 'class_key') ?>

    <?= $form->field($model, 'nom') ?>

    <?= $form->field($model, 'valeur') ?>

    <?= $form->field($model, 'info_special') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
