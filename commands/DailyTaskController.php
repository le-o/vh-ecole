<?php

namespace app\commands;

use yii\console\Controller;
use app\models\CoursDate;

/**
 * Test controller
 */
class DailyTaskController extends Controller {

    private static $conversionTable = [
        1225 => 1223,
        1395 => 1223,
        1224 => 1222,
        1394 => 1222,

    ];

    public function actionIndex() {
        echo "cron service runnning";
    }

    public function actionConvertBirthdayToLight() {
        echo "cronjob BEGIN<br />";
        // trouver les anniversaires qui sont concernés
        // !UNIQUEMENT MONTHEY
        // Chaque anniversaire avec moniteurs (fk_nom = 240) qui ne sont pas réservé seront transformé
        // en cours light (fk_nom = 246). Si cela n'est pas possible, créé un nouveau cours light à la place.

        // On calcule la date de début : anniversaires light 72h, autre anniversaire après 14 jours
        $dateTo = date('Y-m-d\T00:00:00', strtotime(date('Y-m-d') . ' + 13 days'));
        $query = CoursDate::find()
            ->where(['between', 'date', date('Y-m-d'), $dateTo])
            ->andWhere(['=', 'cours.fk_nom', 240])
            ->orderBy('fk_lieu, date ASC');
        $query->joinWith(['fkCours']);
        $modelsCoursDate = $query->all();

        $transaction = \Yii::$app->db->beginTransaction();
        $i = 0;
        try {
            foreach ($modelsCoursDate as $model) {
                if (empty($model->clientsHasCoursDate)) {
                    // on ajoute 15 minutes à l'heure de début du cours
                    $beginTime = strtotime("+15 minutes", strtotime($model->heure_debut));

                    $model->fk_cours = self::$conversionTable[$model->fk_cours];
                    $model->heure_debut = date('H:i:s', $beginTime);
                    $model->duree = 1.75;
                    $model->prix = 70;
                    $model->save();
                    $i++;
                }
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            echo $e->__toString();
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
            exit;
        }
        $s1 = (1 < count($modelsCoursDate) ? "s" : "");
        $s2 = (1 < $i ? "s" : "");
        echo count($modelsCoursDate) . " enregistrement" . $s1 . " trouvé" . $s1 . " - " . $i . " anniversaire" . $s2 . " converti" . $s2;
        echo "<br />cronjob DONE";
    }

}