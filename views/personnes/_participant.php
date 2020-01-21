<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use kartik\select2\Select2;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use yii\web\View;
use yii\bootstrap\Modal;
use webvimark\modules\UserManagement\models\User;

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

    <?php if (User::canRoute(['cours/gestionpresences']) || User::canRoute(['/cours/presence'])) { ?>
        <div class="row">
            <?php if ($isInscriptionOk && User::canRoute(['cours/gestioninscriptions'])) { ?>

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

            <?php $form = ActiveForm::begin(); ?>
            <div class="col-sm-7">
                <?php if (User::canRoute(['cours/gestioninscriptions'])) { ?>
                    <?= Html::a(Yii::t('app', 'Gestion inscription'), ['cours/gestioninscriptions', 'cours_id' => (isset($model->cours_id) ? $model->cours_id : $model->fk_cours)], ['class' => ($model->getNombreClientsInscrits() == 0) ? 'btn btn-default disabled' : 'btn btn-default']) ?>
                    <?php Modal::begin([
                        'header' => '<h3>'.Yii::t('app', 'Contenu du message à envoyer').'</h3>',
                        'toggleButton' => ['label' => Yii::t('app', 'Envoyer un email'), 'class' => 'btn btn-default'],
                    ]);

                    echo '<a id="toggleEmail" href="#">'.Yii::t('app', 'Voir email(s)').'</a>';
                    echo '<div id="item" style="display:none;">';
                    echo $form->field($parametre, 'listeEmails')->textarea()->label(false);
                    echo '</div>';

                    $parametre->keyForMail = $viewAndId[1];
                    $parametre->listePersonneId = implode('|', $participantIDs);
                    echo $form->field($parametre, 'keyForMail')->hiddenInput()->label(false);
                    echo $form->field($parametre, 'listePersonneId')->hiddenInput()->label(false);

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
                    echo Yii::$app->view->renderFile('@app/views/site/dynamicFields.php');
                    echo $form->field($parametre, 'valeur')->widget(\yii\redactor\widgets\Redactor::className())->label(Yii::t('app', 'Texte'));

                    echo Html::submitButton(Yii::t('app', 'Envoyer'), ['class' => 'btn btn-primary']);
                    Modal::end(); ?>
                <?php } ?>
                    
                <?php if (!$forPresenceOnly || User::canRoute(['/cours/presence'])) { ?>
                    <?php $nomBouton = (User::hasRole(['accueil', 'moniteurs'])) ? Yii::t('app', 'Imprimer la liste des participants') : Yii::t('app', 'Imprimer'); ?>
                    <?= Html::a($nomBouton, ['/cours/presence', 'id' => (isset($model->cours_id) ? $model->cours_id : $model->fk_cours)], ['class' => 'btn btn-default']) ?>
                <?php } ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    <?php } ?>
    
    <?= GridView::widget([
        'dataProvider' => $participantDataProvider,
        'id' => 'participantgrid',
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'statutPart',
                'label' => 'Statut',
                'visible' => (!isset($model->fk_type) || (isset($model->fk_type) && in_array($model->fk_type, Yii::$app->params['coursPlanifieS']))) ? true : true,
            ],
            'suivi_client',
            'societe',
            'nom',
            'prenom',
            'age',
            [
                'attribute' => 'email',
                'visible' => User::canRoute(['/personnes/advanced']),
            ],
            [
                'attribute' => 'telephone',
                'visible' => User::canRoute(['/personnes/advanced']),
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
                'visible' => ($forPresenceOnly && User::canRoute(['/cours-date/presence'])),
                'checkboxOptions' => function ($data, $key, $index, $column) use ($model, $forPresenceOnly) {
                    if ($forPresenceOnly) {
                        $bool = $data->getClientsHasOneCoursDate($model->cours_date_id);
                        return ['value' => $key, 'checked' => (isset($bool->is_present)) ? $bool->is_present : false];
                    }
                    return '';
                }
            ],
            
            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{partView} {partUpdate} {partDeleteFutur} {partDelete}',
                'visibleButtons'=>[
                    'partView' => User::canRoute(['/personnes/view']),
                    'partUpdate' => (User::canRoute(['/clients-has-cours/update']) && $viewAndId[0] != 'cours-date') ? true : false,
                    'partDeleteFutur' => (User::canRoute(['/cours/participant-delete']) && $model::className() == 'app\models\Cours') ? (isset($model->fk_type) ? in_array($model->fk_type, Yii::$app->params['coursPlanifieS']) : in_array($model->fkCours->fk_type, Yii::$app->params['coursPlanifieS'])) : false,
                    'partDelete' => (User::canRoute(['/cours/participant-delete']) && $model::className() == 'app\models\Cours') ? true : false,
                ],
                'buttons'=>[
                    'partView' => function ($model, $key, $index) {
                        if ($key->personne_id != '') {
                            return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to(['/personnes/view', 'id' => $key->personne_id]), [
                                'title' => Yii::t('app', 'Voir'),
                            ]);
                        }
                    },
                    'partUpdate' => function ($model, $key, $index) use ($viewAndId) {
                        if ($key->personne_id != '') {
                            return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Url::to(['/clients-has-cours/update', 'fk_personne' => $key->personne_id, 'fk_cours' => $viewAndId[1]]), [
                                'title' => Yii::t('app', 'Modifier statut'),
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
                                'data-confirm' => Yii::t('app', 'Vous allez supprimer le participant uniquement sur cette date. OK?'),
                            ]);
                        }
                    }
                ],
            ],
        ],
        'caption' => ($isInscriptionOk) ? (!User::hasRole(['accueil', 'moniteurs'])) ? '' : '<div class="row"><div class="col-sm-3">'.Yii::t('app', 'Liste des participants').'</div></div>' : '<div class="row"><div class="col-sm-3">'.Yii::t('app', 'Nombre participant max atteint').'</div></div>',
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
                        $("#msg").html(data.message).toggle();
                        $("#msg").delay(600).fadeOut("slow");
                    },
                });
            });
        });';
        $this->registerJs($script, View::POS_END);
    } ?>
</div>
