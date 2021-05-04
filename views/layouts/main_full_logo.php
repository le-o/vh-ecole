<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
//use yii\bootstrap\Nav;
//use yii\bootstrap\NavBar;
//use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->registerJsFile('../web/js/iframeResizer.contentWindow.min.js'); ?>
    <?php $this->head() ?>
    <style type="text/css">
        .wrap > .container {
            padding-top: 0;
            padding-bottom: 0;
        }
        body {
            background-color: transparent;
        }
        #logo {
            width: 150px;
        }
    </style>
</head>
<body class="body-no-height">
<?php $this->beginBody() ?>

<div class="wrap">

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3"><img src="../web/images/logo-VH-indoor-climbing-centers.jpg" id="logo" /></div>
            <div class="col-md-9"><h1><br /><?= Yii::t('app', 'Inscription online') ?></h1></div>
        </div>
        <?= $content ?>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
