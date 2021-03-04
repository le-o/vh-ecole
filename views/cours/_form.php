<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\bootstrap\Alert;
use yii\web\View;
use kartik\file\FileInput;
use kartik\dialog\Dialog;

/* @var $this yii\web\View */
/* @var $model app\models\Cours */
/* @var $form yii\widgets\ActiveForm */

$script = '
    $("#cours-prix").change(function() {
        $("#edit-price").val($(this).val());
    });
    
    $("#submitButton").on("click", function() {
        if ("" != $("#edit-price").val()) {
            myDialogPrice.confirm("' . Yii::t('app', 'Modifier également le prix de chaque date de cours ?') . '", function (result) {
                if (result) {
                    console.log($("#edit-price").val());
                } else {
                    $("#edit-price").val("");
                }
                $("#editCours").submit();
            });
            event.preventDefault();
        }
    });
';
$this->registerJs($script, View::POS_END);
?>

<?php if ($alerte != '') {
    echo Alert::widget([
        'options' => [
            'class' => 'alert-danger',
        ],
        'body' => $alerte,
    ]); 
} ?>

<div class="cours-form">

    <?php $form = ActiveForm::begin(['options' => ['id' => 'editCours', 'enctype' => 'multipart/form-data']]) ?>
    <?= Dialog::widget([
        'libName' => 'myDialogPrice',
        'options' => [
            'type' => Dialog::TYPE_DEFAULT,
            'title' => Yii::t('app', 'Le prix du cours a été modifié !'),
            'btnOKClass' => 'btn-primary',
            'btnOKLabel' => Yii::t('app', 'Oui'),
            'btnCancelLabel' => Yii::t('app', 'Non'),
        ]
    ]) ?>
    <?= Dialog::widget([
        'libName' => 'myDialogBareme',
        'options' => [
            'type' => Dialog::TYPE_DEFAULT,
            'title' => Yii::t('app', 'Le barême du cours a été modifié !'),
            'btnOKClass' => 'btn-primary',
            'btnOKLabel' => Yii::t('app', 'Oui'),
            'btnCancelLabel' => Yii::t('app', 'Non'),
        ]
    ]) ?>
    <div class="row">
        <div class="col-sm-2">
            <?= $form->field($model, 'fk_niveau')->dropDownList($modelParams->optsNiveau($model->fk_niveau),['prompt'=>Yii::t('app', 'Choisir un niveau')]) ?>
        </div>
        <div class="col-sm-2">
            <label></label>
            <?= $form->field($model, 'is_materiel_compris')->checkbox() ?>
        </div>
        <div class="col-sm-2">
            <label></label>
            <?= $form->field($model, 'is_entree_compris')->checkbox() ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'fk_statut')->dropDownList($modelParams->optsStatutCours($model->fk_statut),['prompt'=>Yii::t('app', 'Choisir un statut')]) ?>
        </div>
        <div class="col-sm-2">
            <label></label>
            <?= $form->field($model, 'is_publie')->checkbox() ?>
        </div>
        <div class="col-sm-2">
            <?= $form->field($model, 'tri_internet')->textInput(['type' => 'number', 'min' => 0]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'fk_nom')->dropDownList($modelParams->optsNomCours($model->fk_nom),['prompt'=>Yii::t('app', 'Choisir un nom')]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'fk_age')->dropDownList($modelParams->optsTrancheAge($model->fk_age),['prompt'=>Yii::t('app', 'Choisir une tranche d\'âge')]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'session')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'fk_jours')->checkboxList($modelParams->optsJourSemaine()) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'fk_saison')->dropDownList($modelParams->optsSaison($model->fk_saison),['prompt'=>Yii::t('app', 'Choisir une saison')]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <?= $form->field($model, 'duree')->textInput(['type' => 'number', 'step' => '0.25', 'placeholder' => Yii::t('app', 'en heure')]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'prix')->textInput(['type' => 'number', 'min' => 0, 'max' => 5000]) ?>
            <input type="hidden" id="edit-price" name="editPrice" value="" />
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'participant_min')->textInput(['type' => 'number', 'min' => 1, 'max' => 50]) ?>
        </div>
        <div class="col-sm-3">
            <?= $form->field($model, 'participant_max')->textInput(['type' => 'number', 'min' => 1, 'max' => 150]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'extrait')->textarea(['rows' => 2]) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'offre_speciale')->textarea(['rows' => 2]) ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <?= $form->field($model, 'description')->widget(new \yii\redactor\widgets\Redactor()) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'fk_categories')->checkboxList($modelParams->optsCategorie()) ?>
            <?= $form->field($model, 'fk_langue')->dropDownList($modelParams->optsLangue($model->fk_langue)) ?>
            <?= $form->field($model, 'fk_salle')->dropDownList($modelParams->optsSalle($model->fk_salle)) ?>

            <label for="editBareme"><?= Yii::t('app', 'Changer le barême pour toutes les dates') ?></label>
            <?= Html::dropDownList('editBareme', null, $modelParams->optsNiveauFormation(), ['class' => 'form-control', 'prompt'=>'Choisir un barême']) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'image')->widget(FileInput::classname(), [
                'options' => ['accept' => 'image/*'],
                'pluginOptions' => [
                    'initialPreview'=>($model->image_web != '') ? ['..'.Yii::$app->params['uploadPath'] . $model->image_web] : false,
                    'initialPreviewAsData'=>true,
                    'showUpload' => false,
                    'overwriteInitial'=>true
                ],
                'pluginEvents' => [
                    'filecleared' => 'function() { 
                        $("#cours-image_hidden").val("");
                    }',
                ]
            ]) ?>
            <?= $form->field($model, 'image_hidden')->hiddenInput()->label(false); ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['id' => 'submitButton', 'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?php if (!$model->isNewRecord) { ?>
            <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->cours_id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app', 'Vous allez supprimer le cours ainsi que tous les participants et toutes les planifications. OK?'),
                    'method' => 'post',
                ],
            ]) ?>
            <?= Html::a(Yii::t('app', 'Dupliquer'), ['clone', 'id' => $model->cours_id], ['class' => 'btn btn-info']) ?>
        <?php } ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
