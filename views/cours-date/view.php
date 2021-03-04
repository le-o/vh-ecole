<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Alert;
use webvimark\modules\UserManagement\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\CoursDate */

$this->title = Yii::t('app', 'Planification').' '.$model->fkCours->fkNom->nom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours'), 'url' => ['/cours/view', 'id' => $model->fk_cours]];
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

<div class="cours-date-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (User::canRoute(['/cours-date/advanced'])) { ?>
    
    <?= $this->render('_form', [
        'alerte' => $alerte,
        'model' => $model,
        'dataCours' => $dataCours,
        'dataMoniteurs' => $dataMoniteurs,
        'selectedMoniteurs' => $selectedMoniteurs,
        'modelParams' => $modelParams,
    ]) ?>
    
    <?php } else { ?>
    
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            //'cours_date_id',
            'date',
            'fkCours.fkNom.nom',
            [
                'label' => Yii::t('app', 'Statut'),
                'attribute' => 'fkCours.fkStatut.nom',
                'visible' => (in_array($model->fkCours->fk_type, Yii::$app->params['coursPonctuelUnique'])) ? true : false,
            ],
            [
                'label' => Yii::t('app', 'Fk Niveau'),
                'attribute' => 'fkCours.fkNiveau.nom',
                'visible' => (in_array($model->fkCours->fk_type, Yii::$app->params['coursPonctuelUnique'])) ? true : false,
            ],
            [
                'label' => Yii::t('app', 'Fk Type'),
                'attribute' => 'fkCours.fkType.nom',
                'visible' => (in_array($model->fkCours->fk_type, Yii::$app->params['coursPonctuelUnique'])) ? true : false,
            ],
            [
                'attribute' => 'fkCours.session',
                'visible' => (in_array($model->fkCours->fk_type, Yii::$app->params['coursPonctuelUnique'])) ? true : false,
            ],
            [
                'attribute' => 'fkCours.annee',
                'visible' => (in_array($model->fkCours->fk_type, Yii::$app->params['coursPonctuelUnique'])) ? true : false,
            ],
            [
                'format' => 'ntext',
                'attribute' => 'fkCours.description',
                'visible' => (in_array($model->fkCours->fk_type, Yii::$app->params['coursPonctuelUnique'])) ? true : false,
            ],
            [
                'label' => Yii::t('app', 'Lieu'),
                'attribute' => 'fkLieu.nom',
            ],
            [
                'attribute' => 'coursHasMoniteurs',
                'value' => $listeMoniteurs,
            ],
            'fkCours.participant_min',
            'fkCours.participant_max',
            'remarque',
        ],
    ]) ?>
    
    <?php } ?>

    <?= $this->render('/personnes/_participant', [
        'model' => $model,
        'viewAndId' => ['cours-date', $model->cours_date_id],
        'isInscriptionOk' => (User::hasRole('Admin') || $participantDataProvider->totalCount < $model->fkCours->participant_max) ? true : false,
        'dataClients' => $dataClients,
        'participantDataProvider' => $participantDataProvider,
        'participantIDs' => $participantIDs,
        'parametre' => $parametre,
        'emails' => $emails,
        'forPresenceOnly' => (in_array($model->fkCours->fk_type, Yii::$app->params['coursPonctuelUnique'])) ? false : true,
        'hasPlanification' => true,
    ]) ?>

</div>
