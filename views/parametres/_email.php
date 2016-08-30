<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\color\ColorInput;

/* @var $this yii\web\View */
/* @var $model app\models\Parametres */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="parametres-form">

    <?php $form = ActiveForm::begin();

    echo $form->field($template, 'nom')->textInput()->label(Yii::t('app', 'Sujet'));
    echo $form->field($template, 'valeur')->widget(\yii\redactor\widgets\Redactor::className())->label(Yii::t('app', 'Texte'));

    echo '<div id="test_div">vide</div>';

    echo Html::submitButton(Yii::t('app', 'Envoyer'), ['class' => 'btn btn-primary']);

    ActiveForm::end(); ?>

</div>
