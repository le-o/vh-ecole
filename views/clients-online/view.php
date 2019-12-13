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
        <div class="row">
            <div class="col-md-<?= ($doublon !== null) ? '6' : '12' ?>">
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
            </div>
            <?php if ($doublon !== null && $model->fk_parent == '') : ?>
                <div class="col-md-6">
                    <?php echo Html::a(Yii::t('app', 'Fusionner les dossiers'), ['fusionclient', 'idClientOnline' => $model->client_online_id, 'idPersonne' => $doublon->personne_id], ['class' => 'btn btn-warning']); ?>
                </div>
            <?php endif; ?>
        </div>
    </p>

    <div class="row">
        <div class="col-md-<?= ($doublon !== null) ? '6' : '12' ?>">
            <h2>Client online</h2>
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'client_online_id',
                    'fk_parent',
                    [
                        'label' => Yii::t('app', 'Nom cours'),
                        'value' => function ($model) {
                            return ($model->fkCours) ? $model->fkCours->fkNom->nom : $model->fkCoursNom->nom;
                        }
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
        <?php if ($doublon !== null) : ?>
            <div class="col-md-6">
                <h2>Possible doublon détecté</h2>
                <?= DetailView::widget([
                    'model' => $doublon,
                    'attributes' => [
                        'personne_id',
                        'suivi_client',
                        'nom',
                        'prenom',
                        'adresse1',
                        'npa',
                        'localite',
                        'telephone',
                        'email:email',
                        'date_naissance',
                        'informations:ntext',
                    ],
                ]) ?>
            </div>
        <?php endif; ?>
    </div>

</div>
