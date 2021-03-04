<?php

use yii\helpers\Html;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */

$arrayForm = [
    'model' => $model,
    'modelsClient' => $modelsClient,
    'dataCours' => $dataCours,
    'selectedCours' => $selectedCours,
    'params' => $params,
];
if (isset($choixAge)) {
    $arrayForm['choixAge'] = $choixAge;
}
?>
<div class="clients-online-create">

    <?php if (Yii::$app->session->hasFlash('alerte')) {
        $alerte = Yii::$app->session->getFlash('alerte');
        echo Alert::widget([
            'options' => [
                'class' => 'alert-'.$alerte['type'],
            ],
            'body' => $alerte['info'],
        ]);
    } ?>

    <?= $this->render($displayForm, $arrayForm) ?>

</div>
