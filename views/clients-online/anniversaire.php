<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */

$this->title = Yii::t('app', 'Anniversaires') . ' - Vertic-Halle';
?>
<div class="site-index">
        
    <h2>Vertic-Halle : <?= Yii::t('app', 'Calendrier des anniversaires') ?></h2>

    <div class="row">
        <div class="col-sm-12">
            <?= Yii::t('app', 'Vous souhaitez effectuer une réservation dans un délai de moins de 2 semaines ou dans une horaire autre que ceux qui sont affichés ?') ?>
            <a class="btn btn-info" href="https://ecole.vertic-halle.ch/web/index.php?r=clients-online%2Fcreateanniversaire&ident=<?= $ident ?>&free=1&lang_interface=<?= Yii::$app->language ?>"><?= Yii::t('app', 'Horaire au choix') ?></a><br /><br />
        </div>
    </div>
    
    <div class="row">
        <?php $form = ActiveForm::begin(); ?>
        <div class="col-sm-3">
            <?= $form->field($model, 'parametre_id')->widget(Select2::class, [
                'options'=>[
                    'multiple' => false,
                ],
                'data' => \yii\helpers\ArrayHelper::map(\app\models\Parametres::find()->where('class_key = 16')->orderBy('tri')->all(), 'parametre_id', 'nom'),
                'pluginOptions'=>[
                    'initialize' => true,
                    'allowClear' => false,
                    'tags' => false,
                ],
            ])->label(false); ?>
        </div>
        <div class="col-sm-2">
            <?= Html::submitButton(Yii::t('app', 'Afficher'), ['btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
    <div class="row">
        <div class="col-md-12">
            <?= yii2fullcalendar\yii2fullcalendar::widget([
                'clientOptions' => [
                    'lang' => 'fr',
                    'defaultView' => Yii::$app->session->get('anni-cal-view'),
                    'weekNumbers' => true,
                    'defaultDate' => Yii::$app->session->get('anni-cal-debut'),
                    'eventTimeFormat' => 'H:mm',
                    'scrollTime' => '08:00:00',
                    'aspectRatio' => 1.6,
                    'allDaySlot' => false,
                    'eventLimit' => 3,
                    'nowIndicator' => true,
                    'validRange' => [
                        'start' => date('Y-m-d', strtotime(date('Y-m-d') . ' + 14 days')),
                    ],
                ],
                'options' => [
                    'id' => 'myCalendar' . $model->parametre_id,
                ],
                'header' => [
                    'center'=>'title',
                    'left'=>'prev,next',
                    'right'=>'listMonth,agendaDay,agendaWeek,month'
                ],
                'eventRender' => 'function(event, element) {
                    element.find(\'.fc-title\').append("<br/><br/><i>" + event.nonstandard + "</i>");
                } ',
                'eventAfterAllRender' => 'function(event) {
                    var moment = $(\'#myCalendar' . $model->parametre_id . '\').fullCalendar(\'getDate\');
                    $.ajax({
                            type: "POST",
                            cache: false,
                            url: "'.Url::toRoute(['/site/setcalendarview', 'for' => 'null', 'name' => 'anni', 'open' => true]).'",
                            data: {view: event.name, start: moment.format()},
                            dataType: "json",
                        });
                } ',
                'ajaxEvents' => yii\helpers\Url::to(['/cours-date/jsoncalannionline', 'for' => $model->parametre_id])
            ]); ?>
        </div>
    </div>
    
</div>
