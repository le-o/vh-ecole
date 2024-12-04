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

$this->title = Yii::t('app', 'Liste des moniteurs - Exportation ASSE et MontagnePro');
$this->params['breadcrumbs'][] = $this->title;
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
                'columns' => $gridColumnsASSE,
                'target' => ExportMenu::TARGET_SELF,
                'showConfirmAlert' => false,
                'showColumnSelector' => false,
                'filename' => 'for-asse-' . date('Y-m-d_H-i-s'),
                'noExportColumns' => [14],
                'dropdownOptions' => [
                    'class' => 'btn btn-default',
                    'label' => Yii::t('app', 'Export ASSE'),
                ],
                'exportConfig' => [
                    ExportMenu::FORMAT_HTML => false,
                    ExportMenu::FORMAT_TEXT => [
                        'label' => 'CSV for ASSE',
                        'icon' => 'glyphicon glyphicon-floppy-open',
                        'options' => ['title' => 'Semicolon Separated Values'],
                        'delimiter' => ';',
                        'mime' => 'application/csv',
                        'extension' => 'csv',
                    ],
                    ExportMenu::FORMAT_PDF => false,
                    ExportMenu::FORMAT_EXCEL => false,
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
            echo ExportMenu::widget([
                'dataProvider' => $dataProvider,
                'columns' => $gridColumnsMP,
                'target' => ExportMenu::TARGET_SELF,
                'showConfirmAlert' => false,
                'showColumnSelector' => false,
                'filename' => 'for-mp-' . date('Y-m-d_H-i-s'),
                'noExportColumns' => [14],
                'dropdownOptions' => [
                    'class' => 'btn btn-default',
                    'label' => Yii::t('app', 'Export MontagnePro'),
                ],
                'exportConfig' => [
                    ExportMenu::FORMAT_HTML => false,
                    ExportMenu::FORMAT_TEXT => [
                        'label' => 'CSV for MontagnePro',
                        'icon' => 'glyphicon glyphicon-floppy-open',
                        'options' => ['title' => 'Semicolon Separated Values'],
                        'delimiter' => ';',
                        'mime' => 'application/csv',
                        'extension' => 'csv',
                    ],
                    ExportMenu::FORMAT_PDF => false,
                    ExportMenu::FORMAT_EXCEL => false,
                    ExportMenu::FORMAT_EXCEL_X => false,
                    ExportMenu::FORMAT_CSV => false,
                ]
            ]);
            ?>
        </div>
    <?php } ?>

    <?= GridView::widget([
        'id' => 'moniteurgrid',
        'dataProvider' => $dataProvider,
        'columns' => $gridColumnsASSE,
        'summary' => '',
        'tableOptions' => ['class' => 'moniteurs-liste']
    ]); ?>

</div>
