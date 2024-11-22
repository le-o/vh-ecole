<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SentEmailSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Sent Emails');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sent-email-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'sent_email_id',
                'visible'=>Yii::$app->user->isSuperadmin,
            ],
            'from:email',
            'to:email',
            'bcc',
            'sent_date:datetime',
            'subject',
            'body:html',
            //'email_params:ntext',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}',
            ],
        ],
    ]); ?>


</div>
