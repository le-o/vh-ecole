<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\Cours */
/* @var $form yii\widgets\ActiveForm */
?>

<?php if ($alerte != '') {
    echo Alert::widget([
        'options' => [
            'class' => 'alert-danger',
        ],
        'body' => $alerte,
    ]); 
} ?>

<div class="cours-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'fk_niveau')->dropDownList($modelParams->optsNiveau(),['prompt'=>Yii::t('app', 'Choisir un niveau')]) ?>
        </div>
        <div class="col-sm-1">
            <label></label>
            <?= $form->field($model, 'is_actif')->checkbox() ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-8">
            <?= $form->field($model, 'fk_nom')->dropDownList($modelParams->optsNomCours(),['prompt'=>Yii::t('app', 'Choisir un nom')]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'session')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'annee')->textInput(['type' => 'number', 'min' => 2016, 'maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'duree')->textInput(['type' => 'number', 'step' => '0.25', 'placeholder' => Yii::t('app', 'en heure')]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'prix')->textInput(['type' => 'number', 'min' => 0, 'max' => 5000]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'participant_min')->textInput(['type' => 'number', 'min' => 1, 'max' => 50]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'participant_max')->textInput(['type' => 'number', 'min' => 1, 'max' => 150]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
