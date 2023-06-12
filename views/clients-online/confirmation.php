<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $model app\models\ClientsOnline */

$script = '
    jQuery(document).ready(function() {
        window.parent.$("body").animate({scrollTop:0}, "slow");
    });';
$this->registerJs($script, View::POS_END);

?>
<div class="clients-online-view">

    <div class="no-print">
        <?php echo '<div class="alert alert-success">'.Yii::t('app', 'Nous avons bien reçu votre commande.<br>Un mail de confirmation de réception de celle-ci vous a été envoyé par e-mail.').'</div>'; ?>
    </div>
    
</div>