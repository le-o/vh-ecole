<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Cours */

$this->title = Yii::t('app', 'Liste des présences cours').' '.$model->fkNom->nom;
?>

<div class="cours-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php foreach ($decoupage as $coursDate) { ?>
        <table>
            <tr class="entete">
                <td colspan="4" class="titre">
                    <?= $model->fkNom->nom.' Session '.$model->session.'.'.$model->annee.' / '.$model->prix.'.-' ?>
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
                echo '<td class="num"></td>';
                foreach ($coursDate as $pos => $date) {
                    if (date('Y-m-d', strtotime($date->date)) <= date('Y-m-d')) {
                        $pres = $date->getForPresence($part->personne_id);
                        if (!empty($pres)) {
                            if ($pres->is_present == true) {
                                echo '<td style="text-align: center;">x</td>';
                            } else {
                                echo '<td style="background-color: grey;"></td>';
                            }
                        } else {
                            echo '<td></td>';
                        }
                    } else {
                        echo '<td></td>';
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
