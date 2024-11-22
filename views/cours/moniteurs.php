<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\bootstrap\Alert;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Cours */
/* @var $form yii\widgets\ActiveForm */
$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Cours',
]) . ' ' . $model->fkNom->nom;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Cours'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->cours_id, 'url' => ['view', 'id' => $model->cours_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Moniteurs');

$this->registerJsFile(
    '@web/js/vh-script.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);
?>
<div class="cours-moniteurs">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($alerte != '') {
        echo Alert::widget([
            'options' => [
                'class' => 'alert-danger',
            ],
            'body' => $alerte,
        ]); 
    } ?>

    <div class="cours-form">

        <?php $form = ActiveForm::begin(); ?>
        
        <div class="row" style="padding-bottom: 20px;">
            <div class="col-sm-4">
                <?= Select2::widget([
                    'name' => 'new_moniteur',
                    'data' => $dataMoniteurs,
                    'options' => ['placeholder' => Yii::t('app', 'Ajouter moniteur ...')],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'tags' => true,
                        'style' => 'width: 80%;',
                    ],
                ]); ?>
            </div>
            <div class="col-sm-1">
                <?= Html::submitButton(Yii::t('app', 'Ajouter'), ['class' => 'btn btn-primary']); ?>
            </div>
        </div>
        
        <?php echo '<table id="array-check-all" class="table table-striped table-bordered"><tr><td></td>';
        foreach ($arrayData as $data) {
            echo '<th>'.$data['model']->date.'</th>';
        }
        echo '</tr>';

        // ligne pour chaque moniteurs
        foreach ($arrayMoniteurs as $key => $moniteur) {
            echo '<tr><td>' . $moniteur->fkMoniteur->nom . ' ' . $moniteur->fkMoniteur->prenom . ' ';
            $allChecked = true;
            $myCell = '';
            foreach ($arrayData as $data) {
                $dateCours = date('Ymd', strtotime($data['model']->date));
                $isChecked = (isset($data['moniteurs']) && array_key_exists($key, $data['moniteurs']) ? true : false);
                $allChecked = $allChecked && $isChecked;
                $myCell .= '<td>'.yii\bootstrap\BaseHtml::checkbox('datemoniteur[' . $dateCours . '][' . $data['model']->cours_date_id . '|' . $key . ']', $isChecked).'</td>';
            }
            echo '<div class="pull-right">' . Html::checkbox(null, $allChecked, [
                    'class' => 'check-all-line',
                ]) . ' ' . Yii::t('app', 'Tous/aucun') . '</div>';
            echo  '</td>';
            echo $myCell;
            echo '</tr>';
        }
        echo '</table>';
        ?>

        <div class="form-group">
            <?= Html::checkbox('withNotification', true, ['label' => Yii::t('app', 'Avec email aux moniteurs')]) ?><br />
            <?= Html::submitButton(Yii::t('app', 'Enregistrer'), ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
