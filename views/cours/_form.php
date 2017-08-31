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
        <div class="col-sm-3">
            <?= $form->field($model, 'fk_niveau')->dropDownList($modelParams->optsNiveau(),['prompt'=>Yii::t('app', 'Choisir un niveau')]) ?>
        </div>
        <div class="col-sm-2">
            <label></label>
            <?= $form->field($model, 'is_actif')->checkbox() ?>
        </div>
        <div class="col-sm-2">
            <label></label>
            <?= $form->field($model, 'is_publie')->checkbox() ?>
        </div>
        <div class="col-sm-2">
            <label></label>
            <?= $form->field($model, 'is_materiel_compris')->checkbox() ?>
        </div>
        <div class="col-sm-2">
            <label></label>
            <?= $form->field($model, 'is_entree_compris')->checkbox() ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'fk_nom')->dropDownList($modelParams->optsNomCours(),['prompt'=>Yii::t('app', 'Choisir un nom')]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'fk_age')->dropDownList($modelParams->optsTrancheAge(),['prompt'=>Yii::t('app', 'Choisir une tranche d\'âge')]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'session')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'annee')->textInput(['type' => 'number', 'min' => 2016, 'maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'fk_jours')->checkboxList($modelParams->optsJourSemaine()) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'fk_saison')->dropDownList($modelParams->optsSaison(),['prompt'=>Yii::t('app', 'Choisir une saison')]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'fk_semestre')->dropDownList($modelParams->optsSemestre(),['prompt'=>Yii::t('app', 'Choisir un semestre')]) ?>
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
        <div class="col-sm-6">
            <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'offre_speciale')->textarea(['rows' => 6]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?php if (!$model->isNewRecord) { ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->cours_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Vous allez supprimer le cours ainsi que tous les participants et toutes les planifications. OK?'),
                'method' => 'post',
            ],
        ]) ?>
        <?php } ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
