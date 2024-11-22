<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $model app\models\MoniteursHasBareme */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="moniteurs-has-bareme-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'fk_bareme')->dropDownList($modelParams->optsBaremeMoniteur($model->fk_bareme),['prompt'=>Yii::t('app', 'Choisir un barème')]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'date_debut')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'language'=>'fr',
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'date_fin')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'language'=>'fr',
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
