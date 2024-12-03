<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Modal;
use yii\bootstrap\Alert;
use yii\web\View;
use leo\modules\UserManagement\models\User;
use kartik\export\ExportMenu;
use kartik\date\DatePicker;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CoursDateSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Liste des moniteurs - Exportation ASSE');
$this->params['breadcrumbs'][] = $this->title;

// On créé les colonnes ici, comme ca réutilisable dans l'export et la gridview
$gridColumns = [
    [
        'label' => 'Kletteranlage',
        'value' => function($model) {
            if (isset($model->fkPersonne->fk_salle_admin)) {
                return 'Vertic-Halle - ' . $model->fkPersonne->fkSalleadmin->nom;
            }
            return '';
        }
    ],
    [
        'label' => 'Rolle',
        'value' => function($model) {
            return $model->moniteursRole;
        }
    ],
    [
        'label' => 'Status',
        'value' => function($model) {
            if (isset($model->fkPersonne->fk_type)) {
                return Yii::t('app', $model->fkPersonne->fkType->nom, [], 'de-CH');
            }
            return '';
        }
    ],
    ['label' => 'Name', 'attribute' => 'fkPersonne.nom'],
    ['label' => 'Vorname', 'attribute' => 'fkPersonne.prenom'],
    [
        'label' => 'Strasse',
        'value' => function($model) {
            return $model->fkPersonne->adresse1  . ' ' . $model->fkPersonne->numeroRue;
        }
    ],
    ['label' => 'PLZ', 'attribute' => 'fkPersonne.npa'],
    ['label' => 'Ort', 'attribute' => 'fkPersonne.localite'],
    [
        'label' => 'Geschlecht',
        'value' => function($model) {
            if (isset($model->fkPersonne->fk_sexe)) {
                return Yii::t('app', $model->fkPersonne->fkSexe->nom, [], 'de-CH');
            }
            return '';
        }
    ],
    ['label' => 'Geb. Datum', 'attribute' => 'fkPersonne.date_naissance'],
    ['label' => 'Tel. ', 'attribute' => 'fkPersonne.telephone'],
    ['label' => 'email', 'attribute' => 'fkPersonne.email'],
    [
        'label' => 'Prüfung',
        'value' => function($model) {
            return $model->moniteursExamDate;
        }
    ],
    ['label' => 'Bemerkung', 'attribute' => 'remarque'],
];

foreach ($formations as $key => $f) {
    $gridColumns[] = [
        'label' => $f,
        'value' => function($model) use ($key) {
            return ($model->checkMoniteursHasOneFormation($model->moniteur_id, $key) ? 'OK' : 'FAUX');
        },
    ];
}
$exportFilename = 'for-asse-' . date('Y-m-d_H-i-s');
?>

<?php if (!empty($alerte)) {
    echo Alert::widget([
        'options' => [
            'class' => 'alert-'.$alerte['class'],
        ],
        'body' => $alerte['message'],
    ]);
} ?>

<div class="export-asse-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <br />

    <?php if (User::hasRole(['admin', 'gestion'])) { ?>
        <div style="margin-bottom: 10px;">
            <?php
            // Renders a export dropdown menu
            echo ExportMenu::widget([
                'dataProvider' => $dataProvider,
                'columns' => $gridColumns,
                'target' => ExportMenu::TARGET_SELF,
                'showConfirmAlert' => false,
                'showColumnSelector' => false,
                'columnBatchToggleSettings' => [
                    'label' => Yii::t('app', 'Tous/aucun'),
                ],
                'filename' => 'for-js-' . date('Y-m-d_H-i-s'),
                'noExportColumns' => [14],
                'dropdownOptions' => [
                    'class' => 'btn btn-default',
                    'label' => Yii::t('app', 'Exporter tous'),
                ],
                'exportConfig' => [
                    ExportMenu::FORMAT_HTML => false,
                    ExportMenu::FORMAT_TEXT => [
                        'label' => 'CSV For ASSE',
                        'icon' => 'glyphicon glyphicon-floppy-open',
                        'options' => ['title' => 'Semicolon Separated Values'],
                        'delimiter' => ';',
                        'mime' => 'application/csv',
                        'extension' => 'csv',
                    ],
                    ExportMenu::FORMAT_PDF => false,
                    ExportMenu::FORMAT_EXCEL_X => [
                        'label' => 'Excel 2007+',
                        'icon' => 'glyphicon glyphicon-floppy-open',
                        'iconOptions' => ['class' => 'text-success'],
                        'linkOptions' => [],
                        'options' => ['title' => 'Microsoft Excel 2007+ (xlsx)'],
                        'alertMsg' => 'The EXCEL 2007+ (xlsx) export file will be generated for download.',
                        'mime' => 'application/application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'extension' => 'xlsx',
                        'writer' => ExportMenu::FORMAT_EXCEL_X
                    ],
                    ExportMenu::FORMAT_CSV => false,
                ]
            ]);
            ?>
        </div>
    <?php } ?>

    <?= GridView::widget([
        'id' => 'moniteurgrid',
        'dataProvider' => $dataProvider,
        'columns' => $gridColumns,
        'summary' => '',
        'tableOptions' => ['class' => 'moniteurs-liste']
    ]); ?>

</div>
