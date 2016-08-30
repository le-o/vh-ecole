<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Personnes */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Personnes',
]) . ' ' . $model->nom.' '.$model->prenom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Personnes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->personne_id, 'url' => ['view', 'id' => $model->personne_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="personnes-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'modelParams' => $modelParams,
        'dataInterlocuteurs' => $dataInterlocuteurs,
        'selectedInterlocuteurs' => $selectedInterlocuteurs,
    ]) ?>

</div>
