<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use kartik\export\ExportMenu;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CoursDateSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Planification');
$this->params['breadcrumbs'][] = $this->title;

// On créé les colonnes ici, comme ca réutilisable dans l'export et la gridview
$gridColumns = [
    ['class' => 'kartik\grid\SerialColumn'],
    'date',
    'fkCours.fkNom.nom',
    'fkCours.session',
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
    'remarque',

    ['class' => 'yii\grid\ActionColumn',
        'template'=>'{coursDateView} {coursDateUpdate}',
        'visibleButtons'=>[
            'coursDateUpdate' => (Yii::$app->user->identity->id < 1000) ? true : false,
        ],
        'buttons'=>[
            'coursDateView' => function ($url, $model, $key) {
                if ($model->fkCours->fk_type == Yii::$app->params['coursPlanifie']) {
                    return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to(['/cours/view', 'id' => $model->fk_cours]), [
                        'title' => Yii::t('yii', 'View'),
                    ]);
                }
                return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to(['/cours-date/view', 'id' => $key]), [
                    'title' => Yii::t('yii', 'View'),
                ]);
            },
            'coursDateUpdate' => function ($url, $model, $key) {
                return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Url::to(['/cours-date/update', 'id' => $key]), [
                    'title' => Yii::t('yii', 'Update'),
                ]);
            },
        ],
    ],
];
?>
<div class="cours-date-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php echo $this->render('_search', ['model' => $searchModel]); ?>
    
    <?php if (Yii::$app->user->identity->id < 1000) { ?>
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
    ]); ?>

</div>
