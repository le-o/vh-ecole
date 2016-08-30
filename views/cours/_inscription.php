<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use kartik\select2\Select2;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\CoursDate */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="cours-participant-form">

    <?php if (Yii::$app->user->identity->id < 1000) { ?>
    <div class="row">

        <?php $form = ActiveForm::begin(); ?>
            <div class="col-sm-4">
                <?= Select2::widget([
                    'name' => 'new_cours',
                    'data' => $dataCours,
                    'options' => ['placeholder' => Yii::t('app', 'Ajouter cours ...')],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'tags' => true,
                        'style' => 'width: 80%;',
                    ],
                ]); ?>
            </div>
            <div class="col-sm-1">
                <?= Html::submitButton(Yii::t('app', 'Ajouter'), ['class' => 'btn btn-primary']); ?>
            </div>
        <?php ActiveForm::end(); ?>
        
    </div>
    <?php } ?>
    
    <?= GridView::widget([
        'dataProvider' => $coursDataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'label' => Yii::t('app', 'Fk Type'),
                'attribute' => 'fkType.nom',
            ],
            [
                'label' => Yii::t('app', 'Fk Nom'),
                'attribute' => 'fkNom.nom',
            ],
            'duree',
            'session',
            'annee',
            'date',
            
            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{coursView}',
                'buttons'=>[
                    'coursView' => function ($model, $key, $index) {
                        $key = explode('|', $index);
                        if ($key[1] == Yii::$app->params['coursPlanifie']) {
                            $page = '/cours/view';
                        } else {
                            $page = '/cours-date/view';
                        }
                    	return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to([$page, 'id' => $key[0]]), [
							'title' => Yii::t('app', 'Voir'),
						]);
                    }
                ],
            ],
        ],
    ]); ?>

</div>
