<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\Personnes */

$this->title = $model->nom.' '.$model->prenom;
$this->params['breadcrumbs'][] = Yii::t('app', 'Outils');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Moniteurs'), 'url' => ['personnes/moniteurs']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?php if (!empty($alerte)) {
    echo Alert::widget([
        'options' => [
            'class' => 'alert-'.$alerte['class'],
        ],
        'body' => $alerte['message'],
    ]); 
} ?>

<div class="personnes-view">

    <h1><?= Html::encode($this->title) ?></h1>
    <table class="table">
        <caption>
            <div class="row">
                <div class="col-sm-6"><?= Yii::t('app', 'Mes cours comme moniteurs') ?> <?= Yii::t('app', 'du').' '.date('d.m.Y', strtotime($fromData['searchFrom'])).' '.Yii::t('app', 'au').' '.date('d.m.Y', strtotime($fromData['searchTo'])) ?>
                    &nbsp;<?= Html::a(Yii::t('app', 'Imprimer'), ['viewmoniteur', 'id' => $model->personne_id, 'fromData' => serialize($fromData), 'print' => true], ['class' => 'btn btn-default hide-print']) ?></div>
            </div>
        </caption>
    </table>
    
    <?php
    echo $this->render('/cours-date/_moniteur', [
        'coursDateDataProvider' => $coursDateDataProvider,
        'withSum' => true,
        'sum' => $sum,
    ]);
    ?>

</div>
