<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\bootstrap\Alert;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Cours */
/* @var $form yii\widgets\ActiveForm */
$this->title = Yii::t('app', 'Modification des informations moniteurs');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->fkCoursDate->fk_cours, 'url' => ['view', 'id' => $model->fkCoursDate->fk_cours]];
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Gestion moniteurs'), 'url' => ['cours/gestionmoniteurs', 'cours_id' => $model->fkCoursDate->fk_cours]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Infos moniteurs');
?>
<div class="cours-has-moniteurs">

    <h1><?= Html::encode($this->title) ?></h1>

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
                <div class="form-group">
                    <label class="control-label">Moniteur</label> : <?= $model->fkMoniteur->nomPrenom ?> <?= $model->fkMoniteur->getLetterBaremeFromDate(date('Y-m-d')) ?>
                    <br />
                    <label class="control-label">Cours</label> : <?= $model->fkCoursDate->fkCours->fkNom->nom ?>
                    <br />
                    <label class="control-label">Date</label> : <?= $model->fkCoursDate->date ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($model, 'fk_bareme')->dropDownList($listeBareme) ?>
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', 'Enregistrer'), ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
