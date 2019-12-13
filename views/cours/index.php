<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CoursSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Cours');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cours-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

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
            if ($model->is_actif == false) return ['class' => 'danger'];
            return [];
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'is_actif',
                'value' => function ($data) {
                    return ($data->is_actif) ? 'Oui' : 'Non';
                },
                'filter' => ['1'=>'Oui', '0'=>'Non'],
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
                'template'=>'{view} {delete}',
            ],
        ],
    ]); ?>

</div>
