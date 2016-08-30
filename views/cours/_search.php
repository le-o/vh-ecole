<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\CoursSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="cours-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'cours_id') ?>

    <?= $form->field($model, 'fk_niveau') ?>

    <?= $form->field($model, 'nom') ?>

    <?= $form->field($model, 'description') ?>

    <?= $form->field($model, 'annee') ?>

    <?php // echo $form->field($model, 'participant_min') ?>

    <?php // echo $form->field($model, 'participant_max') ?>

    <?php // echo $form->field($model, 'is_actif') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
