<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Modal;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PersonnesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Moniteurs');
$this->params['breadcrumbs'][] = Yii::t('app', 'Outils');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="personnes-moniteurs">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'selectedCours' => $selectedCours,
        'dataCours' => $dataCours,
        'selectedLangue' => $selectedLangue,
        'dataLangues' => $dataLangues,
        'searchFrom' => $searchFrom,
        'searchTo' => $searchTo,
    ]); ?>

    <?= GridView::widget([
        'dataProvider' => $moniteursProvider,
        'showFooter' => true,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'statut',
            'type',
            'societe',
            'nom',
            'prenom',
            'localite',
            [
                'attribute' => 'fk_langues',
                'label' => Yii::t('app', 'Langues parlées'),
            ],
            'email:email',
            'telephone',
            [
                'attribute' => 'heures',
                'footer' => '<div style="text-align:right; font-weight:bold;">'.$heuresTotal.'</div>',
                'contentOptions' => ['style' => 'text-align:right;']
            ],
            
            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{view} {update} {listeHeures}',
                'buttons'=>[
                    'listeHeures' => function ($url, $model, $key) use ($fromData) {
                        return Html::a('<span class="glyphicon glyphicon-calendar"></span>', Url::to(['viewmoniteur', 'id' => $key, 'fromData' => $fromData]), [
                            'title' => Yii::t('app', 'Voir les heures'),
                        ]);
                    },
                ],
            ],
            
        ],
    ]); ?>

</div>
