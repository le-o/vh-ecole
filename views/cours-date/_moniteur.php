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

<div class="cours-moniteur-form">
    
    <?= GridView::widget([
        'dataProvider' => $coursDateDataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            'date',
            'fkCours.fkNom.nom',
            'heure_debut',
            [
                'label' => Yii::t('app', 'Heure Fin'),
                'value' => function($data) {
                    return $data->heureFin;
                },
            ],
            'lieu',
            'duree',
            'remarque',
            
//            ['class' => 'yii\grid\ActionColumn',
//                'template'=>'{coursView}',
//                'buttons'=>[
//                    'coursView' => function ($model, $key, $index) {
//                    	return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Url::to(['/cours/view', 'id' => $index]), [
//							'title' => Yii::t('app', 'Voir'),
//						]);
//                    }
//                ],
//            ],
        ],
    ]); ?>

</div>
