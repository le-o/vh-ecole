<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use leo\modules\UserManagement\models\User;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\CoursDateSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="cours-date-search">

    <?php $form = ActiveForm::begin([
        'action' => ['liste'],
        'method' => 'get',
    ]); ?>
    
    <div class="row">
        <div class="col-sm-2">
            <?= $form->field($model, 'fkCours')->textInput(['placeholder' => Yii::t('app', 'Nom du cours')])->label(false) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'session')->textInput(['placeholder' => Yii::t('app', 'Session')])->label(false) ?>
        </div>
        <?php if (User::canRoute(['/cours-date/search'])) { ?>
            <div class="col-sm-3">
                <?php echo DatePicker::widget([
                    'model' => $model,
                    'attribute' => 'depuis',
                    'attribute2' => 'dateA',
                    'options' => ['placeholder' => Yii::t('app', 'Date début')],
                    'options2' => ['placeholder' => Yii::t('app', 'Date fin')],
                    'type' => DatePicker::TYPE_RANGE,
                    'separator' => '&nbsp;'.Yii::t('app', ' à ').'&nbsp;',
                    'form' => $form,
                    'pluginOptions' => [
                        'format' => 'dd.mm.yyyy',
                        'autoclose' => true,
                    ]
                ]); ?>
            </div>
        <?php } ?>
        <div class="col-sm-2">
            <?= Select2::widget([
                'name' => 'fkTypeCours',
                'value' => $selectedTypeCours, // initial value
                'data' => $dataTypeCours,
                'options' => ['placeholder' => Yii::t('app', 'Type de cours')],
                'pluginOptions' => ['allowClear' => true],
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?= Select2::widget([
                'name' => 'fkSalle',
                'value' => $selectedSalle, // initial value
                'data' => $dataSalle,
                'options' => ['placeholder' => Yii::t('app', 'Salle')],
                'pluginOptions' => ['allowClear' => true],
            ]); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-2">
            <?= $form->field($model, 'withoutMoniteur')->checkbox(['label' => Yii::t('app', 'Sans moniteur')]) ?>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
                <?= Html::a(Yii::t('app', 'Reset'), ['cours-date/liste'], ['class'=>'btn btn-default']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
