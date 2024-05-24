<?php

use webvimark\modules\UserManagement\models\User;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\Url;
use kartik\export\ExportMenu;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PersonnesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->registerCss('.table-responsive {overflow-x: visible;}');

$this->title = ($isMoniteur) ? Yii::t('app', 'Mes cours comme moniteur') : Yii::t('app', 'Moniteurs');
$this->params['breadcrumbs'][] = Yii::t('app', 'Personnes');
$this->params['breadcrumbs'][] = $this->title;

// On créé les colonnes ici, comme ca réutilisable dans l'export et la gridview
$gridColumnsBegin = [
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
];
$gridColumnsMiddle = [
    [
        'attribute' => 'fk_langues',
        'label' => Yii::t('app', 'Langues parlées'),
    ],
    'email:email',
    'telephone',
    [
        'attribute' => 'fk_formation',
        'label' => Yii::t('app', 'Barème par défaut'),
    ]
];
foreach ($baremes as $key => $bareme) {
    $gridColumnsHours[] =
        [
            'attribute' => $bareme,
            'label' => Yii::t('app', $bareme),
        ];
}
$gridColumnsEnd = [
    [
        'attribute' => 'heures',
        'footer' => '<div style="text-align:right; font-weight:bold;">'.$heuresTotal.'</div>',
        'contentOptions' => ['style' => 'text-align:right;']
    ],

    ['class' => 'yii\grid\ActionColumn',
        'template'=>'{view} {update} {listeHeures}',
        'visibleButtons'=>[
            'view' => User::canRoute(['/personnes/view']),
            'update' => User::canRoute(['/personnes/update']),
            'listeHeures' => User::canRoute(['/personnes/viewmoniteur']),
        ],
        'buttons'=>[
            'listeHeures' => function ($url, $model, $key) use ($fromData) {
                return Html::a('<span class="glyphicon glyphicon-calendar"></span>', Url::to(['viewmoniteur', 'id' => $key, 'fromData' => $fromData]), [
                    'title' => Yii::t('app', 'Voir les heures'),
                ]);
            },
        ],
    ],
];
$gridColumns = array_merge ($gridColumnsBegin, $gridColumnsMiddle, $gridColumnsHours, $gridColumnsEnd);
$gridColumnsExport = array_merge($gridColumnsBegin, ['adresse1', 'adresse2', 'npa', 'date_naissance'], $gridColumnsMiddle, $gridColumnsHours, $gridColumnsEnd);
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
        'isMoniteur' => $isMoniteur,
    ]); ?>
    
    <?php if (User::hasRole(['admin', 'gestion'])) { ?>
        <div style="margin-bottom: 10px;">
            <?php
            // Renders a export dropdown menu
            echo ExportMenu::widget([
                'dataProvider' => $moniteursProvider,
                'columns' => $gridColumnsExport,
                'target' => ExportMenu::TARGET_SELF,
                'showConfirmAlert' => false,
                'showColumnSelector' => true,
                'columnBatchToggleSettings' => [
                    'label' => Yii::t('app', 'Tous/aucun'),
                ],
                'noExportColumns' => [12],
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
