<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use leo\modules\UserManagement\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\Moniteurs */

\yii\web\YiiAsset::register($this);
?>
<div class="moniteurs-view">

    <?php if (User::canRoute(['/moniteurs/update']) || User::canRoute(['/moniteurs/delete'])) { ?>
        <p>
            <?php if (!empty($modelMoniteur)) { ?>
                <?= Html::a(Yii::t('app', 'Modifier données moniteur'), ['/moniteurs/update', 'id' => $modelMoniteur->moniteur_id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a(Yii::t('app', 'Supprimer données moniteur'), ['/moniteurs/delete', 'id' => $modelMoniteur->moniteur_id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                    ],
                ]) ?>
            <?php } else { ?>
                <?= Html::a(Yii::t('app', 'Ajouter données moniteur'), ['/moniteurs/create', 'fk_personne' => $model->personne_id], ['class' => 'btn btn-success']) ?>
            <?php } ?>
        </p>
    <?php }
    if (in_array($model->fk_type, Yii::$app->params['typeEncadrant'])) {
        if (!empty($modelMoniteur)) { ?>
            <?= DetailView::widget([
                'model' => $modelMoniteur,
                'attributes' => [
                    'diplome:ntext',
                    'remarque:ntext',
                    'no_cresus',
                    'experience_cours:date',
                    'animateur_asse:date',
                    'parcours:date',
                    'methode_VCS:date',
                    'js1_escalade:date',
                    'js_allround:date',
                    'encadrant_asse:date',
                    'instructeur_asse:date',
                    'js2_escalade:date',
                    'js3_escalade:date',
                    'referent_asse:date',
                    'expert_asse:date',
                    'prof_escalade:date',
                    [
                        'label' => Yii::t('app', 'Formations'),
                        'format' => 'raw',
                        'value' => function ($modelMoniteur) {
                            $display = [];
                            $formations = $modelMoniteur->moniteursHasFormations;
                            foreach ($formations as $f) {
                                $display[] = $f->fkFormation->nom;
                            }
                            if (empty($display)) {
                                return Yii::t('app', 'Aucune donnée');
                            }
                            return implode('<br /> ', $display);
                        }
                    ],
                ],
            ]) ?>
        <?php }

        echo $baremeSuggere;

        if (User::canRoute(['/moniteurs-has-bareme/index'])) { ?>
            <?= $this->render('/moniteurs-has-bareme/_moniteur', [
                'model' => $model,
                'moniteursHasBaremeDataProvider' => $moniteursHasBaremeDataProvider,
            ]) ?>
        <?php } ?>

        <br />
        <h3><?= Yii::t('app', 'Mes cours comme moniteurs') ?></h3>
        <?= $this->render('/cours-date/_moniteur', [
            'coursDateDataProvider' => $coursDateDataProvider,
            'withSum' => false,
            'sum' => 0,
            ]) ?>
    <?php } ?>

</div>
