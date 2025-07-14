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
                        'escapeMarkup' => new \yii\web\JsExpression("function(m) { return m; }"),
                    ],
                ]); ?>
            </div>
            <div class="col-sm-1">
                <?= Html::submitButton(Yii::t('app', 'Ajouter'), ['class' => 'btn btn-primary']); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <span class="glyphicon glyphicon-info-sign"></span> Pour modifier un barème, il est possible de cliquer sur la date ou sur le barème à côté de la case à cocher.
            </div>
        </div>
        
        <?php echo '<table id="array-check-all" class="table table-striped table-bordered"><tr><td></td>';
        foreach ($arrayData as $data) {
            echo '<th>' . Html::a(
                    $data['model']->date,
                    \yii\helpers\Url::to(['/cours-date/view', 'id' => $data['model']->cours_date_id, 'msg' => 'moniteur'])) . '</th>';
        }
        echo '</tr>';

        // ligne pour chaque moniteurs
        foreach ($arrayMoniteurs as $key => $moniteur) {
            $baremeActuel = $moniteur->fkMoniteur->getLetterBaremeFromDate(date('Y-m-d'));
            echo '<tr><td>' . $moniteur->fkMoniteur->nomPrenom . ' ' . $baremeActuel . ' ';
            $allChecked = true;
            $myCell = [];
            foreach ($arrayData as $data) {
                $dateCours = date('Ymd', strtotime($data['model']->date));
                $isChecked = (isset($data['moniteurs']) && array_key_exists($key, $data['moniteurs']) ? true : false);
                $allChecked = $allChecked && $isChecked;
                $baremeDate = '';
                foreach ($data['model']->coursHasMoniteurs as $coursHasMoniteur) {
                    if ($moniteur->fk_moniteur == $coursHasMoniteur->fk_moniteur) {
                        if (!is_null($coursHasMoniteur->fk_bareme)
                            || $moniteur->fkMoniteur->getLetterBaremeFromDate($data['model']->date) != $baremeActuel) {
                            $tolink = $coursHasMoniteur->letterBareme;
                        } else {
                            $tolink = $baremeActuel;
                        }
                        $baremeDate = Html::a(strip_tags($tolink),
                            \yii\helpers\Url::to(['/cours-date/changebaremefordate', 'fk_moniteur' => $moniteur->fk_moniteur, 'fk_cours_date' => $data['model']->cours_date_id])
                        );
                    }
                }
                $myCell[] = yii\bootstrap\BaseHtml::checkbox('datemoniteur[' . $dateCours . '][' . $data['model']->cours_date_id . '|' . $key . ']', $isChecked)
                    . ' ' . $baremeDate;
            }
            echo '<div class="pull-right">' . Html::checkbox(null, $allChecked, [
                    'class' => 'check-all-line',
                ]) . ' ' . Yii::t('app', 'Tous/aucun') . '</div>';
            echo  '</td><td>';
            echo implode('</td><td>', $myCell);
            echo '</td></tr>';
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
