<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use kartik\select2\Select2;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use yii\web\View;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\CoursDate */
/* @var $form yii\widgets\ActiveForm */

$this->registerJs('$("#toggleEmail").click(function() { $( "#item" ).toggle(); });', View::POS_END);
?>

<style>
.flyover {
   /*left: 150%;*/
   overflow: hidden;
   position: fixed;
   width: 20%;
   opacity: 0.9;
   z-index: 1050;
   transition: left 0.6s ease-out 0s;
   display: none;
}
.modal.fade .modal-dialog {
    transform: translate(0px, -25%);
    transition: transform 0.3s ease-out 0s;
}
.modal.fade.in .modal-dialog {
    transform: translate(0px, 0px);
}
</style>

<div id="msg" class="alert alert-success flyover" role="alert"></div>

<div class="cours-participant-form">

    <?php if (Yii::$app->user->identity->id < 1000) { ?>
        <div class="row">
            <?php if ($isInscriptionOk) { ?>

                <?php $form = ActiveForm::begin(); ?>
                    <div class="col-sm-4">
                        <?= Select2::widget([
                            'name' => 'new_participant',
                            'data' => $dataClients,
                            'options' => ['placeholder' => Yii::t('app', 'Ajouter participant ...')],
                            'pluginOptions' => [
                                'allowClear' => true,
                                'tags' => true,
                                'style' => 'width: 80%;',
                            ],
                        ]); ?>
                    </div>
                    <div class="col-sm-1">
                        <?= Html::submitButton(Yii::t('app', 'Ajouter'), ['class' => 'btn btn-primary']); ?>
                    </div>
                <?php ActiveForm::end(); ?>
            <?php } ?>

            <?php if (!$forPresenceOnly) { ?>
                <?php $form = ActiveForm::begin(); ?>
                <div class="col-sm-4">
                    <?php Modal::begin([
                        'header' => '<h3>'.Yii::t('app', 'Contenu du message à envoyer').'</h3>',
                        'toggleButton' => ['label' => Yii::t('app', 'Envoyer un email'), 'class' => 'btn btn-default'],
                    ]);

                    echo '<a id="toggleEmail" href="#">'.Yii::t('app', 'Voir email(s)').'</a>';
                    echo '<div id="item" style="display:none;">';
                    echo implode(', ', $listeEmails);
                    echo '</div>';

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
                                });
                            }
                        });return false;",
                        ])->label(Yii::t('app', 'Modèle'));

                    echo $form->field($parametre, 'nom')->textInput()->label(Yii::t('app', 'Sujet'));
                    echo $form->field($parametre, 'valeur')->widget(\yii\redactor\widgets\Redactor::className())->label(Yii::t('app', 'Texte'));

                    echo Html::submitButton(Yii::t('app', 'Envoyer'), ['class' => 'btn btn-primary']);
                    Modal::end(); ?>

                    <?= Html::a(Yii::t('app', 'Imprimer'), ['/cours/presence', 'id' => (isset($model->cours_id) ? $model->cours_id : $model->fk_cours)], ['class' => 'btn btn-default']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            <?php } ?>
        </div>
    <?php } ?>
    
    <?= GridView::widget([
        'dataProvider' => $participantDataProvider,
        'id' => 'participantgrid',
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'fkStatut.nom',
                'label' => 'Statut',
                'visible' => (isset($model->fk_type) && $model->fk_type == Yii::$app->params['coursPlanifie']) ? true : false,
            ],
            'societe',
            'nom',
            'prenom',
            'age',
            [
                'attribute' => 'email',
                'visible' => (Yii::$app->user->identity->id < 1100) ? true : false,
            ],
            [
                'attribute' => 'telephone',
                'visible' => (Yii::$app->user->identity->id < 1100) ? true : false,
            ],
            [
                'label' => Yii::t('app', 'interlocuteur'),
                'format' => 'html',
                'value' => function ($data) {
                    return $data->getInterlocuteurs();
                }
            ],
            [
                'class' => 'yii\grid\CheckboxColumn',
                'header' => Yii::t('app', 'present?'),
                'visible' => $forPresenceOnly,
                'checkboxOptions' => function ($data, $key, $index, $column) use ($model, $forPresenceOnly) {
                    if ($forPresenceOnly) {
                        $bool = $data->getClientsHasOneCoursDate($model->cours_date_id);
                        return ['value' => $key, 'checked' => $bool->is_present];
                    }
                    return '';
                }
            ],
            
            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{partView} {partDeleteFutur} {partDelete}',
                'visibleButtons'=>[
                    'partView' => (Yii::$app->user->identity->id < 1100) ? true : false,
                    'partDeleteFutur' => (Yii::$app->user->identity->id < 1000) ? (isset($model->fk_type) ? $model->fk_type == Yii::$app->params['coursPlanifie'] : $model->fkCours->fk_type  == Yii::$app->params['coursPlanifie']) : false,
                    'partDelete' => (Yii::$app->user->identity->id < 1000) ? true : false,
                ],
                'buttons'=>[
                    'partView' => function ($model, $key, $index) {
                        if ($key->personne_id != '') {
                            return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to(['/personnes/view', 'id' => $key->personne_id]), [
                                'title' => Yii::t('app', 'Voir'),
                            ]);
                        }
                    },
                    'partDeleteFutur' => function ($model, $key, $index) use ($viewAndId) {
                        if ($key->personne_id != '') {
                            return Html::a('<span class="glyphicon glyphicon-remove-circle"></span>', Url::to(['/cours/participant-delete', 'personne_id' => $key->personne_id, 'cours_ou_date_id' => $viewAndId[1], 'from' => $viewAndId[0].'futur']), [
                                'title' => Yii::t('app', 'Supprimer futur'),
                                'data-confirm' => Yii::t('app', 'Vous allez supprimer le participant des planifications dans le futur. OK?'),
                            ]);
                        }
                    },
                    'partDelete' => function ($model, $key, $index) use ($viewAndId) {
                        if ($key->personne_id != '') {
                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', Url::to(['/cours/participant-delete', 'personne_id' => $key->personne_id, 'cours_ou_date_id' => $viewAndId[1], 'from' => $viewAndId[0]]), [
                                'title' => Yii::t('yii', 'Delete'),
                                'data-confirm' => Yii::t('app', 'Vous allez supprimer le participant de toutes les planifications. OK?'),
                            ]);
                        }
                    }
                ],
            ],
        ],
        'caption' => ($isInscriptionOk) ? (Yii::$app->user->identity->id < 1000) ? '' : '<div class="row"><div class="col-sm-3">'.Yii::t('app', 'Liste des participants').'</div></div>' : '<div class="row"><div class="col-sm-3">'.Yii::t('app', 'Nombre participant max atteint').'</div></div>',
    ]); ?>
    
    <?php if ($forPresenceOnly) {
        $script = '
        jQuery(document).ready(function() {
            $(\'input[name^="selection"]\').click(function () {
                var key = $(this).attr("value");
                $.ajax({
                    type: "POST",
                    url: "'.Url::to(['/cours-date/presence']).'", 
                    dataType: "json",
                    data: {"personne": key, "coursdate": "'.$model->cours_date_id.'"},
                    success: function(data) {
                        console.log("Personne ID " + key + " - " + data.message);
                        $("#msg").html(data.message).toggle();
                        $("#msg").delay(600).fadeOut("slow");
                    },
                });
            });
        });';
        $this->registerJs($script, View::POS_END);
    } ?>
</div>
