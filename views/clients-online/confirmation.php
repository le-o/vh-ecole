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
        <?php echo '<div class="alert alert-success">'.Yii::t('app', 'Merci pour votre commande.<br>Une confirmation d\'inscription vous a été envoyée par e-mail.').'</div>'; ?>
    </div>
    
</div>