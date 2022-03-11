<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use kartik\select2\Select2;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use webvimark\modules\UserManagement\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\MoniteursHasBareme */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="cours-moniteur-form">

    <div class="row">
        <div class="col-sm-12">
            <h3><?= Yii::t('app', 'Historique des barèmes') ?></h3>
            <?= Html::a(Yii::t('app', 'Ajouter un barème'), ['/moniteurs-has-bareme/create', 'fk_personne' => $model->personne_id], ['class' => 'btn btn-primary']) ?>
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
                'template'=>'{update}',
                'visibleButtons'=>[
                    'update' => User::canRoute(['/moniteurs-has-bareme/update']),
                ],
                'urlCreator' => function ($action, $model) {
                    if ($action === 'update') {
                        $data = [
                            'fk_personne' => $model->fk_personne,
                            'fk_bareme' => $model->fk_bareme,
                            'date_debut' => date('Y-m-d', strtotime($model->date_debut)),
                        ];
                        return Url::to(['/moniteurs-has-bareme/update', 'jsonData' => json_encode($data)]);
                    }
                }
            ],
        ],
    ]); ?>

</div>
