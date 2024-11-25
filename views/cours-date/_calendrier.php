<?php

/* @var $this yii\web\View */

$this->title = 'VH Calendrier des cours';
?>
<div class="calendrier">

    <h2>VH Calendrier des cours</h2>
    
    <?= yii2fullcalendar\yii2fullcalendar::widget([
        'clientOptions' => [
            'defaultView' => 'agendaWeek',
            'weekNumbers' => true,
            'defaultDate' => date('Y-m-d'),
            'eventTimeFormat' => 'H:mm',
            'scrollTime' => '08:00:00',
            'aspectRatio' => 1.6,
            'allDaySlot' => false,
            'eventLimit' => 3,
        ],
        'options' => [
            'id' => 'myCalendar',
            'lang' => 'fr',
        ],
        'header' => [
            'center'=>'title',
            'left'=>'prev,next today', 
            'right'=>'agendaDay,agendaWeek,month'
        ],
        'eventRender' => 'function(event, element) {
            element.find(\'.fc-title\').append("<br/><br/><i>" + event.description + "</i>");
        } ',
        'ajaxEvents' => yii\helpers\Url::to(['/cours-date/jsoncalendar'])
    ]); ?>

</div>
