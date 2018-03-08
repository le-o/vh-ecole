<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Modal;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use yii\web\View;
use kartik\export\ExportMenu;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PersonnesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Moniteurs');
$this->params['breadcrumbs'][] = Yii::t('app', 'Personnes');
$this->params['breadcrumbs'][] = $this->title;

// On créé les colonnes ici, comme ca réutilisable dans l'export et la gridview
$gridColumns = [
    ['class' => 'kartik\grid\SerialColumn'],
    
    'statut',
    'type',
    [
        'attribute' => 'societe',
        'format' => 'html',
    ],
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
];
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
    
    <?php if (Yii::$app->user->identity->id < 1000) { ?>
        <div style="margin-bottom: 10px;">
            <?php
            // Renders a export dropdown menu
            echo ExportMenu::widget([
                'dataProvider' => $moniteursProvider,
                'columns' => $gridColumns,
                'target' => ExportMenu::TARGET_SELF,
                'showConfirmAlert' => false,
                'showColumnSelector' => true,
                'columnBatchToggleSettings' => [
                    'label' => Yii::t('app', 'Tous/aucun'),
                ],
                'noExportColumns' => [10],
                'dropdownOptions' => [
                    'class' => 'btn btn-default',
                    'label' => Yii::t('app', 'Exporter tous'),
                ],
                'exportConfig' => [
                    ExportMenu::FORMAT_HTML => false,
                    ExportMenu::FORMAT_TEXT => false,
                    ExportMenu::FORMAT_PDF => false,
                    ExportMenu::FORMAT_EXCEL_X => false,
                ]
            ]);
            ?>
        </div>
    <?php } ?>

    <?= GridView::widget([
        'dataProvider' => $moniteursProvider,
        'showFooter' => true,
        'columns' => $gridColumns,
    ]); ?>

</div>
