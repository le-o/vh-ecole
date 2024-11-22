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
        'showFooter' => $withSum,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            'date',
            [
                'label' => Yii::t('app', 'Nom'),
                'value' => function($data) {
                    if (isset($data->fkCours)) return $data->fkCours->fkNom->nom;
                    return $data['nom'];
                },
            ],
            'heure_debut',
            [
                'label' => Yii::t('app', 'Heure Fin'),
                'value' => function($data) {
                    return (isset($data->heureFin) ? $data->heureFin : $data['heure_fin']);
                },
            ],
            [
                'label' => Yii::t('app', 'Lieu'),
                'value' => function($data) {
                    return (isset($data->fkLieu) ? $data->fkLieu->nom : $data['lieu']);
                },
                'footer' => '<strong>' . Yii::t('app', 'Total') . '</strong>',
            ],
            [
                'attribute' => 'bareme',
                'visible' => $withSum,
            ],
            [
                'attribute' => 'duree',
                'format' => ['decimal', 2],
                'footer' => '<strong>' . $sum . '</strong>',
            ],
            [
                'attribute' => 'remarque',
                'visible' => !$withSum,
            ],
        ],
    ]); ?>

</div>
