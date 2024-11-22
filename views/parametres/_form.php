<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\color\ColorInput;

/* @var $this yii\web\View */
/* @var $model app\models\Parametres */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="parametres-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'class_key')->dropDownList($model->optsRegroupement(),['prompt'=>Yii::t('app', 'Choisir une valeur')]) ?>

    <?= $form->field($model, 'nom')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'valeur')->widget(\yii\redactor\widgets\Redactor::className()) ?>
    
    <?= $form->field($model, 'fk_langue')->dropDownList($model->languesInterface(),['prompt'=>Yii::t('app', 'Non renseigné')]) ?>

    <?= $form->field($model, 'info_special')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'info_couleur')->widget(ColorInput::classname(), ['options' => ['placeholder' => Yii::t('app', 'Choisir la couleur ...')],]) ?>
    
    <?= $form->field($model, 'tri')->textInput(['type' => 'number']) ?>
    
    <?= $form->field($model, 'date_fin_validite')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'jj.mm.aaaa'],
        'removeButton' => false,
        'pluginOptions' => [
            'autoclose'=>true,
            'format' => 'dd.mm.yyyy'
        ]
    ]); ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
