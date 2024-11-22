<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Moniteurs */

$this->title = Yii::t('app', 'Create Moniteurs');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Moniteurs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="moniteurs-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'alerte' => $alerte,
        'model' => $model,
    ]) ?>

</div>
