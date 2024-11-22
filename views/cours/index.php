<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use webvimark\modules\UserManagement\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CoursSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Cours');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cours-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <div class="btn-toolbar" role="toolbar">
            <?= Html::a(Yii::t('app', 'Create Cours'), ['create'], ['class' => 'btn btn-success']) ?>
            <div class="btn-group">
                <?php foreach ($btnSalle as $s) { ?>
                    <?= Html::a(Yii::t('app', $s['label']), ['index', 'salle' => $s['salleID']], ['class' => 'btn btn-default' . $s['class']]) ?>
                <?php } ?>
            </div>
            <?= Html::a(Yii::t('app', 'Priorité internet'), ['index', 'onlyForWeb' => ($btnClassPriorise == '') ? true : false], ['class' => 'btn btn-default' . $btnClassPriorise]) ?>
        </div>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function($model) {
            if (in_array($model->fk_type, Yii::$app->params['coursPlanifieS']) && $model->getNombreClientsInscrits() >= $model->participant_max) return ['class' => 'warning'];
            if ($model->fk_statut == Yii::$app->params['coursInactif']) return ['class' => 'danger'];
            return [];
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'fkStatut',
                'value' => 'fkStatut.nom',
                'label' => Yii::t('app', 'Statut'),
                'filter' => $statutFilter,
                'headerOptions' => ['style' => 'width:125px;'],
            ],
            [
                'attribute' => 'fkSalle',
                'value' => 'fkSalle.nom',
                'label' => Yii::t('app', 'Salle'),
            ],
            [
                'attribute' => 'fkNom',
                'value' => 'fkNom.nom',
                'label' => Yii::t('app', 'Nom'),
            ],
            'session',
            [
                'attribute' => 'fkSaison',
                'value' => 'fkSaison.nom',
                'label' => Yii::t('app', 'Saison'),
                'filter' => $saisonFilter,
                'headerOptions' => ['style' => 'width:95px;'],
            ],
            'participant_min',
            'participant_max',
            [
                'label' => Yii::t('app', 'Nb Part'),
                'value' => function ($model) {
                    return (in_array($model->fk_type, Yii::$app->params['coursPlanifieS'])) ? $model->nombreClientsInscritsForDataGrid : Yii::t('app', 'n/a');
                }
            ],
            [
                'attribute' => 'fkType',
                'value' => 'fkType.nom',
                'label' => Yii::t('app', 'Type'),
            ],
            [
                'attribute' => 'fkJoursNoms',
                'label' => Yii::t('app', 'Jours'),
            ],

            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{view} {printParticipant} {delete}',
                'visibleButtons'=>[
                    'printParticipant' => User::canRoute(['/cours/presence']),
                    'delete' => User::canRoute(['/cours/delete']),
                ],
                'buttons'=>[
                    'printParticipant' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-print"></span>', Url::to(['/cours/presence', 'id' => $key]), [
                            'title' => Yii::t('app', 'Imprimer la liste des participants'),
                            'target' => '_blank',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

</div>
