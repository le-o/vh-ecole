<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\PersonnesSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="personnes-search">

    <?php $form = ActiveForm::begin([
        'action' => ['moniteurs'],
        'method' => 'get',
    ]); ?>
    
    <div class="row">
        <div class="col-sm-2">
            <?= $form->field($model, 'nom')->label(Yii::t('app', 'Nom du moniteur')) ?>
        </div>
        <div class="col-sm-2">
            <label class="control-label"><?= Yii::t('app', 'Nom du cours') ?></label>
            <?= Select2::widget([
				'name' => 'list_cours',
				'value' => $selectedCours, // initial value
				'data' => $dataCours,
				'options' => ['placeholder' => Yii::t('app', 'Choisir un cours ...')],
			    'pluginOptions' => [
			        'allowClear' => true,
			    ],
			]); ?>
        </div>
        <div class="col-sm-4">
            <label class="control-label"><?= Yii::t('app', 'Date du cours') ?></label>
            <?= DatePicker::widget([
                'name' => 'from_date',
                'value' => $searchFrom,
                'type' => DatePicker::TYPE_RANGE,
                'name2' => 'to_date',
                'value2' => $searchTo,
                'separator' => Yii::t('app', ' à '),
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy'
                ]
            ]); ?>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <br />
                <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
            </div>
        </div>

    <?php ActiveForm::end(); ?>

</div>
