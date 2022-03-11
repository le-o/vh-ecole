<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MoniteursHasBareme */

$this->title = Yii::t('app', 'Ajouter barème: {name}', [
    'name' => $model->fkPersonne->nomPrenom,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Moniteurs'), 'url' => ['/personnes/moniteurs']];
$this->params['breadcrumbs'][] = ['label' => $model->fkPersonne->nomPrenom, 'url' => ['/personnes/view', 'id' => $model->fk_personne]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Ajouter barème');
?>
<div class="moniteurs-has-bareme-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'modelParams' => $modelParams,
    ]) ?>

</div>
