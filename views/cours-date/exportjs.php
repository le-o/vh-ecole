<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Modal;
use yii\bootstrap\Alert;
use yii\web\View;
use webvimark\modules\UserManagement\models\User;
use kartik\export\ExportMenu;
use kartik\date\DatePicker;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CoursDateSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Liste clients actifs - Exportation JS');
$this->params['breadcrumbs'][] = $this->title;

// On créé les colonnes ici, comme ca réutilisable dans l'export et la gridview
$gridColumns = [
    ['label' => 'N° PERSONNEL', 'attribute' => 'nopersonnel'],
    ['label' => 'NOM', 'attribute' => 'nom'],
    ['label' => 'PRENOM', 'attribute' => 'prenom'],
    ['label' => 'DATE DE NAISSANCE', 'attribute' => 'date_naissance'],
    ['label' => 'SEXE', 'attribute' => 'fkSexe.nom'],
    ['label' => 'N_AVS', 'attribute' => 'no_avs'],
    ['label' => 'PEID'],
    ['label' => 'NATIONALITE', 'attribute' => 'fkNationalite.nom'],
    ['label' => 'LANGUE MATERNELLE', 'attribute' => 'fkLangueMat.valeur'],
    ['label' => 'RUE', 'attribute' => 'rue'],
    ['label' => 'NUMERO', 'attribute' => 'numero'],
    ['label' => 'NPA', 'attribute' => 'npa'],
    ['label' => 'LOCALITE', 'attribute' => 'localite'],
    ['label' => 'PAYS', 'attribute' => 'fkPays.nom'],
];
$exportFilename = 'for-js-' . date('Y-m-d_H-i-s');
$nbColumnToExport = 14;
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
    
    <div class="row">

        <?php $searchForm = ActiveForm::begin([
            'action' => [$view],
            'method' => 'get',
        ]); ?>
        <div class="col-sm-3">
            <?= Select2::widget([
                'name' => 'list_cours',
                'value' => $selectedCours, // initial value
                'data' => $dataCours,
                'options' => ['placeholder' => Yii::t('app', 'Choisir un/des cours ...'), 'multiple' => true],
                'pluginOptions' => [
                    'allowClear' => true,
                    'tags' => true,
                ],
            ]); ?>
        </div>
        <div class="col-sm-2">
            <?php echo DatePicker::widget([
                'model' => $searchModel,
                'attribute' => 'depuis',
                'attribute2' => 'dateA',
                'options' => ['placeholder' => Yii::t('app', 'Date début')],
                'options2' => ['placeholder' => Yii::t('app', 'Date fin')],
                'type' => DatePicker::TYPE_RANGE,
                'separator' => '&nbsp;'.Yii::t('app', ' à ').'&nbsp;',
                'form' => $searchForm,
                'pluginOptions' => [
                    'format' => 'dd.mm.yyyy',
                    'autoclose' => true,
                ]
            ]); ?>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
                <?= Html::a(Yii::t('app', 'Reset'), ['cours-date/actif'], ['class'=>'btn btn-default']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
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
                        'label' => 'CSV For JS',
                        'icon' => 'glyphicon glyphicon-floppy-open',
                        'options' => ['title' => 'Semicolon Separated Values'],
                        'delimiter' => ';',
                        'mime' => 'application/csv',
                        'extension' => 'csv',
                    ],
                    ExportMenu::FORMAT_PDF => false,
                    ExportMenu::FORMAT_EXCEL_X => false,
                    ExportMenu::FORMAT_CSV => false,
                ]
            ]);
            ?>
        </div>
    <?php } ?>

    <?= GridView::widget([
        'id' => 'personnegrid',
        'dataProvider' => $dataProvider,
        'columns' => $gridColumns,
        'summary' => '',
        'tableOptions' => ['class' => 'cours-date-liste']
    ]); ?>

</div>
