<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\PersonnesSearch */
/* @var $form yii\widgets\ActiveForm */

if (!isset($isMoniteur)) $isMoniteur = false;
?>

<div class="personnes-search">

    <?php $form = ActiveForm::begin([
        'action' => ['moniteurs'],
        'method' => 'get',
    ]); ?>
    
    <div class="row">
        <?php if (false == $isMoniteur) { ?>
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
                    'pluginOptions' => ['allowClear' => true],
                ]); ?>
            </div>
        <?php } ?>
        <div class="col-sm-3">
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
        <?php if (false == $isMoniteur) { ?>
            <div class="col-sm-2">
                <label class="control-label"><?= Yii::t('app', 'Langue parlée') ?></label>
                <?= Select2::widget([
                    'name' => 'fk_langues',
                    'value' => $selectedLangue, // initial value
                    'data' => $dataLangues,
                    'options' => ['placeholder' => Yii::t('app', 'Choisir une langue ...')],
                    'pluginOptions' => ['allowClear' => true],
                ]); ?>
            </div>
        <?php } ?>
        <div class="col-sm-3">
            <div class="form-group">
                <br />
                <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>

    <?php ActiveForm::end(); ?>

</div>
