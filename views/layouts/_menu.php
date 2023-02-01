<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;

use webvimark\modules\UserManagement\UserManagementModule;
use webvimark\modules\UserManagement\models\User;
    
    NavBar::begin([
        'brandLabel' => 'Vertic-Halle - Gestion des cours',
        'brandUrl' => Yii::$app->homeUrl,
        'innerContainerOptions' => ['class' => 'container-fluid'],
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    
    echo Nav::widget([
        'encodeLabels' => false,
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            ['label' => Yii::t('app', 'Accueil'), 'url' => ['/site/index']],
                ['label' => Yii::t('app', 'Les personnes'), 'visible' => !Yii::$app->user->isGuest && User::canRoute(['/personnes/index']),
                    'items' => [
                        ['label' => Yii::t('app', 'Les clients'), 'url' => ['/personnes'], 'visible' => User::canRoute(['/personnes/index'])],
                        ['label' => Yii::t('app', 'Les moniteurs'), 'url' => ['/personnes/moniteurs'], 'visible' => User::canRoute(['/personnes/moniteurs'])],
                    ]
                ],
                ['label' => Yii::t('app', 'Les cours'), 'visible' => !Yii::$app->user->isGuest && (User::canRoute(['/cours-date/liste']) || User::canRoute(['/cours/index'])),
                    'items' => [
                        ['label' => Yii::t('app', 'Planification'), 'url' => ['/cours-date/liste'], 'visible' => User::canRoute(['/cours-date/liste'])],
                        ['label' => Yii::t('app', 'Gestion des cours'), 'url' => ['/cours'], 'visible' => User::canRoute(['/cours/index'])],
                        ['label' => Yii::t('app', 'Anniversaires'), 'url' => ['/site/anniversaire'], 'visible' => User::canRoute(['/site/anniversaire'])],
                    ],
                ],
                ['label' => Yii::t('app', 'Outils'), 'visible' => !Yii::$app->user->isGuest && User::canRoute(['/clients-online/index']),
                    'items' => [
                         ['label' => Yii::t('app', 'Inscription online'), 'url' => ['/clients-online'], 'visible' => User::canRoute(['/clients-online/index'])],
                         ['label' => Yii::t('app', 'Inscription anniversaire'), 'url' => ['/clients-online/createanniversaire', 'free' => true], 'linkOptions' => ['target'=>'_blank'], 'visible' => User::canRoute(['/clients-online/index'])],
                         ['label' => Yii::t('app', 'Clients actifs'), 'url' => ['/cours-date/actif'], 'visible' => User::canRoute(['/cours-date/actif'])],
                         ['label' => Yii::t('app', 'Gestion des codes'), 'url' => ['/parametres'], 'visible' => User::canRoute(['/parametres/index'])],
                         ['label' => Yii::t('app', 'Sauvegardes'), 'url' => ['/backuprestore'], 'visible' => User::canRoute(['/backuprestore/index'])],
                         ['label' => Yii::t('app', 'Synchro calendrier'), 'url' => ['/site/calendarsync'], 'visible' => User::canRoute(['//site/calendarsync'])],
                         ['label' => Yii::t('app', 'Emails envoyés'), 'url' => ['/sent-email'], 'visible' => User::canRoute(['/sent-email/index'])],
                    ],
                ],
            ['label' => Yii::t('app', 'Gestion des droits'), 'visible' => !Yii::$app->user->isGuest && User::canRoute(['/user-management/user/index']),
                'items' => [
                    ['label' => UserManagementModule::t('back', 'Users'), 'url' => ['/user-management/user/index'], 'visible' => User::canRoute(['/user-management/user/index'])],
                    ['label' => UserManagementModule::t('back', 'Roles'), 'url' => ['/user-management/role/index'], 'visible' => User::canRoute(['/user-management/role/index'])],
                    ['label' => UserManagementModule::t('back', 'Permissions'), 'url' => ['/user-management/permission/index'], 'visible' => User::canRoute(['/user-management/permission/index'])],
                    ['label' => UserManagementModule::t('back', 'Permission groups'), 'url' => ['/user-management/auth-item-group/index'], 'visible' => User::canRoute(['/user-management/auth-item-group/index'])],
                    ['label' => UserManagementModule::t('back', 'Visit log'), 'url' => ['/user-management/user-visit-log/index'], 'visible' => User::canRoute(['/user-management/user-visit-log/index'])],
                ],
            ],
            Yii::$app->user->isGuest ?
                ['label' => Yii::t('app', 'Se connecter'), 'url' => ['/site/login']] :
                [
                    'label' => '<span class="glyphicon glyphicon-user"></span> '.Yii::$app->user->username,
                        'items' => [
                            ['label' => Yii::t('app', 'Mes cours'), 'url' => ['/personnes/mycours'], 'visible' => User::canRoute(['/personnes/mycours'])],
                            ['label' => Yii::t('app', 'Mon compte'), 'url' => ['/user-management/user/view', 'id' => User::getCurrentUser()->id], 'visible' => User::canRoute(['/user-management/user/view'])],
                            ['label' => Yii::t('app', 'Changer mot de passe'), 'url' => ['/user-management/auth/change-own-password'], 'visible' => User::canRoute(['/user-management/auth/change-own-password'])],
                            ['label' => Yii::t('app', 'Se déconnecter'), 'url' => ['/site/logout'], 'linkOptions' => ['data-method' => 'post']]
                        ]
                ],
            \lajax\languagepicker\widgets\LanguagePicker::widget([
                'itemTemplate' => '<li><a href="{link}" title="{language}">{name}</a></li>',
                'activeItemTemplate' => '{language} <span class="caret"></span>',
                'parentTemplate' => '<li class="dropdown language-picker dropdown-list {size}"><a class="dropdown-toggle" href="#" data-toggle="dropdown">{activeItem}</a><ul class="dropdown-menu">{items}</ul></li>',
            ]),
        ],
    ]);
    NavBar::end();
    
    ?>