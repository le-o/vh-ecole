<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use kartik\time\TimePicker;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\CoursDate */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="cours-date-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'fk_cours')->widget(Select2::classname(), [
			    'data' => $dataCours,
			    'options' => ['placeholder' => Yii::t('app', 'Choisir un cours ...')],
                'disabled' => true,
			    'pluginOptions' => [
			        'allowClear' => true
			    ],
			]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model->fkCours, 'participant_min')->textInput(['disabled' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model->fkCours, 'participant_max')->textInput(['disabled' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'date')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'language'=>'fr',
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'heure_debut')->widget(TimePicker::classname(), [
                'pluginOptions' => [
                    'minuteStep' => 1,
                    'showMeridian' => false,
                ]
            ]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'duree')->textInput(['type' => 'number', 'step' => '0.25', 'placeholder' => Yii::t('app', 'en heure')]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'prix')->textInput(['type' => 'number', 'min' => 0, 'max' => 5000]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-6">
	        <label class="control-label" for="w1"><?= Yii::t('app', 'Fk Moniteur'); ?></label>
	        <?= Select2::widget([
				'name' => 'list_moniteurs',
				'value' => $selectedMoniteurs, // initial value
				'data' => $dataMoniteurs,
				'options' => ['placeholder' => Yii::t('app', 'Choisir un/des moniteurs ...'), 'multiple' => true],
			    'pluginOptions' => [
			        'allowClear' => true,
			        'tags' => true,
			    ],
			]); ?>
        </div>
        <div class="col-sm-6">
			<?= $form->field($model, 'lieu')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'remarque')->textarea(['rows' => 6]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'nb_client_non_inscrit')->textInput(['type' => 'number', 'step' => '1']) ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?php if (!$model->isNewRecord) { ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->cours_date_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
        <?php } ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
