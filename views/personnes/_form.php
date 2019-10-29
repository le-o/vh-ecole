<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use kartik\helpers\Enum;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\Personnes */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="personnes-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'fk_statut')->dropDownList($modelParams->optsStatut($model->fk_statut),['prompt'=>'Choisir un statut']) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'fk_type')->dropDownList($modelParams->optsType($model->fk_type),['prompt'=>'Choisir un type']) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'fk_formation')->dropDownList($modelParams->optsNiveauFormation($model->fk_formation),['prompt'=>'Choisir un niveau']) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-2">
            <?= $form->field($model, 'noclient_cf')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'societe')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'nom')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'prenom')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'adresse1')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'adresse2')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'npa')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'localite')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'email')->textInput(['maxlength' => true]); ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'email2')->textInput(['maxlength' => true]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'telephone')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'telephone2')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'date_naissance')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                    'defaultViewDate' => ['year' => 1980]
                ]
            ]); ?>
        </div>
        <div class="col-sm-6">
	        <label class="control-label" for="w1"><?= Yii::t('app', 'Fk Interlocuteur'); ?></label>
	        <?= Select2::widget([
				'name' => 'list_interlocuteurs',
				'value' => $selectedInterlocuteurs, // initial value
				'data' => $dataInterlocuteurs,
				'options' => ['placeholder' => Yii::t('app', 'Choisir un/des interlocuteurs ...'), 'multiple' => true],
			    'pluginOptions' => [
			        'allowClear' => true,
			        'tags' => true,
			    ],
			]); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'informations')->textarea(['rows' => 6]) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'suivi_client')->textarea(['rows' => 6]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'carteclient_cf')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'categorie3_cf')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'soldefacture_cf')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    
    <?php if (in_array($model->fk_type, Yii::$app->params['typeEncadrant'])) { ?>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'fk_langues')->checkboxList($modelParams->optsLangue()) ?>
        </div>
        <div class="col-sm-8">
            <?= $form->field($model, 'complement_langue')->textarea(['rows' => 6]) ?>
        </div>
    </div>
    <?php } ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
