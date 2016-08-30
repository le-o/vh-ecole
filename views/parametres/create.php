<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Parametres */

$this->title = Yii::t('app', 'Create Parametres');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Parametres'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="parametres-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
