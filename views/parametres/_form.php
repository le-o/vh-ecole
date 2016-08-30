<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\color\ColorInput;

/* @var $this yii\web\View */
/* @var $model app\models\Parametres */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="parametres-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'class_key')->dropDownList($model->optsRegroupement(),['prompt'=>'Choisir une valeur']) ?>

    <?= $form->field($model, 'nom')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'valeur')->widget(\yii\redactor\widgets\Redactor::className()) ?>

    <?= $form->field($model, 'info_special')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'info_couleur')->widget(ColorInput::classname(), ['options' => ['placeholder' => Yii::t('app', 'Choisir la couleur ...')],]) ?>
    
    <?= $form->field($model, 'tri')->textInput(['type' => 'number']) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
