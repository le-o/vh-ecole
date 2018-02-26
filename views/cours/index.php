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
        <?= Html::a(Yii::t('app', 'Create Cours'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function($model) {
            if (in_array($model->fk_type, Yii::$app->params['coursPlanifieS']) && $model->nombreClientsInscrits >= $model->participant_max) return ['class' => 'warning'];
            if ($model->is_actif == false) return ['class' => 'danger'];
            return [];
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

//            [
//                'attribute' => 'cours_id',
//                'contentOptions'=>['style'=>'width:80px;']
//            ],
            [
                'attribute' => 'is_actif',
                'value' => function ($data) {
                    return ($data->is_actif) ? 'Oui' : 'Non';
                },
                'filter' => ['1'=>'Oui', '0'=>'Non'],
            ],
            [
                'attribute' => 'fkType',
                'value' => 'fkType.nom',
                'label' => Yii::t('app', 'Type'),
            ],
            [
                'attribute' => 'fkNom',
                'value' => 'fkNom.nom',
                'label' => Yii::t('app', 'Nom'),
            ],
            [
                'attribute' => 'fkNiveau',
                'value' => 'fkNiveau.nom',
                'label' => Yii::t('app', 'Niveau'),
            ],
            [
                'attribute' => 'fkJoursNoms',
                'label' => Yii::t('app', 'Jours'),
            ],
            [
                'attribute' => 'fkSaison',
                'value' => 'fkSaison.nom',
                'label' => Yii::t('app', 'Saison'),
            ],
            'session',
            'participant_min',
            'participant_max',
            [
                'label' => Yii::t('app', 'Nb Part'),
                'value' => function ($model) {
                    return (in_array($model->fk_type, Yii::$app->params['coursPlanifieS'])) ? $model->nombreClientsInscritsForDataGrid : Yii::t('app', 'n/a');
                }
            ],
            // 'description:ntext',

            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{view} {delete}',
            ],
        ],
    ]); ?>

</div>
