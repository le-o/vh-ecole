<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CoursDateSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Planification');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cours-date-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', 'Create Cours Date'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'Create Cours Date Multiple'), ['recursive'], ['class' => 'btn btn-info']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
			
            'date',
            [
                'attribute' => 'fkCours',
                'value' => 'fkCours.fkNom.nom',
            ],
            [
                'attribute' => 'fkLieu',
                'value' => 'fkLieu.nom',
            ],
            [
                'attribute' => 'participantMin',
                'value' => 'fkCours.participant_min',
            ],
            [
                'attribute' => 'participantMax',
                'value' => 'fkCours.participant_max',
            ],

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
