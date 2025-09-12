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
            $(".choix_regulier").show();
        } else {
            $(".choix_regulier").hide();
        }
        if ($.inArray(testType, arDemande) != -1) {
            $("#sur_demande_info").show();
        } else {
            $("#sur_demande_info").hide();
        }
    }'
    , \yii\web\View::POS_END);
?>

<br /><br />

<div class="clients-online-form">

    <?php $form = ActiveForm::begin(['id' => 'dynamic-form']); ?>
    
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'nom')->textInput(['maxlength' => true])->label(Yii::t('app', 'Nom participant.e')) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'prenom')->textInput(['maxlength' => true])->label(Yii::t('app', 'Prénom participant.e')) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'nom_representant')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'prenom_representant')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'adresse')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-1">
            <?= $form->field($model, 'numeroRue')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-1">
            <?= $form->field($model, 'npa')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'localite')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'fk_pays')->dropDownList(
                $params->optsPays($model->fk_pays),
                ['prompt'=>Yii::t('app', 'Choisir une valeur')]
            ) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'date_naissance')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'jj.mm.aaaa'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'dd.mm.yyyy',
                    'defaultViewDate' => ['year' => 1980]
                ]
            ])->label(Yii::t('app', 'Date de naissance participant.e')) ?>
        </div>
    </div>
    <div class="row choix_regulier" style="border: 2px solid darkorange; display: none;">
        <div class="col-sm-3">
            <label class="control-label"><?= Yii::t('app', 'Données obligatoires pour les moins de 20 ans (J+S)') ?></label>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, "no_avs")->textInput(['maxlength' => true])->label(Yii::t('app', 'No AVS participant.e')) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'fk_sexe')->dropDownList(
                $params->optsSexe($model->fk_sexe),
                ['prompt'=>Yii::t('app', 'Choisir une valeur')]
            ) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'fk_nationalite')->dropDownList(
                $params->optsPays($model->fk_nationalite),
                ['prompt'=>Yii::t('app', 'Choisir une valeur')]
            ) ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'fk_langue_mat')->dropDownList(
                $params->optsLangue($model->fk_langue_mat),
                ['prompt'=>Yii::t('app', 'Choisir une valeur')]
            ) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'telephone')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
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
                'pmt_complet' => Yii::t('app', 'Paiement du cours en un seul versement'),
                'pmt_tranche' => Yii::t('app', 'Paiement du cours en plusieurs versements (+ CHF 40 de frais administratifs)')
            ], ['class' => 'choix_regulier', 'style' => 'display:none;']) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12"><br />
            <div id="sur_demande_info" style="display:none;"><span style="color:red; font-weight:bold;"><?= Yii::t('app', 'Vous avez choisi un cours sur demande, veuillez indiquer le(s) horaire(s) souhaité(s) date et heure') ?></span></div>
            <?= $form->field($model, 'informations')->textarea(['rows' => 6])->label(Yii::t('app', 'Infos, détails et besoins particuliers')) ?>
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
