<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap\Alert;
use webvimark\modules\UserManagement\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\Personnes */

$this->title = $model->nom.' '.$model->prenom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Client'), 'url' => ['index']];
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

    <?php if (User::canRoute(['personnes/update']) || User::canRoute(['personnes/delete'])) { ?>
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
            'nopersonnel',
            [
                'label' => Yii::t('app', 'Statut'),
                'value' => $model->fkStatut->nom,
            ],
            [
                'label' => Yii::t('app', 'Finances'),
                'value' => (isset($model->fkFinance) ? $model->fkFinance->nom : ''),
            ],
            [
                'attribute' => 'fk_salle_admin',
                'value' => (isset($model->fkSalleadmin) ? $model->fkSalleadmin->nom : ''),
            ],
            [
                'label' => Yii::t('app', 'Type'),
                'value' => $model->fkType->nom,
            ],
            'societe',
            'nom',
            'prenom',
            [
                'attribute' => 'fk_sexe',
                'value' => (isset($model->fkSexe) ? $model->fkSexe->nom : ''),
            ],
            'adresse1',
            'numeroRue',
            'adresse2',
            'npa',
            'localite',
            [
                'attribute' => 'fk_pays',
                'value' => (isset($model->fkPays) ? $model->fkPays->nom : ''),
            ],
            'telephone',
            'telephone2',
            [
                'attribute' => 'fk_langue_mat',
                'value' => (isset($model->fkLangueMat) ? $model->fkLangueMat->nom : ''),
            ],
            'email:email',
            'date_naissance',
            [
                'attribute' => 'fk_nationalite',
                'value' => (isset($model->fkNationalite) ? $model->fkNationalite->nom : ''),
            ],
            'no_avs',
            [
                'label' => Yii::t('app', 'interlocuteur'),
                'format' => 'html',
                'value' => $model->getInterlocuteurs(),
            ],
            [
                'label' => Yii::t('app', 'est interlocuteur de'),
                'format' => 'html',
                'value' => $model->getIsInterlocuteursFrom(),
            ],
            'informations:ntext',
            'suivi_client:ntext',
            [
                'attribute' => 'fkLanguesNoms',
                'label' => Yii::t('app', 'Langues parlées'),
                'visible' => in_array($model->fk_type, Yii::$app->params['typeEncadrant']),
            ],
            [
                'attribute' => 'complement_langue',
                'visible' => in_array($model->fk_type, Yii::$app->params['typeEncadrant']),
            ],
        ],
    ]) ?>
    
    <?php if (in_array($model->fk_type, Yii::$app->params['typeEncadrant'])) {
        if (User::canRoute(['/moniteurs-has-bareme/index'])) {
            echo $this->render('/moniteurs-has-bareme/_moniteur', [
                'model' => $model,
                'moniteursHasBaremeDataProvider' => $moniteursHasBaremeDataProvider,
            ]);
        }

        echo '<br /><h3>'.Yii::t('app', 'Mes cours comme moniteurs').'</h3>';
        echo $this->render('/cours-date/_moniteur', [
            'coursDateDataProvider' => $coursDateDataProvider,
            'withSum' => false,
            'sum' => 0,
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
