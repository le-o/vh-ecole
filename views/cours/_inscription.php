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

$modalJs = "
    $('#toggleEmail').click(function() {
        $( '#item' ).toggle();
    });
        
    $('.modal-email-link').click(function() {
        $('#parametres-keyformail').val($(this).closest('tr').data('key'));
    });
";
$this->registerJs($modalJs, View::POS_END);
?>

<div class="cours-participant-form">

    <?php if (User::canRoute(['cours/gestioninscriptions'])) { ?>
    <div class="row">

        <?php $form = ActiveForm::begin(); ?>
            <div class="col-sm-4">
                <?= Select2::widget([
                    'name' => 'new_cours',
                    'data' => $dataCours,
                    'options' => ['placeholder' => Yii::t('app', 'Ajouter cours ...')],
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
        
        <?php $form = ActiveForm::begin(); ?>
            <div class="col-sm-4">
                <?php Modal::begin([
                    'id' => 'modal-email',
                    'header' => '<h3>'.Yii::t('app', 'Contenu du message à envoyer').'</h3>',
                    'toggleButton' => ['label' => Yii::t('app', 'Envoyer un email'), 'class' => 'btn btn-default'],
                ]);

                echo '<a id="toggleEmail" href="#">'.Yii::t('app', 'Voir email(s)').'</a>';
                echo '<div id="item" style="display:none;">';
                echo $personneModel->email;
                echo '</div>';
                
                echo $form->field($parametre, 'keyForMail')->hiddenInput()->label(false);
                echo $form->field($personneModel, 'personne_id')->hiddenInput()->label(false);

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
            </div>
        <?php ActiveForm::end(); ?>
        
    </div>
    <?php } ?>
    
    <?= GridView::widget([
        'dataProvider' => $coursDataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'label' => Yii::t('app', 'Fk Type'),
                'attribute' => 'fkType.nom',
            ],
            [
                'label' => Yii::t('app', 'Fk Nom'),
                'attribute' => 'fkNom.nom',
            ],
            'duree',
            'session',
            [
                'label' => Yii::t('app', 'Fk Saison'),
                'attribute' => 'fkSaison.nom',
            ],
            'date',
            
            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{coursView} {coursEmail}',
                'visibleButtons'=>[
                    'coursView' => User::canRoute(['/cours/view']),
                    'coursEmail' => User::canRoute(['/cours/email']),
                ],
                'buttons'=>[
                    'coursView' => function ($model, $key, $index) {
                        $key = explode('|', $index);
                        if (in_array($key[1], Yii::$app->params['coursPlanifieS'])) {
                            $page = '/cours/view';
                        } else {
                            $page = '/cours-date/view';
                        }
                    	return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to([$page, 'id' => $key[0]]), [
							'title' => Yii::t('app', 'Voir'),
						]);
                    },       
                    'coursEmail' => function ($model, $key, $index) {
                        return Html::a('<span class="glyphicon glyphicon-envelope"></span> ', '#', [
                            'data-toggle' => 'modal',
                            'data-target' => '#modal-email',
                            'data-key' => $index,
                            'class' => 'modal-email-link',
                        ]);
                    }
                ],
            ],
        ],
    ]); ?>

</div>
