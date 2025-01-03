<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\bootstrap\Alert;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Modal;
use kartik\select2\Select2;
use leo\modules\UserManagement\models\User;

/* @var $this yii\web\View */

$script = "
    $('.modal-moniteur-link').click(function() {
        $('#coursDateId').val($(this).closest('tr').data('key'));
        console.log($(this).closest('tr').data('key'));
    });
";
$this->registerJs($script, View::POS_END);

$this->title = 'VH Gestion des cours';
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
    
        <?php /* $form = ActiveForm::begin(); ?>
            <div class="col-sm-4">
                <?php Modal::begin([
                    'options' => [
                        'id' => 'modal-moniteur',
                        'tabindex' => false // important for Select2 to work properly
                    ],
                    'header' => '<h3>'.Yii::t('app', 'Se proposer comme moniteur').'</h3>',
                ]);
                echo '<div class="row"><div class="col-sm-9">';
                echo Select2::widget([
                    'name' => 'new_moniteur',
                    'data' => $dataMoniteurs,
                    'options' => ['placeholder' => Yii::t('app', 'Ajouter moniteur ...')],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ],
                ]);
                echo Html::hiddenInput('coursDateId', '', ['id'=>'coursDateId']);
                echo '</div><div class="col-sm-2">';

                echo Html::submitButton(Yii::t('app', 'Enregistrer'), ['class' => 'btn btn-primary']);
                echo '</div></div>';
                Modal::end(); ?>
            </div>
        <?php ActiveForm::end(); ?>

        <?php if ($dataProviderNM->totalCount > 0) { ?>
            <h2><?= Yii::t('app', 'Cours actifs sans moniteur') ?></h2>

            <?= GridView::widget([
                'id' => 'courssansmoniteurgrid',
                'dataProvider' => $dataProviderNM,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

                    [
                        'attribute' => 'date',
                    ],
                    [
                        'attribute' => 'fkCours',
                        'value' => 'fkCours.fkNom.nom',
                    ],
                    [
                        'label' => 'Niveau',
                        'value' => 'fkCours.fkNiveau.nom',
                    ],
                    'fkCours.session',
                    [
                        'label' => 'Saison',
                        'value' => 'fkCours.fkSaison.nom',
                    ],

                    ['class' => 'yii\grid\ActionColumn',
                        'template'=>'{ajoutMoniteur}',
                        'buttons'=>[
                            'ajoutMoniteur' => function ($model, $key, $index) {
                                return Html::a('<span class="glyphicon glyphicon-plus-sign"></span> ', '#', [
                                    'data-toggle' => 'modal',
                                    'data-target' => '#modal-moniteur',
                                    'data-key' => $index,
                                    'class' => 'modal-moniteur-link',
                                ]);
                            }
                        ],
                    ],
                ],
            ]); 
        } */?>

        <?php if ($dataProviderNF->totalCount == 0 && User::canRoute(['/cours/update'])) { ?>
            <div class="row"><br /><br /><br />INFO: <?= Yii::t('app', 'Aucun cours actifs sans date future') ?></div>
        <?php } elseif ($dataProviderNF->totalCount > 0 && User::canRoute(['/cours/update'])) { ?>
            <h2><br /><?= Yii::t('app', 'Cours actifs sans date future') ?></h2>

            <?= GridView::widget([
                'dataProvider' => $dataProviderNF,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

                    [
                        'attribute' => 'fkCours',
                        'value' => 'fkCours.fkNom.nom',
                    ],
                    [
                        'label' => 'Niveau',
                        'value' => 'fkCours.fkNiveau.nom',
                    ],
                    'fkCours.session',
                    [
                        'label' => 'Saison',
                        'value' => 'fkCours.fkSaison.nom',
                    ],

                    ['class' => 'yii\grid\ActionColumn',
                        'template'=>'{coursUpdate}',
                        'buttons'=>[
                            'coursUpdate' => function ($url, $model) {
                                return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Url::to(['/cours/view', 'id' => $model->fk_cours]), [
                                    'title' => Yii::t('yii', 'Update'),
                                ]);
                            },
                        ],
                    ],
                ],
            ]); 
        } ?>
    </div>
        
    <h2>VH <?= Yii::t('app', 'Calendrier des cours') ?></h2>
    
    <div class="row">
        <?php foreach ($dataSalles as $salle) { ?>
            <div class="col-md-6">
                <h2><?= $salle->nom ?></h2>
                <?= yii2fullcalendar\yii2fullcalendar::widget([
                    'clientOptions' => [
                        'defaultView' => Yii::$app->session->get('home-cal-view-' . $salle->parametre_id),
                        'weekNumbers' => true,
                        'defaultDate' => Yii::$app->session->get('home-cal-debut-' . $salle->parametre_id),
                        'eventTimeFormat' => 'H:mm',
                        'scrollTime' => '08:00:00',
                        'aspectRatio' => 1.6,
                        'allDaySlot' => false,
                        'eventLimit' => 3,
                        'nowIndicator' => true,
                    ],
                    'options' => [
                        'id' => 'myCalendar' . $salle->parametre_id,
                        'lang' => substr(Yii::$app->language, 0, 2),
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
                                url: "'.Url::toRoute(['/site/setcalendarview', 'for' => $salle->parametre_id, 'name' => 'home']).'",
                                data: {view: event.name, start: moment.format()},
                                dataType: "json",
                            });
                    } ',
                    'ajaxEvents' => yii\helpers\Url::to(['/cours-date/jsoncalendar', 'for' => $salle->parametre_id])
                ]); ?>
            </div>
        <?php } ?>
    </div>
    
</div>
