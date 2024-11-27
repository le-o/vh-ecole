<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\bootstrap\Alert;
use kartik\export\ExportMenu;
use leo\modules\UserManagement\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CoursDateSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Planification');
$this->params['breadcrumbs'][] = $this->title;

// On créé les colonnes ici, comme ca réutilisable dans l'export et la gridview
$gridColumns = [
    ['class' => 'kartik\grid\SerialColumn'],
    'date',
    [
        'label' => Yii::t('app', 'Nom du cours'),
        'attribute' => 'fkNom',
        'value' => 'fkCours.fkNom.nom',
    ],
    [
        'label' => Yii::t('app', 'Type'),
        'attribute' => 'fkTypeCours',
        'value' => 'fkCours.fkType.nom',
    ],
    [
        'attribute' => 'session',
        'value' => 'fkCours.session',
    ],
    [
        'label' => Yii::t('app', 'Salle'),
        'attribute' => 'fkSalle',
        'value' => 'fkCours.fkSalle.nom',
    ],
    [
        'label' => Yii::t('app', 'Fk Moniteur'),
        'value' => function($data) {
            $array_moniteurs = [];
            foreach ($data->coursHasMoniteurs as $moniteur) {
                $array_moniteurs[] = $moniteur->fkMoniteur->nom.' '.$moniteur->fkMoniteur->prenom;
            }
            return implode(', ', $array_moniteurs);
        },
    ],
    'heure_debut',
    [
        'label' => Yii::t('app', 'Heure Fin'),
        'value' => function($data) {
            return $data->heureFin;
        },
    ],
    'duree',
    [
        'attribute' => 'prix',
        'visible' => (Yii::$app->user->identity->id < 1100) ? true : false,
    ],
    [
        'label' => Yii::t('app', 'Nb Part'),
        'value' => function($data) {
            return $data->nombreClientsInscritsForDataGrid;
        },
    ],
    'remarque',

    ['class' => 'yii\grid\ActionColumn',
        'template'=>'{coursPresence} {coursDateView} {coursDateDelete}',
        'visibleButtons'=>[
            'coursPresence' => User::canRoute('/cours/presence'),
            'coursDateView' => User::canRoute('/cours-date/view'),
            'coursDateDelete' => User::canRoute('/cours-date/delete'),
        ],
        'buttons'=>[
            'coursPresence' => function ($url, $model, $key) {
                return Html::a('<span class="glyphicon glyphicon-print"></span>', Url::to(['/cours/presence', 'id' => $model->fk_cours]), [
                    'title' => Yii::t('yii', 'Imprimer'),
                ]);
            },
            'coursDateView' => function ($url, $model, $key) {
                return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to(['/cours-date/view', 'id' => $key]), [
                    'title' => Yii::t('yii', 'View'),
                ]);
            },
            'coursDateDelete' => function ($url, $model, $key) {
                return Html::a('<span class="glyphicon glyphicon-trash"></span>', Url::to(['/cours-date/delete', 'id' => $key, 'from' => '/cours-date/liste']), [
                    'title' => Yii::t('yii', 'Delete'),
                    'data' => [
                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]);
            },
        ],
    ],
];
?>

<?php if (!empty($alerte)) {
    echo Alert::widget([
        'options' => [
            'class' => 'alert-'.$alerte['class'],
        ],
        'body' => $alerte['message'],
    ]); 
} ?>

<div class="cours-date-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php echo $this->render('_search', [
            'model' => $searchModel,
            'dataTypeCours' => $dataTypeCours,
            'selectedTypeCours' => $selectedTypeCours,
            'dataSalle' => $dataSalle,
            'selectedSalle' => $selectedSalle,
        ]); ?>
    
    <?php if (User::canRoute(['/gridview/export'])) { ?>
        <div style="margin-bottom: 10px;">
            <?php
            // Renders a export dropdown menu
            echo ExportMenu::widget([
                'dataProvider' => $dataProvider,
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
        'dataProvider' => $dataProvider,
        'rowOptions' => function($model) {
            foreach ($model->coursHasMoniteurs as $moniteur) {
                if (in_array($moniteur->fk_moniteur, Yii::$app->params['sansEncadrant'])) return ['class' => 'info'];
            }
            return [];
        },
        'columns' => $gridColumns,
        'summary' => '',
        'tableOptions' => ['class' => 'cours-date-liste']
    ]); ?>

</div>
