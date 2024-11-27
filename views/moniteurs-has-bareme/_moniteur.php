<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use kartik\select2\Select2;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use leo\modules\UserManagement\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\MoniteursHasBareme */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="cours-moniteur-form">

    <div class="row">
        <div class="col-sm-12">
            <h3><?= Yii::t('app', 'Historique des barèmes') ?></h3>
            <?php if (User::canRoute(['/moniteurs-has-bareme/create'])) {
                echo Html::a(Yii::t('app', 'Ajouter un barème'), ['/moniteurs-has-bareme/create', 'fk_personne' => $model->personne_id], ['class' => 'btn btn-primary']);
            } ?>
        </div>
    </div>
    
    <?= GridView::widget([
        'dataProvider' => $moniteursHasBaremeDataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'label' => Yii::t('app', 'Barème'),
                'attribute' => 'fkBareme.nom',
            ],
            'date_debut',
            'date_fin',

            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{update} {delete}',
                'visibleButtons'=>[
                    'update' => User::canRoute(['/moniteurs-has-bareme/update']),
                    'delete' => function ($model, $key, $index) {
                        return ($index == 0 && User::canRoute(['/moniteurs-has-bareme/delete']) ? true : false);
                    },
                ],
                'urlCreator' => function ($action, $model, $data, $index) {
                    if ($action === 'update') {
                        return Url::to(['/moniteurs-has-bareme/update', 'jsonData' => json_encode($data)]);
                    }
                    if ($action === 'delete') {
                        return Url::to(['/moniteurs-has-bareme/delete', 'jsonData' => json_encode($data)]);
                    }
                }
            ],
        ],
    ]); ?>

</div>
