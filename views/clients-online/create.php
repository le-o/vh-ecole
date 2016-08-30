<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */
?>
<div class="clients-online-create">

    <?= $this->render('_form', [
        'model' => $model,
        'modelsClient' => $modelsClient,
        'dataCours' => $dataCours,
        'selectedCours' => $selectedCours,
    ]) ?>

</div>
