<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ClientsOnlineSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Clients Onlines');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="clients-online-index">

    <h1><?= Html::encode($this->title) ?></h1>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function($model) {
            if ($model->is_actif == false) return ['class' => 'success'];
            return [];
        },
//        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'fk_parent',
            [
                'label' => Yii::t('app', 'Nom cours'),
                'value' => function ($model) {
                    return ($model->fkCours) ? $model->fkCours->fkNom->nom : $model->fkCoursNom->nom;
                }
            ],
            'nom',
            'prenom',
            // 'adresse',
            'npa',
            'localite',
            'telephone',
            'email:email',
            'date_naissance',
            'informations:ntext',
            'date_inscription',
            [
                'attribute' => 'is_actif',
                'value' => function ($model) {
                    return ($model->is_actif == true) ? Yii::t('app', 'non') : Yii::t('app', 'oui');
                }
            ],

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
