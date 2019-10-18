<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Modal;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use yii\web\View;
use webvimark\modules\UserManagement\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PersonnesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Clients');
$this->params['breadcrumbs'][] = Yii::t('app', 'Personnes');
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
?>

<?php if (!empty($alerte)) {
    echo Alert::widget([
        'options' => [
            'class' => 'alert-'.$alerte['class'],
        ],
        'body' => $alerte['message'],
    ]);
} ?>


<div class="personnes-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <div class="row">
        <div class="col-sm-6">
            <?= Html::a(Yii::t('app', 'Create Client'), ['create'], ['class' => 'btn btn-success']) ?>

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

    <?= GridView::widget([
        'id' => 'personnegrid',
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\CheckboxColumn'],
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'fk_statut',
                'value' => 'fkStatut.nom',
                'filter' => $typeStatut,
            ],
            [
                'attribute' => 'fk_type',
                'value' => 'fkType.nom',
                'filter' => $typeFilter,
            ],
            'suivi_client',
            'societe',
            'nom',
            'prenom',
            'localite',
            'email:email',
            'telephone',
            
            ['class' => 'yii\grid\ActionColumn',
                'visibleButtons'=>[
                    'update' => User::canRoute(['personnes/update']),
                    'delete' => User::canRoute(['personnes/delete']),
                ],
            ],
        ],
    ]); ?>

</div>
