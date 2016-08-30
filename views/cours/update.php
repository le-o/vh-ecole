<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Cours */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Cours',
]) . ' ' . $model->fkNom->nom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->cours_id, 'url' => ['view', 'id' => $model->cours_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="cours-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
	    'alerte' => $alerte,
        'model' => $model,
        'modelParams' => $modelParams,
    ]) ?>

</div>
