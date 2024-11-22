<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Cours */

$this->title = Yii::t('app', 'Create Cours');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cours-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'alerte' => $alerte,
        'model' => $model,
        'modelParams' => $modelParams,
    ]) ?>

</div>
