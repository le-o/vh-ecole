<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\bootstrap\Alert;
use yii\helpers\Url;
use leo\modules\UserManagement\models\User;

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

    <?php if (User::canRoute(['cours/update'])) { ?>
    
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
                'label' => Yii::t('app', 'Statut'),
                'attribute' => 'fkStatut.nom',
            ],
            [
                'label' => Yii::t('app', 'Fk Salle'),
                'attribute' => 'fkSalle.nom',
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
        'isInscriptionOk' => (User::hasRole('Admin') || $participantDataProvider->totalCount < $model->participant_max) ? true : false,
        'dataClients' => $dataClients,
        'participantDataProvider' => $participantDataProvider,
        'participantIDs' => $participantIDs,
        'parametre' => $parametre,
        'emails' => $emails,
        'forPresenceOnly' => false,
        'hasPlanification' => (0 == $coursDateProvider->totalCount) ? false : true,
    ]) ?>
    
    <?php
    $actionButtons = (User::canRoute(['cours-date/create'])) ? Html::a(Yii::t('app', 'Create Cours Date'), ['cours-date/create', 'cours_id' => $model->cours_id], ['class' => 'btn btn-primary']) : '';
    $actionButtons .= (User::canRoute(['cours-date/recursive'])) ? '&nbsp;'.Html::a(Yii::t('app', 'Create Cours Date Multiple'), ['cours-date/recursive', 'cours_id' => $model->cours_id], ['class' => 'btn btn-info']) : '';
    $actionButtons .= (User::canRoute(['cours/gestionmoniteurs'])) ? '&nbsp;'.Html::a(Yii::t('app', 'Gestion moniteurs'), ['cours/gestionmoniteurs', 'cours_id' => $model->cours_id], ['class' => 'btn btn-default']) : '';
    $actionButtons .= (User::canRoute(['cours/gestionpresences'])) ? '&nbsp;'.Html::a(Yii::t('app', 'Gestion présences'), ['cours/gestionpresences', 'cours_id' => $model->cours_id], ['class' => 'btn btn-default']) : '';
    ?>
    <?= GridView::widget([
        'dataProvider' => $coursDateProvider,
        'rowOptions' => function($model) {
            if ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel'] && $model->getNombreClientsInscrits() >= $model->fkCours->participant_max) return ['class' => 'warning'];
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
                'visible' => User::canRoute(['/cours/advanced']),
            ],
            [
                'label' => Yii::t('app', 'Lieu'),
                'attribute' => 'fkLieu.nom',
            ],
            [
                'label' => Yii::t('app', 'Fk Moniteur'),
                'format' => 'raw',
                'value' => function($data) {
                    $coursdate = date('Y-m-d', strtotime($data->date));
                    $array_moniteurs = [];
                    foreach ($data->coursHasMoniteurs as $moniteur) {
                        $array_moniteurs[] = $moniteur->fkMoniteur->nom . ' ' . $moniteur->fkMoniteur->prenom . ' ' . $moniteur->letterBareme;
                    }
                    return implode(', ', $array_moniteurs);
                },
            ],
            'remarque',
            
            ['class' => 'yii\grid\ActionColumn',
                'template'=>'{coursDateView} {coursDateUpdate} {coursDateDelete}',
                'visibleButtons'=>[
                    'coursDateDelete' => User::canRoute(['/cours-date/delete']),
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
                        '<div class="col-sm-8">'.$actionButtons.'</div>',
        'summary' => '',
    ]); ?>

</div>
