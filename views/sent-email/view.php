<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\SentEmail */

$this->title = $model->to;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sent Emails'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="sent-email-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'sent_email_id',
                'visible'=>Yii::$app->user->isSuperadmin,
            ],
            'from:email',
            'to:email',
            'bcc:email',
            'sent_date:datetime',
            'subject',
            'body:html',
            'email_params:ntext',
        ],
    ]) ?>

</div>
