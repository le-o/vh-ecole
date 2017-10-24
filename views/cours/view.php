<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\bootstrap\Alert;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Cours */

$this->title = $model->fkNom->nom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?php if (!empty($alerte)) {
    echo Alert::widget([
        'options' => [
            'class' => 'alert-'.$alerte['class'],
        ],
        'body' => $alerte['message'],
    ]); 
} ?>

<div class="cours-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->user->identity->id < 1000) { ?>
    
     <?= $this->render('_form', [
	    'alerte' => '',
        'model' => $model,
        'modelParams' => $modelParams,
    ]) ?>
    
    <?php } else { ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'cours_id',
            [
                'label' => Yii::t('app', 'Is Actif'),
                'value' => ($model->is_actif) ? 'Oui' : 'Non',
            ],
            [
                'label' => Yii::t('app', 'Fk Niveau'),
                'attribute' => 'fkNiveau.nom',
            ],
            [
                'label' => Yii::t('app', 'Fk Type'),
                'attribute' => 'fkType.nom',
            ],
            [
                'label' => Yii::t('app', 'Fk Nom'),
                'attribute' => 'fkNom.nom',
            ],
//            'duree',
            'session',
            'annee',
            [
                'label' => Yii::t('app', 'Fk Saison'),
                'attribute' => 'fkSaison.nom',
            ],
            [
                'label' => Yii::t('app', 'Fk Semestre'),
                'attribute' => 'fkSemestre.nom',
            ],
//            'prix',
            'participant_min',
            'participant_max',
            'description:ntext',
        ],
    ]) ?>
    
    <?php } ?>

    
    <?= $this->render('/personnes/_participant', [
        'alerte' => '',
        'model' => $model,
        'viewAndId' => ['cours', $model->cours_id],
        'isInscriptionOk' => (Yii::$app->user->identity->id < 500 || $participantDataProvider->totalCount < $model->participant_max) ? true : false,
        'dataClients' => $dataClients,
        'participantDataProvider' => $participantDataProvider,
        'parametre' => $parametre,
        'emails' => $emails,
        'listeEmails' => $listeEmails,
        'forPresenceOnly' => false,
    ]) ?>
    
    
    <?= GridView::widget([
        'dataProvider' => $coursDateProvider,
        'rowOptions' => function($model) {
            if ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel'] && $model->nombreClientsInscrits >= $model->fkCours->participant_max) return ['class' => 'warning'];
            return [];
        },
        'columns' => [
            'date',
            'heure_debut',
            [
                'label' => Yii::t('app', 'Heure Fin'),
                'value' => function($data) {
                    return $data->heureFin;
                },
            ],
            'duree',
            [
                'attribute' => 'prix',
                'visible' => (Yii::$app->user->identity->id < 1100) ? true : false,
            ],
            'lieu',
            [
                'label' => Yii::t('app', 'Fk Moniteur'),
                'value' => function($data) {
                    $array_moniteurs = [];
                    foreach ($data->coursHasMoniteurs as $moniteur) {
                        $array_moniteurs[] = $moniteur->fkMoniteur->nom.' '.$moniteur->fkMoniteur->prenom;
                    }
                    return implode(', ', $array_moniteurs);
                },
            ],
            'remarque',
            
            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{coursDateView} {coursDateUpdate} {coursDateDelete}',
                'visibleButtons'=>[
                    'coursDateDelete' => (Yii::$app->user->identity->id < 1000) ? true : false,
                ],
                'buttons'=>[
                    'coursDateView' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-user"></span>', Url::to(['/cours-date/view', 'id' => $key]), [
                            'title' => Yii::t('yii', 'View'),
                        ]);
                    },
                    'coursDateDelete' => function ($url, $model, $key) use ($coursDateProvider) {
                        if ($coursDateProvider->getCount() == 1) {
                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', Url::to(['/cours-date/delete', 'id' => $key]),
                                ['data' => [
                                    'method' => 'post',
                                    'title' => Yii::t('yii', 'Delete'),
                                    'confirm' => Yii::t('app', 'Vous allez supprimer le cours ainsi que tous les participants. OK?'),
                                ],
                            ]);
                        } else {
                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', Url::to(['/cours-date/delete', 'id' => $key]),
                                ['data' => [
                                    'method' => 'post',
                                    'title' => Yii::t('yii', 'Delete'),
                                ],
                            ]);
                        }
                    },
                ],
            ],
        ],
        'caption' => '<div class="row"><div class="col-sm-2">'.Yii::t('app', 'Planification prévue').'</div>'.
                        '<div class="col-sm-6">'.Html::a(Yii::t('app', 'Create Cours Date'), ['cours-date/create', 'cours_id' => $model->cours_id], ['class' => 'btn btn-primary'.$displayActions]).'
                        '.Html::a(Yii::t('app', 'Create Cours Date Multiple'), ['cours-date/recursive', 'cours_id' => $model->cours_id], ['class' => 'btn btn-info'.$displayActions.$createR]).'
                        '.Html::a(Yii::t('app', 'Gestion moniteurs'), ['cours/gestionmoniteurs', 'cours_id' => $model->cours_id], ['class' => 'btn btn-default'.$displayActions]).'
                        '.Html::a(Yii::t('app', 'Gestion présences'), ['cours/gestionpresences', 'cours_id' => $model->cours_id], ['class' => 'btn btn-default'.$displayActions]).'</div>',
        'summary' => '',
    ]); ?>

</div>
