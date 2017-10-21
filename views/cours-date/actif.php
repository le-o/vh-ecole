<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Modal;
use yii\bootstrap\Alert;
use yii\web\View;

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
    'statutPart',
    [
        'label' => 'Cours',
        'value' => function($data) {
            return $data['nomCours'].' '.$data['niveauCours'];
        }
    ],
    'nom',
    'prenom',
    'suivi_client',
    'age',

    ['class' => 'yii\grid\ActionColumn',
        'template'=>'{partUpdate}',
        'buttons'=>[
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
        <div class="col-sm-6">
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
    </div>
    <br />
    <?= GridView::widget([
        'id' => 'personnegrid',
        'dataProvider' => $dataProvider,
        'columns' => $gridColumns,
        'summary' => '',
        'tableOptions' => ['class' => 'cours-date-liste']
    ]); ?>

</div>
