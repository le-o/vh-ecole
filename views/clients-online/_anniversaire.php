<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use kartik\depdrop\DepDrop;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="clients-online-form">

    <h1><?= $dataCours ?></h1>

    <?php $form = ActiveForm::begin(['id' => 'dynamic-form']); ?>
    
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'nom')->textInput(['maxlength' => true])->label(Yii::t('app', 'Nom (personne responsable)')) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'prenom')->textInput(['maxlength' => true])->label(Yii::t('app', 'Prénom (personne responsable)')) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'adresse')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'npa')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'localite')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'telephone')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'prenom_enfant')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'date_naissance_enfant')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                    'defaultViewDate' => ['year' => 2010]
                ]
            ]); ?>
        </div>
    </div>
    <div class="row" id="infoanni">
        <div class="col-sm-6">
            <?= $form->field($model, 'agemoyen')->widget(Select2::classname(), [
                'options'=>[
                    'id' => 'age-moyen',
                    'placeholder'=>Yii::t('app', 'Choisir un âge moyen'),
                    'multiple' => false,
                ],
                'data' => $choixAge,
                'pluginOptions'=>[
                    'initialize' => true,
                    'allowClear' => false,
                    'tags' => false,
                ],
            ]); ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'nbparticipant')->widget(DepDrop::classname(), [
                'options'=>['id'=>'nb-participant'],
                'type' => DepDrop::TYPE_SELECT2,
                'data' => $model->optsPartByAge($model->agemoyen),
                'pluginOptions'=>[
                    'depends'=>['age-moyen'],
                    'placeholder'=>Yii::t('app', 'Choisir un nombre de participant'),
                    'url'=> Url::to(['depnbparticipants']),
                ],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12"><br />
            <?= $form->field($model, 'informations')->textarea(['rows' => 6])->label(Yii::t('app', 'Infos, détails et besoins particuliers')) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'iagree')->checkbox() ?>
        </div>
    </div>
    
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'S\'inscrire') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
    
    <br /><br />
    <?= Yii::t('app', "Conditions inscription et annulation anniversaire") ?>


</div>