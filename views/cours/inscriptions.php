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

<style>
.flyover {
   /*left: 150%;*/
   overflow: hidden;
   position: fixed;
   width: 20%;
   opacity: 0.9;
   z-index: 1050;
   transition: left 0.6s ease-out 0s;
   display: none;
}
</style>

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
    
    <div id="msg" class="alert alert-success flyover" role="alert"></div>

    <div class="cours-form">

        <?php $form = ActiveForm::begin(); ?>
        
        <div class="row" style="padding-bottom: 20px;">
            <div class="col-sm-4">
                <?= Select2::widget([
                    'name' => 'new_inscription',
                    'data' => $dataParticipants,
                    'options' => ['placeholder' => Yii::t('app', 'Ajouter participant ...')],
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
                $isChecked = false;
                if (isset($data['participants']) && array_key_exists($key, $data['participants']) && $data['participants'][$key]->fk_statut == Yii::$app->params['partInscrit']) {
                    $isChecked = true;   
                }
                echo '<td>'.yii\bootstrap\BaseHtml::checkbox('dateparticipant['.$dateCours.']['.$data['model']->cours_date_id.'|'.$key.']', $isChecked, ['value' => $data['model']->cours_date_id.'|'.$key]).'</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        echo Html::hiddenInput('allParticipants', json_encode($arrayParticipants));
        ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', 'Enregistrer'), ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
    <?php if (true) {
        $script = '
        jQuery(document).ready(function() {
            $(\'input[name^="dateparticipant"]\').click(function () {
                var key = $(this).attr("value");
                var isChecked = $(this).is(":checked");
                console.log(key);
                $.ajax({
                    type: "POST",
                    url: "'.yii\helpers\Url::to(['/cours/toggleinscription']).'", 
                    dataType: "json",
                    data: {"key": key, "isChecked": isChecked},
                    success: function(data) {
                        $("#msg").html(data.message).toggle();
                        $("#msg").delay(600).fadeOut("slow");
                    },
                });
            });
        });';
        $this->registerJs($script, \yii\web\View::POS_END);
    } ?>
</div>
