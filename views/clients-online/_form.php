<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use wbraganca\dynamicform\DynamicFormWidget;
use app\models\Cours;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */
/* @var $form yii\widgets\ActiveForm */

$this->registerJs('
    jQuery(document).ready(function() {
        displayMessage(jQuery("#choix_cours"), ' . implode(',', $selectedCours) . ');
    });
    function displayMessage(that, type) {
        var arRegulier = [' . Cours::getCoursByType(Yii::$app->params['coursRegulie']) . '];
        var arDemande = [' . $params->optsNomCoursByType(Yii::$app->params['coursPonctuel']) . '];
        if (typeof type != \'undefined\') testType = parseInt(type);
        else testType = parseInt(that.val());

        if ($.inArray(testType, arRegulier) != -1) {
            $("#choix_regulier").show();
        } else {
            $("#choix_regulier").hide();
        }
        if ($.inArray(testType, arDemande) != -1) {
            $("#sur_demande_info").show();
        } else {
            $("#sur_demande_info").hide();
        }
    }'
    , \yii\web\View::POS_END);
$this->registerJs('
    $(".dynamicform_wrapper").on("beforeDelete", function(e, item) {
        if (! confirm("'.Yii::t('app', 'Etes-vous sur de vouloir supprimer cet élément?').'")) {
            return false;
        }
        return true;
    });

    $(".dynamicform_wrapper").on("limitReached", function(e, item) {
        alert("'.Yii::t('app', 'Vous avez atteint la limite autorisée.').'");
    });'
    , \yii\web\View::POS_READY);
?>

<h3><?= Yii::t('app', 'Attention : pour les inscriptions à - de 72h , merci de nous contacter par téléphone') ?></h3>
<br /><br />

<div class="clients-online-form">

    <?php $form = ActiveForm::begin(['id' => 'dynamic-form']); ?>
    
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'nom')->textInput(['maxlength' => true])->label(Yii::t('app', 'Nom (du représentant légal si mineur)')) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'prenom')->textInput(['maxlength' => true])->label(Yii::t('app', 'Prénom (du représentant légal si mineur)')) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'adresse')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'npa')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'localite')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'telephone')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'date_naissance')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                    'defaultViewDate' => ['year' => 1980]
                ]
            ]); ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'fk_cours')->widget(Select2::classname(), [
                'options'=>['placeholder' => Yii::t('app', 'Choisir un cours ...'), 
                    'id' => 'choix_cours',
                    'multiple' => false, 
                    'onchange'=>"displayMessage($(this))",
                    'disabled' => (count($dataCours) == 1) ? true : false,
                    'value' => $selectedCours, // initial value
                ],
                'data' => $dataCours,
                'pluginOptions'=>[
                    'initialize' => true,
                    'allowClear' => true,
                    'tags' => true,
                ],
            ]); ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <?= yii\bootstrap\BaseHtml::radioList('offre_supp', false, [
                'cours_essai' => Yii::t('app', 'J\'aimerais que mon enfant essaie avant de l\'inscrire pour la saison et je souhaite être contacté à ce sujet'),
                'pmt_complet' => Yii::t('app', 'J\'inscris mon enfant pour la saison et je paie le montant du cours en un seul versement'),
                'pmt_tranche' => Yii::t('app', 'J\'inscris mon enfant pour la saison et je paie le montant du cours en plusieurs versements (+ CHF 40 de frais administratifs)')
            ], ['id' => 'choix_regulier', 'style' => 'display:none;']) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12"><br />
            <div id="sur_demande_info" style="display:none;"><span style="color:red; font-weight:bold;"><?= Yii::t('app', 'Vous avez choisi un cours sur demande, veuillez indiquer le(s) horaire(s) souhaité(s) date et heure') ?></span></div>
            <?= $form->field($model, 'informations')->textarea(['rows' => 6])->label(Yii::t('app', 'Infos, détails et besoins particuliers')) ?>
        </div>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-heading"><h4><i class="glyphicon glyphicon-plus"></i> <?= Yii::t('app', 'Inscrire un/des enfant/s sous mon nom:') ?></h4></div>
        <div class="panel-body">
             <?php DynamicFormWidget::begin([
                'widgetContainer' => 'dynamicform_wrapper', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
                'widgetBody' => '.container-items', // required: css class selector
                'widgetItem' => '.item', // required: css class
                'limit' => 4, // the maximum times, an element can be cloned (default 999)
                'min' => 1, // 0 or 1 (default 1)
                'insertButton' => '.add-item', // css class
                'deleteButton' => '.remove-item', // css class
                'model' => $modelsClient[0],
                'formId' => 'dynamic-form',
                'formFields' => [
                    'nom',
                    'prenom',
                    'date_naissance',
                ],
            ]); ?>

            <div class="container-items"><!-- widgetContainer -->
            <?php foreach ($modelsClient as $i => $modelClient): ?>
                <div class="item panel panel-default"><!-- widgetBody -->
                    <div class="panel-heading">
                        <h3 class="panel-title pull-left"><?= Yii::t('app', 'Client') ?></h3>
                        <div class="pull-right">
                            <button type="button" class="add-item btn btn-success btn-xs"><i class="glyphicon glyphicon-plus"></i></button>
                            <button type="button" class="remove-item btn btn-danger btn-xs"><i class="glyphicon glyphicon-minus"></i></button>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php
                            // necessary for update action.
                            if (!$modelClient->isNewRecord) {
                                echo Html::activeHiddenInput($modelClient, "[{$i}]client_online_id");
                            }
                        ?>
                        <div class="row">
                            <div class="col-sm-4">
                                <?= $form->field($modelClient, "[{$i}]nom")->textInput(['maxlength' => true]) ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($modelClient, "[{$i}]prenom")->textInput(['maxlength' => true]) ?>
                            </div>
                            <div class="col-sm-4">
                                <?= $form->field($modelClient, "[{$i}]date_naissance")->widget(DatePicker::classname(), [
                                    'options' => ['placeholder' => 'jj.mm.aaaa'],
                                    'removeButton' => false,
                                    'pluginOptions' => [
                                        'autoclose'=>true,
                                        'format' => 'dd.mm.yyyy',
                                        'defaultViewDate' => ['year' => 1980]
                                    ]
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php DynamicFormWidget::end(); ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'iagree')->checkbox() ?>
        </div>
    </div>
    
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'S\'inscrire') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
    
    <br /><br />
    <?= Yii::t('app', "Conditions inscription et annulation") ?>


</div>
