<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\CoursDate */

$this->title = Yii::t('app', 'Create Cours Date');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours Dates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cours-date-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
	    'alerte' => $alerte,
        'model' => $model,
        'dataCours' => $dataCours,
        'dataMoniteurs' => $dataMoniteurs,
        'selectedMoniteurs' => $selectedMoniteurs,
    ]) ?>

</div>
