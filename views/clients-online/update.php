<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Clients Online',
]) . $model->client_online_id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Clients Onlines'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->client_online_id, 'url' => ['view', 'id' => $model->client_online_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="clients-online-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'modelsClient' => $modelsClient,
        'dataCours' => $dataCours,
        'selectedCours' => $selectedCours,
    ]) ?>

</div>
