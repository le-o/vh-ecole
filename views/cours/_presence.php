<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Cours */

$this->title = Yii::t('app', 'Cours').' '.$model->fkNom->nom.' '.$model->fkNiveau->nom.' - '.
        $model->fkJoursNoms.' '.$model->firstCoursDate->heure_debut.' '.$model->fkSaison->nom;

?>

<div class="cours-view">

    <h2><?= Html::encode($this->title) ?></h2>

    <?php foreach ($decoupage as $coursDate) { ?>
        <table>
            <tr class="entete">
                <td colspan="5" style="text-align: left;">
                    <span style="font-weight: normal;">
                        <?= (isset($model->nextCoursDate)) ? $model->nextCoursDate->getCoursHasMoniteursListe($model->nextCoursDate->coursHasMoniteurs, '<br />') : '' ?>
                    </span>
                </td>
                <?php
                foreach ($coursDate as $date) {
                    if ($date->date != '')
                        echo '<td class="date" style="width:45px;">'.date('d.m', strtotime($date->date)).'</td>';
                    else 
                        echo '<td class="date" style="width:45px;"></td>';
                }
                ?>
                <td><?= Yii::t('app', 'Infos diverses') ?></td>
            </tr>
            <?php
            $i = 0;
            foreach ($participants as $part) {
                $i++;
                echo '<tr>';
                echo '<td class="num">'.$i.'</td>';
                echo '<td>'.$part->nom.'</td>';
                echo '<td>'.$part->prenom.'</td>';
                echo '<td class="num" style="text-align:right;">'.$part->age.'</td>';
                echo '<td nowrap="nowrap">'.$part->statutPart.'</td>';
                
                foreach ($coursDate as $pos => $date) {
                    $pres = $date->getForPresence($part->personne_id);
                    if (date('Y-m-d', strtotime($date->date)) <= date('Y-m-d')) {
                        if ($pres == false) {
                            echo '<td style="background-color:gray; background-image: repeating-linear-gradient(315deg, transparent, transparent 3px, rgba(255,255,255,.5) 3px, rgba(255,255,255,.5) 6px);"></td>';
                        } elseif (!empty($pres)) {
                            if ($pres->is_present == true) {
                                echo '<td style="text-align: center;">x</td>';
                            } else {
                                echo '<td style="background-color: grey;"></td>';
                            }
                        } else {
                            echo '<td style="background-color: red;"></td>';
                        }
                    } else {
                        if ($pres == false) {
                            echo '<td style="background-color:gray; background-image: repeating-linear-gradient(315deg, transparent, transparent 3px, rgba(255,255,255,.5) 3px, rgba(255,255,255,.5) 6px);"></td>';
                        } elseif (!empty($pres)) {
                            if ($pres->is_present == true) {
                                echo '<td></td>';
                            } else {
                                echo '<td style="background-color: grey;"></td>';
                            }
                        } else echo '<td></td>';
                    }
                }
                echo '<td></td>';
                echo '</tr>';
            }
            ?>
        </table>
    <?php } ?>
    <br />
    <strong><?= Yii::t('app', 'Remarques générales') ?></strong>
    <hr />
    <hr />

</div>
