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

$this->title = Yii::t('app', 'Liste clients actifs');
$this->params['breadcrumbs'][] = $this->title;

$script = "$(document).on('click', '.showModalButton', function(){
    if ($('#modal').data('bs.modal').isShown) {
        var keys = $('#personnegrid').yiiGridView('getSelectedRows');
        $.post({
            url: '".Url::toRoute('/personnes/setemail')."',
            dataType: 'json',
            data: {keylist: keys, allEmails: '".implode(', ', $listeEmails)."'},
            success: function(data) {
                $('#checkedEmails').attr('value', data.emails);
                $('#item').html(data.emails);
            },
        });
    }
});";
$this->registerJs($script, View::POS_END);
$this->registerJs('$("#toggleEmail").click(function() { $( "#item" ).toggle(); });', View::POS_END);

// On créé les colonnes ici, comme ca réutilisable dans l'export et la gridview
$gridColumns = [
    ['class' => 'kartik\grid\CheckboxColumn'],
    ['class' => 'kartik\grid\SerialColumn'],

    'personne_id',
    'cours_info',
    'nom',
    'prenom',
    [
        'label' => Yii::t('app', 'Suivi client'),
        'attribute' => 'suivi_client',
    ],
    'age',
    'adresse1',
    'adresse2',
    'npa',
    'localite',
    'email',
    'telephone',

    ['class' => 'yii\grid\ActionColumn',
        'template'=>'{partView} {partUpdate}',
        'buttons'=>[
            'partView' => function ($url, $data) {
                return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to(['/personnes/view', 'id' => $data['personne_id']]), [
                        'title' => Yii::t('app', 'Voir la personne'),
                    ]);
            },
            'partUpdate' => function ($url, $data) {
                $from['url'] = 'cours-date/actif';
                if (isset($_GET['page'])) {
                    $from['page'] = $_GET['page'];
                }
                $from = json_encode($from);
                return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Url::to(['/clients-has-cours-date/update', 'fk_personne' => $data['personne_id'], 'fk_cours' => $data['cours_id'], 'from' => $from]), [
                    'title' => Yii::t('app', 'Modifier statut'),
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
    
    <div class="row">
        <div class="col-sm-2">
            <?php $form = ActiveForm::begin(['options' => ['style' => 'display:inline;']]); ?>
            <?php Modal::begin([
                'id' => 'modal',
                'header' => '<h3>'.Yii::t('app', 'Contenu du message à envoyer').'</h3>',
                'toggleButton' => ['label' => Yii::t('app', 'Envoyer un email'), 'class' => 'btn btn-default showModalButton'],
            ]);

            echo '<a id="toggleEmail" href="#">'.Yii::t('app', 'Voir email(s)').'</a>';
            echo '<div id="item" style="display:none;">';
            echo implode(', ', $listeEmails);
            echo '</div>';

            echo Html::hiddenInput('checkedEmails', implode(', ', $listeEmails), ['id'=>'checkedEmails']);

            echo $form->field($parametre, 'parametre_id')->dropDownList(
                $emails,
                ['onchange'=>"$.ajax({
                    type: 'POST',
                    cache: false,
                    url: '".Url::toRoute('/parametres/getemail')."',
                    data: {id: $(this).val()},
                    dataType: 'json',
                    success: function(response) {
                        $.each( response, function( key, val ) {
                            $('#parametres-nom').attr('value', val.sujet);
                            $('.redactor-editor').html(val.contenu);
                            $('#parametres-valeur').val(val.contenu);
                        });
                    }
                });return false;",
            ])->label(Yii::t('app', 'Modèle'));

            echo $form->field($parametre, 'nom')->textInput()->label(Yii::t('app', 'Sujet'));
            echo $form->field($parametre, 'valeur')->widget(\yii\redactor\widgets\Redactor::className())->label(Yii::t('app', 'Texte'));

            echo Html::submitButton(Yii::t('app', 'Envoyer'), ['class' => 'btn btn-primary']);
            Modal::end(); ?>
            <?php ActiveForm::end(); ?>
        </div>

        <?php $searchForm = ActiveForm::begin([
            'action' => ['actif'],
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
        <div class="col-sm-3">
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
        'id' => 'personnegrid',
        'dataProvider' => $dataProvider,
        'columns' => $gridColumns,
        'summary' => '',
        'tableOptions' => ['class' => 'cours-date-liste']
    ]); ?>

</div>
