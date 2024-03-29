<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use yii\bootstrap\Alert;

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

    <?php if (Yii::$app->user->identity->id < 1000) { ?>
    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->cours_date_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->cours_date_id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <?php } ?>
    
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            //'cours_date_id',
            'date',
            'fkCours.fkNom.nom',
            [
                'label' => Yii::t('app', 'Is Actif'),
                'value' => ($model->fkCours->is_actif) ? 'Oui' : 'Non',
                'visible' => ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) ? true : false,
            ],
            [
                'label' => Yii::t('app', 'Fk Niveau'),
                'attribute' => 'fkCours.fkNiveau.nom',
                'visible' => ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) ? true : false,
            ],
            [
                'label' => Yii::t('app', 'Fk Type'),
                'attribute' => 'fkCours.fkType.nom',
                'visible' => ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) ? true : false,
            ],
            [
                'attribute' => 'fkCours.session',
                'visible' => ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) ? true : false,
            ],
            [
                'attribute' => 'fkCours.annee',
                'visible' => ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) ? true : false,
            ],
            [
                'format' => 'ntext',
                'attribute' => 'fkCours.description',
                'visible' => ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) ? true : false,
            ],
            'lieu',
            [
                'attribute' => 'coursHasMoniteurs',
                'value' => $selectedMoniteurs,
            ],
            'fkCours.participant_min',
            'fkCours.participant_max',
            'remarque',
        ],
    ]) ?>

    <?php /*if ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) { */ ?>
        <?= $this->render('/personnes/_participant', [
            'model' => $model,
            'viewAndId' => ['cours-date', $model->cours_date_id],
            'isInscriptionOk' => ($participantDataProvider->totalCount < $model->fkCours->participant_max) ? true : false,
            'dataClients' => $dataClients,
            'participantDataProvider' => $participantDataProvider,
            'parametre' => $parametre,
            'emails' => $emails,
            'listeEmails' => $listeEmails,
            'forPresenceOnly' => ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) ? false : true,
        ]) ?>
    <?php /*}*/ ?>

</div>
