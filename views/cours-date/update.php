<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\CoursDate */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Cours Date',
]) . ' ' . $model->fkCours->fkNom->nom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours'), 'url' => ['/cours/view', 'id' => $model->fk_cours]];
$this->params['breadcrumbs'][] = ['label' => $model->cours_date_id, 'url' => ['view', 'id' => $model->cours_date_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="cours-date-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
	    'alerte' => $alerte,
        'model' => $model,
        'dataCours' => $dataCours,
        'dataMoniteurs' => $dataMoniteurs,
        'selectedMoniteurs' => $selectedMoniteurs,
    ]) ?>

</div>
