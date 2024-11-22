<?php

use yii\helpers\Html;
use yii\bootstrap\Alert;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $model app\models\Moniteurs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="moniteurs-form">

    <?php if ($alerte != '') {
        echo Alert::widget([
            'options' => [
                'class' => 'alert-danger',
            ],
            'body' => $alerte,
        ]);
    } ?>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-sm-5">
            <?= $form->field($model, 'diplome')->textarea(['rows' => 6]) ?>
        </div>
        <div class="col-sm-5">
            <?= $form->field($model, 'remarque')->textarea(['rows' => 6]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'no_cresus')->textInput(['type' => 'number', 'min' => '1']) ?><br />
            <?= $form->field($model, 'experience_cours')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-2">
            <?= $form->field($model, 'animateur_asse')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'parcours')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'methode_VCS')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'js1_escalade')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'js_allround')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'encadrant_asse')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'instructeur_asse')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'js2_escalade')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'js3_escalade')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'referent_asse')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'expert_asse')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'js_expert')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'prof_escalade')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                ]
            ]); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-3">
            <br />
            <?php
            $espaceOuRetour = ['&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '<br />'];
            $i = 0;
            foreach ((new \app\models\Parametres())->optsFormation() as $id => $formation) {
                $isChecked = ($model->checkMoniteursHasOneFormation($model->moniteur_id, $id) ? true : false);
                echo yii\bootstrap\BaseHtml::checkbox('formationsMoniteur[' . $id . ']', $isChecked);
                echo ' <label class="control-label">' . $formation . '</label>' . $espaceOuRetour[$i % 2];
                $i++;
            } ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
