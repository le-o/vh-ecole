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
$this->params['breadcrumbs'][] = Yii::t('app', 'Participants');
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
        
        <?php echo '<table class="table table-striped table-bordered"><tr><td></td>';
        foreach ($arrayData as $data) {
            echo '<th>'.$data['model']->date.'</th>';
        }
        echo '</tr>';

        // ligne pour chaque participants
        foreach ($arrayParticipants as $key => $participant) {
            echo '<tr><td>'.$participant->fkPersonne->nom.' '.$participant->fkPersonne->prenom.'</td>';
            foreach ($arrayData as $data) {
                $dateCours = date('Ymd', strtotime($data['model']->date));
                if (isset($data['participants']) && array_key_exists($key, $data['participants']) && $data['participants'][$key]->fk_statut != Yii::$app->params['partDesinscrit']) {
                    $isPresent = $data['participants'][$key]->is_present;
                    $options = ($data['participants'][$key]->fk_statut == Yii::$app->params['partDesinscrit']) ? ['disabled' => ''] : [];
                    echo '<td>'.yii\bootstrap\BaseHtml::checkbox('dateparticipant['.$dateCours.']['.$data['model']->cours_date_id.'|'.$key.']', $isPresent, $options).'</td>';
                } else echo '<td style="background-color: grey;"></td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', 'Enregistrer'), ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
