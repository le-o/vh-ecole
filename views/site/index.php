<?php

use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'VH Gestion des cours';
  
?>
<div class="site-index">
    
    <h2>VH Calendrier des cours</h2>
    
    <?= yii2fullcalendar\yii2fullcalendar::widget([
        'clientOptions' => [
            'lang' => 'fr',
            'defaultView' => Yii::$app->session->get('home-cal-view'),
            'weekNumbers' => true,
            'defaultDate' => Yii::$app->session->get('home-cal-debut'),
            'eventTimeFormat' => 'H:mm',
            'scrollTime' => '08:00:00',
            'aspectRatio' => 1.6,
            'allDaySlot' => false,
            'eventLimit' => 3,
        ],
        'options' => [
            'id' => 'myCalendar',
        ],
        'header' => [
            'center'=>'title',
            'left'=>'prev,next today', 
            'right'=>'agendaDay,agendaWeek,month'
        ],
        'eventRender' => 'function(event, element) {
            element.find(\'.fc-title\').append("<br/><br/><i>" + event.description + "</i>");
        } ',
        'eventAfterAllRender' => 'function(event) {
            var moment = $(\'#myCalendar\').fullCalendar(\'getDate\');
            $.ajax({
                    type: "POST",
                    cache: false,
                    url: "'.Url::toRoute('/site/setcalendarview').'",
                    data: {view: event.name, start: moment.format()},
                    dataType: "json",
                });
        } ',
        'ajaxEvents' => yii\helpers\Url::to(['/cours-date/jsoncalendar'])
    ]); ?>
    
</div>
