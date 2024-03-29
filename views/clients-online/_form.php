<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use wbraganca\dynamicform\DynamicFormWidget;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */
/* @var $form yii\widgets\ActiveForm */

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
            <label class="control-label" for="w1"><?= Yii::t('app', 'Nom du cours'); ?></label>
	        <?= Select2::widget([
				'name' => 'list_cours',
				'value' => $selectedCours, // initial value
				'data' => $dataCours,
				'options' => ['placeholder' => Yii::t('app', 'Choisir un cours ...'), 'multiple' => false],
			    'pluginOptions' => [
			        'allowClear' => true,
			        'tags' => true,
			    ],
			]); ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <?= yii\bootstrap\BaseHtml::checkbox('offre_annuelle', false, ['label' => Yii::t('app', 'Je souhaite profiter de l’offre annuelle (inscription aux semestres 1 et 2 avec abonnement annuel offert)')]) ?>
            <?= yii\bootstrap\BaseHtml::checkbox('pmt_tranche', false, ['label' => Yii::t('app', 'Je souhaite étaler le paiement du cours en plusieurs tranches (10.- frais administratifs)')]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12"><br />
            <?= $form->field($model, 'informations')->textarea(['rows' => 6])->label(Yii::t('app', 'Infos, détails et besoins particuliers (date et horaire, nom de la session, offres, 2 cours à l\'essai, etc.)')) ?>
        </div>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-heading"><h4><i class="glyphicon glyphicon-plus"></i> <?= Yii::t('app', 'Inscrire d\'autres personnes sous mon nom:') ?></h4></div>
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
    <h4>Conditions d'inscription et d'annulation</h4>
    Les <strong>cours sur demande</strong> (cours privés, cours organisés pour des groupes, cours découverte et anniversaires) se font 
    sur réservation 72 heures à l'avance.<br />
    100% de la prestation est due en cas d'annulation à moins de 72h. Si le client reporte le cours, 40% du montant sera demandé en guise de frais de report.<br />
    <br />
    Les <strong>cours planifiés</strong> (cours collectifs programmés en avance sur une saison ainsi que les stages) se font sur réservation. 
    100% du montant doit être versé avant le début du cours pour pouvoir y participer. En cas d'annulation à moins de 72h par 
    le client, 100% de la prestation est due.<br />
    <br />
    En cas d'annulation par l'organisateur, le montant déjà payé sera intégralement remboursé.<br />
    <br />
    Nous conseillons à notre aimable clientèle de souscrire à une assurance annulation afin d'éviter tout désagrément.


</div>
