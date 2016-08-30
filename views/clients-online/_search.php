<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnlineSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="clients-online-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'client_online_id') ?>

    <?= $form->field($model, 'fk_parent') ?>

    <?= $form->field($model, 'fk_cours') ?>

    <?= $form->field($model, 'nom') ?>

    <?= $form->field($model, 'prenom') ?>

    <?php // echo $form->field($model, 'adresse') ?>

    <?php // echo $form->field($model, 'npa') ?>

    <?php // echo $form->field($model, 'localite') ?>

    <?php // echo $form->field($model, 'telephone') ?>

    <?php // echo $form->field($model, 'email') ?>

    <?php // echo $form->field($model, 'date_naissance') ?>

    <?php // echo $form->field($model, 'informations') ?>

    <?php // echo $form->field($model, 'date_inscription') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
