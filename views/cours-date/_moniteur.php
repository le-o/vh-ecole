<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use kartik\select2\Select2;
use yii\bootstrap\Alert;
use yii\helpers\Url;

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
            [
                'label' => Yii::t('app', 'Lieu'),
                'value' => 'fkLieu.nom',
            ],
            'duree',
            'remarque',
        ],
    ]); ?>

</div>
