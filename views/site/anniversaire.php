<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\bootstrap\Alert;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Modal;
use kartik\select2\Select2;
use webvimark\modules\UserManagement\models\User;

/* @var $this yii\web\View */

$this->title = 'VH Gestion des anniversaires';
?>
<div class="site-index">
    <div class="container">
        <?php if (Yii::$app->session->hasFlash('alerte')) {
            $alerte = Yii::$app->session->getFlash('alerte');
            echo Alert::widget([
                'options' => [
                    'class' => 'alert-'.$alerte['type'],
                ],
                'body' => $alerte['info'],
            ]); 
        } ?>

        <?php if ($dataProviderNM->totalCount == 0 && User::canRoute(['/cours/update'])) { ?>
            <div class="row"><br /><br /><br />INFO: <?= Yii::t('app', 'Aucun anniversaire dans le futur sans moniteur') ?></div>
        <?php } elseif ($dataProviderNM->totalCount > 0 && User::canRoute(['/cours/update'])) { ?>
            <h2><br /><?= Yii::t('app', 'Aniversaires sans moniteur') ?></h2>

            <?= GridView::widget([
                'dataProvider' => $dataProviderNM,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

                    [
                        'attribute' => 'fkCours',
                        'value' => 'fkCours.fkNom.nom',
                    ],
                    'date',
                    'heure_debut',
                    [
                        'label' => Yii::t('app', 'Lieu'),
                        'value' => 'fkLieu.nom',
                    ],

                    ['class' => 'yii\grid\ActionColumn',
                        'template'=>'{coursDateUpdate}',
                        'buttons'=>[
                            'coursDateUpdate' => function ($url, $model) {
                                return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Url::to(['/cours-date/view', 'id' => $model->cours_date_id]), [
                                    'title' => Yii::t('yii', 'Update'),
                                ]);
                            },
                        ],
                    ],
                ],
            ]); 
        } ?>
    </div>
        
    <h2>VH Calendrier des anniversaires</h2>

    <div class="row">
        <div class="col-md-6">
            <h4>Légende</h4>
            <span style="color:#ff0000;">Rouge : Pas de clients</span><br />
            <span style="color:#ff9900;">Orange : ATTENTION Client inscrit, mais pas de moniteurs</span><br />
            <span style="color:#27db39;">Vert : tout OK, client inscrit avec un/des moniteurs</span>
        </div>
    </div>
    
    <div class="row">
        <?php foreach ($dataSalles as $salle) { ?>
            <div class="col-md-6">
                <h2><?= $salle->nom ?></h2>
                <?= yii2fullcalendar\yii2fullcalendar::widget([
                    'clientOptions' => [
                        'defaultView' => Yii::$app->session->get('anni-cal-view-' . $salle->parametre_id),
                        'weekNumbers' => true,
                        'defaultDate' => Yii::$app->session->get('anni-cal-debut-' . $salle->parametre_id),
                        'eventTimeFormat' => 'H:mm',
                        'scrollTime' => '08:00:00',
                        'aspectRatio' => 1.6,
                        'allDaySlot' => false,
                        'eventLimit' => 3,
                        'nowIndicator' => true,
                    ],
                    'options' => [
                        'id' => 'myCalendar' . $salle->parametre_id,
                        'lang' => 'fr',
                    ],
                    'header' => [
                        'center'=>'title',
                        'left'=>'prev,next today',
                        'right'=>'agendaDay,agendaWeek,month'
                    ],
                    'eventRender' => 'function(event, element) {
                        element.find(\'.fc-title\').append("<br/><br/><i>" + event.nonstandard + "</i>");
                    } ',
                    'eventAfterAllRender' => 'function(event) {
                        var moment = $(\'#myCalendar' . $salle->parametre_id . '\').fullCalendar(\'getDate\');
                        $.ajax({
                                type: "POST",
                                cache: false,
                                url: "'.Url::toRoute(['/site/setcalendarview', 'for' => $salle->parametre_id, 'name' => 'anni']).'",
                                data: {view: event.name, start: moment.format()},
                                dataType: "json",
                            });
                    } ',
                    'ajaxEvents' => yii\helpers\Url::to(['/cours-date/jsoncalanni', 'for' => $salle->parametre_id])
                ]); ?>
            </div>
        <?php } ?>
    </div>
    
</div>
