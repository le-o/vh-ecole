<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;
use yii\web\View;

$this->title = 'Synchronisation du calendrier';
$this->params['breadcrumbs'][] = $this->title;

$script = "
    $('#sync-form').submit(function() {
        // show the hidden element with id loader
        $('#loader').show();
        $('.sync-form').css('opacity', 0.3);
    });";
$this->registerJs($script, View::POS_END);
?>
<div class="sync-form">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->session->hasFlash('syncOK')): ?>

        <div class="alert alert-success">
            La synchronisation a été effectuée avec succès !
        </div>

    <?php endif; ?>

    <p>
        Outil de synchronisation de l'agenda de l'application avec le compte cours@vertic-halle.ch
    </p>

    <div class="row">
        <div class="col-lg-5">

            <?php $form = ActiveForm::begin(['id' => 'sync-form', 'layout' => 'inline']); ?>

                <div class="form-group">
                    <?= Html::label('Nombre à traiter', 'nbATraiter') ?>
                    <?= \kartik\helpers\Html::textInput('nbATraiter', 0, ['class' =>'form-control', 'type' => 'number', 'min' => 0, 'style' => 'width:50px']) ?>
                    <span class="hint">Info: <tt>zéro</tt> pour illimité, <?= $nombreATraiter ?> à traiter</span>
                </div>
                <div class="form-group">
                    <?= Html::submitButton('Synchroniser', ['class' => 'btn btn-primary', 'name' => 'sync-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>
    
    <?php if (!empty($logTraitement)): ?>
    
        <div class="row">
            <div class="col-lg-12">
                <i>Logs de traitement</i>
                <?php foreach ($logTraitement as $salle => $logBySalle) { ?>
                    <h3><?= $salle ?></h3>
                    <?php foreach ($logBySalle as $log) { ?>
                        <div>[<?=$log['cours_date_id']?>-<?=$log['fk_cours']?>][<i><strong><?=$log['statut']?></strong></i>] cours <?=$log['nom']?> du <?=$log['date']?> de <?=$log['heure_debut']?> à <?=$log['heure_fin']?></div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    
    <?php endif; ?>

</div>

<div id="loader">
    <img src="images/beer-loader.gif" />
</div>