<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Personnes */

$this->title = Yii::t('app', 'Create Client');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Client'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="personnes-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'modelParams' => $modelParams,
        'dataInterlocuteurs' => $dataInterlocuteurs,
        'selectedInterlocuteurs' => $selectedInterlocuteurs,
    ]) ?>

</div>
