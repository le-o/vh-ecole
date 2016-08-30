<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Parametres */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Parametres',
]) . ' ' . $model->nom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Parametres'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->parametre_id, 'url' => ['view', 'id' => $model->parametre_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="parametres-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
