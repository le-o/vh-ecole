<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Moniteurs */

$this->title = Yii::t('app', 'Modification données moniteurs: {name}', [
    'name' => $model->fkPersonne->nomPrenom,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Personnes'), 'url' => ['/personnes']];
$this->params['breadcrumbs'][] = ['label' => $model->fkPersonne->nomPrenom, 'url' => ['/personnes/view', 'id' => $model->fk_personne, 'tab' => 'moniteur']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="moniteurs-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'alerte' => $alerte,
        'model' => $model,
    ]) ?>

</div>
