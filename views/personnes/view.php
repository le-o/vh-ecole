<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap\Alert;

/* @var $this yii\web\View */
/* @var $model app\models\Personnes */

$this->title = $model->nom.' '.$model->prenom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Personnes'), 'url' => ['index']];
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

<div class="personnes-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->user->identity->id < 1000) { ?>
    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->personne_id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->personne_id], [
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
            'personne_id',
            [
                'label' => Yii::t('app', 'Statut'),
                'value' => $model->fkStatut->nom,
            ],
            [
                'label' => Yii::t('app', 'Type'),
                'value' => $model->fkType->nom,
            ],
            [
                'label' => Yii::t('app', 'Niveau formation'),
                'value' => (isset($model->fkFormation)) ? $model->fkFormation->nom : '',
            ],
            'noclient_cf',
            'societe',
            'nom',
            'prenom',
            'adresse1',
            'adresse2',
            'npa',
            'localite',
            'telephone',
            'telephone2',
            'email:email',
            'email2:email',
            'date_naissance',
            [
                'label' => Yii::t('app', 'interlocuteur'),
                'format' => 'html',
                'value' => $model->getInterlocuteurs(),
            ],
            'informations:ntext',
            'carteclient_cf',
            'categorie3_cf',
            'soldefacture_cf'
        ],
    ]) ?>
    
    <?php if ($model->fk_type == Yii::$app->params['typeEncadrant']) {
        echo '<br /><h3>'.Yii::t('app', 'Mes cours comme moniteurs').'</h3>';
        echo $this->render('/cours-date/_moniteur', [
            'coursDateDataProvider' => $coursDateDataProvider,
        ]);
    } ?>
    
    <br /><h3><?= Yii::t('app', 'Mes cours comme participants') ?></h3>
    <?= $this->render('/cours/_inscription', [
        'dataCours' => $dataCours,
        'personneModel' => $model,
        'coursDataProvider' => $coursDataProvider,
        'parametre' => $parametre,
        'emails' => $emails,
    ]) ?>

</div>
