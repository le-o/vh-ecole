<?php

use webvimark\modules\UserManagement\models\User;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\Url;
use kartik\export\ExportMenu;

ini_set('memory_limit', '-1');

/* @var $this yii\web\View */
/* @var $searchModel app\models\PersonnesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->registerCss('.table-responsive {overflow-x: visible;}');

$this->title = ($isMoniteur) ? Yii::t('app', 'Mes cours comme moniteur') : Yii::t('app', 'Heures Moniteurs');
$this->params['breadcrumbs'][] = Yii::t('app', 'Personnes');
$this->params['breadcrumbs'][] = $this->title;

// On créé les colonnes ici, comme ca réutilisable dans l'export et la gridview
$gridColumnsBegin = [
    ['class' => 'kartik\grid\SerialColumn'],

    [
        'attribute' => 'no_cresus',
        'format' => 'raw',
    ],
    'nom',
    'prenom',
];
$gridColumnsMiddle = [
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
        'template'=>'{view} {listeHeures}',
        'visibleButtons'=>[
            'view' => User::canRoute(['/personnes/view']),
            'listeHeures' => User::canRoute(['/personnes/viewmoniteur']),
        ],
        'buttons'=> [
            'view' => function ($url) {
                $url .= '&tab=moniteur';
                return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                    'title' => Yii::t('app', 'Voir'),
                ]);
            },
            'listeHeures' => function ($url, $model, $key) use ($fromData) {
                return Html::a('<span class="glyphicon glyphicon-calendar"></span>', Url::to(['viewmoniteur', 'id' => $key, 'fromData' => $fromData]), [
                    'title' => Yii::t('app', 'Voir les heures'),
                ]);
            },
        ],
    ],
];
$gridColumns = array_merge ($gridColumnsBegin, $gridColumnsMiddle, $gridColumnsHours, $gridColumnsEnd);
$gridColumnsExport = array_merge(
    $gridColumnsBegin,
    ['adresse1', 'adresse2', 'npa', 'date_naissance', [
            'attribute' => 'fk_langues', 'label' => Yii::t('app', 'Langues parlées')
    ], 'email:email', 'telephone'],
    $gridColumnsMiddle,
    $gridColumnsHours,
    $gridColumnsEnd
);
$lastColumnIndex = count($gridColumnsExport)-1;
$baremeColumnIndexes = range(11, count($gridColumnsHours)+12); // +12 pour ajouter la colonne heure
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
                'batchSize' => 50,
                'target' => ExportMenu::TARGET_SELF,
                'showConfirmAlert' => false,
                'showColumnSelector' => true,
                'selectedColumns' => array_merge([1,2,3,7], $baremeColumnIndexes),
                'noExportColumns' => [$lastColumnIndex],
                'columnBatchToggleSettings' => [
                    'label' => Yii::t('app', 'Tous/aucun'),
                ],
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
