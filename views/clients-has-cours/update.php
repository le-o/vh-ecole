<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\CoursDate */
/* @var $form yii\widgets\ActiveForm */

$this->title = $modelPersonne->nom . ' ' . $modelPersonne->prenom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours'), 'url' => ['/cours/index']];
$this->params['breadcrumbs'][] = ['label' => $model->fkCours->fkNom->nom, 'url' => ['/cours/view', 'id' => $model->fk_cours]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cours-date-form">

    <h1><?= Html::encode($this->title) ?></h1>
    <br /><br />
    
    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'fk_statut')->dropDownList($modelParams->optsStatutPart(),['prompt'=>'Choisir un statut']) ?>
        </div>
    </div>
    
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Update'), ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
