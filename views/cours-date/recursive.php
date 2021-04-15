<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use kartik\time\TimePicker;
use kartik\daterange\DateRangePicker;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\CoursDate */
/* @var $form yii\widgets\ActiveForm */

$this->title = Yii::t('app', 'Create Cours Date');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours Dates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cours-date-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!empty($alerte)) {
        echo Alert::widget([
            'options' => [
                'class' => 'alert-'.$alerte['class'],
            ],
            'body' => $alerte['message'],
        ]);
    } ?>

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
            <div class="col-sm-4">
                <label class="control-label" for="date_range_1"><?= Yii::t('app', 'Jour semaine') ?></label>
                <div class="form-group">
                    <label class="col-md-1 checkbox-inline" for="checkboxes-0"><input type="checkbox" name="jour_semaine[]" id="checkboxes-0" value="1">Lu</label>
                    <label class="col-md-1 checkbox-inline" for="checkboxes-1"><input type="checkbox" name="jour_semaine[]" id="checkboxes-1" value="2">Ma</label>
                    <label class="col-md-1 checkbox-inline" for="checkboxes-2"><input type="checkbox" name="jour_semaine[]" id="checkboxes-2" value="3">Me</label>
                    <label class="col-md-1 checkbox-inline" for="checkboxes-3"><input type="checkbox" name="jour_semaine[]" id="checkboxes-3" value="4">Je</label>
                    <label class="col-md-1 checkbox-inline" for="checkboxes-4"><input type="checkbox" name="jour_semaine[]" id="checkboxes-4" value="5">Ve</label>
                    <label class="col-md-1 checkbox-inline" for="checkboxes-5"><input type="checkbox" name="jour_semaine[]" id="checkboxes-5" value="6">Sa</label>
                    <label class="col-md-1 checkbox-inline" for="checkboxes-6"><input type="checkbox" name="jour_semaine[]" id="checkboxes-6" value="7">Di</label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <label class="control-label" for="date_range_1"><?= Yii::t('app', 'Plage de date') ?></label>
                <?php echo DateRangePicker::widget([
                    'name'=>'date_range_1',
                    'value'=>$date_range,
                    'convertFormat'=>true,
                    'useWithAddon'=>true,
                    'hideInput'=>true,
                    'presetDropdown'=>false,
                    'language'=>'fr',
                    'pluginOptions'=>[
                        'locale'=>['format'=>'d.m.Y',
                            'separator'=>Yii::t('app', ' au ')],
                        'opens'=>'left'
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
            <div class="col-sm-4">
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
            <div class="col-sm-2">
                <?= $form->field($model, 'baremeMoniteur')->dropDownList($modelParams->optsNiveauFormation(), ['prompt'=>'Fixer un barème']) ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'fk_lieu')->dropDownList($modelParams->optsLieu($model->fk_lieu)) ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-12">
                <?= $form->field($model, 'remarque')->textarea(['rows' => 6]) ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-6">
                <label class="control-label" for="w1"><?= Yii::t('app', 'Dates à omettre'); ?></label>
                <?= DatePicker::widget([
                    'name' => 'date_exclude_1',
                    'options' => ['placeholder' => 'jj.mm.aaaa'],
                    'removeButton' => false,
                    'language'=>'fr',
                    'pluginOptions' => [
                        'format' => 'dd.mm.yyyy',
                        'multidate' => true,
                        'multidateSeparator' => ' + ',
                    ]
                ]); ?><br />
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
