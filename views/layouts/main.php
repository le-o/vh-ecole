<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
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
    <?php $this->head() ?>
</head>
<body style="background-color: <?= Yii::$app->params['bgcolor'] ?>;">
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => 'Vertic-Halle - Gestion des cours',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            ['label' => 'Accueil', 'url' => ['/site/index']],
            !Yii::$app->user->isGuest && Yii::$app->user->identity->id < 1100 ?
                ['label' => Yii::t('app', 'Les personnes'),
                    'items' => [
                        ['label' => Yii::t('app', 'Les clients'), 'url' => ['/personnes']],
                        ['label' => Yii::t('app', 'Les moniteurs'), 'url' => ['/personnes/moniteurs']],
                    ]
                ] : '',
            !Yii::$app->user->isGuest ? 
                ['label' => Yii::t('app', 'Les cours'),
                    'items' => [
                        ['label' => Yii::t('app', 'Planification'), 'url' => ['/cours-date/liste']],
                        !Yii::$app->user->isGuest && Yii::$app->user->identity->id < 1000 ? ['label' => Yii::t('app', 'Gestion des cours'), 'url' => ['/cours']] : '',
                    ],
                ] : '',
            !Yii::$app->user->isGuest && Yii::$app->user->identity->id < 1000 ?
                ['label' => Yii::t('app', 'Outils'),
                    'items' => [
                         ['label' => Yii::t('app', 'Inscription online'), 'url' => ['/clients-online']],
                         Yii::$app->user->identity->id < 500 ? ['label' => Yii::t('app', 'Clients actifs'), 'url' => ['/cours-date/actif']] : '',
                         Yii::$app->user->identity->id < 500 ? ['label' => Yii::t('app', 'Gestion des codes'), 'url' => ['/parametres']] : '',
                         Yii::$app->user->identity->id < 500 ? ['label' => Yii::t('app', 'Sauvegardes'), 'url' => ['/backuprestore']] : '',
                    ],
                ] : '',
            Yii::$app->user->isGuest ?
                ['label' => 'Se connecter', 'url' => ['/site/login']] :
                [
                    'label' => 'Se déconnecter (' . Yii::$app->user->identity->username . ')',
                    'url' => ['/site/logout'],
                    'linkOptions' => ['data-method' => 'post']
                ],
        ],
    ]);
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; Vertic SA <?= date('Y') ?> - version 3.1.3</p>

        <p class="pull-right">Developpé par <a href="http://www.d-web.ch" target="_blank">d-web.ch</a></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
