<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */

$this->title = $model->client_online_id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Clients Onlines'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="clients-online-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->client_online_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->client_online_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
        <?php if ($model->fk_parent != '') {
            echo Html::a(Yii::t('app', 'Voir interlocuteur'), ['view', 'id' => $model->fk_parent], ['class' => 'btn btn-link']);
        } else {
            echo Html::a(Yii::t('app', 'Transformer en client'), ['pushclient', 'id' => $model->client_online_id], ['class' => 'btn btn-default']);
        } ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'client_online_id',
            'fk_parent',
            [
                'attribute' => 'fkParametre.nom',
                'label' => Yii::t('app', 'Cours'),
            ],
            'nom',
            'prenom',
            'adresse',
            'npa',
            'localite',
            'telephone',
            'email:email',
            'date_naissance',
            'informations:ntext',
            'date_inscription',
        ],
    ]) ?>

</div>
