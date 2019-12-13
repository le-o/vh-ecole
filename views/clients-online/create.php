<?php

use yii\helpers\Html;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */
?>
<div class="clients-online-create">
    
    <h3><?= Yii::t('app', 'Attention : pour les inscriptions à - de 72h , merci de nous contacter par téléphone') ?></h3>
    <br /><br />
    
    <?php if ($alerte != '') {
        echo Alert::widget([
            'options' => [
                'class' => 'alert-danger',
            ],
            'body' => $alerte,
        ]); 
    } ?>

    <?= $this->render('_form', [
        'model' => $model,
        'modelsClient' => $modelsClient,
        'dataCours' => $dataCours,
        'selectedCours' => $selectedCours,
        'params' => $params,
    ]) ?>

</div>
