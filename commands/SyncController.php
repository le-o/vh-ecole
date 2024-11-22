<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use app\models\CoursDate;
use app\controllers\CommonController;

require_once(dirname(__FILE__) . '/../vendor/le-o/simpleCalDAV/SimpleCalDAVClient.php');

/**
 * This command sync the app calendar to infomaniak.
 *
 *
 * @author Léonard Décaillet <leo.decaillet@d-web.ch>
 * @since 2.0
 */
class SyncController extends Controller
{
    
    public $nombreATraiter = 0;
    
    /**
     * 
     * @return array
     */
    public function options($actionID)
    {
        return ['nombreATraiter'];
    }
    
    /**
     * 
     * @return array
     */
    public function optionAliases()
    {
        return ['n' => 'nombreATraiter'];
    }
    
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex()
    {
        $model = new CoursDate();
        $client = new \SimpleCalDAVClient();
        
        $client->connect('https://sync.infomaniak.com/calendars/' . \Yii::$app->params['syncCredentials']['user'], \Yii::$app->params['syncCredentials']['user'], \Yii::$app->params['syncCredentials']['password']);
        $arrayOfCalendars = $client->findCalendars();
        $client->setCalendar($arrayOfCalendars[\Yii::$app->params['syncCredentials']['calendarID']]);
        
        $modelCoursDate = $model->getDateToSync(false, $this->nombreATraiter);
        \Yii::trace('Nombre de date à traiter: ' . count($modelCoursDate), 'calendarSync');
            
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            foreach ($modelCoursDate as $coursDate) {
                $vevent = $coursDate->getVCalendarString();
                if (CoursDate::CALENDAR_NEW == $coursDate->calendar_sync) {
                    // on ajoute un nouvel élément
                    $newEvent = $client->create($vevent, true);
                    $statut = 'ajout';
                } else {
                    // on modifie un élément existant
                    $filter = new \CalDAVFilter("VEVENT");
                    $filter->mustIncludeMatchSubstr("UID", "VH-cours-" . $coursDate->cours_date_id);
                    $events = $client->getCustomReport($filter->toXML());
                    // l'enregistrement n'est probablement jamais passé sur le serveur infomaniak
                    // on tente un nouvelle insertion !
                    if (!isset($events[0])) {
                        $newEvent = $client->create($vevent, true);
                        $statut = 'ajout';
                    } else {
                        $client->change($events[0]->getHref(),$vevent, $events[0]->getEtag());
                        $statut  = 'modification';
                    }
                }

                $coursDate->updateSync = false;
                $coursDate->calendar_sync = CoursDate::CALENDAR_SYNC;
                $coursDate->save();
                
                $logTraitement[] = 'LOG: [' . $coursDate->cours_date_id . '-' . $coursDate->fk_cours . '][' . $statut . '] cours ' . $coursDate->fkCours->fkNom->nom.' '.$coursDate->fkCours->session . ' du ' . $coursDate->date . ' de ' . $coursDate->heure_debut . ' à ' . $coursDate->getHeureFin();
            }

            $transaction->commit();
            \Yii::trace("\n" . implode("\n", $logTraitement), 'calendarSync');
            \Yii::trace('Fin du traitement.', 'calendarSync');
            return 0;
        } catch (Exception $e) {
            $transaction->rollBack();
            echo $e->__toString();
            $alerte = $e->getMessage();
            \Yii::trace('Erreur lors du traitement: ' . $alerte, 'calendarSync');
            return 1;
        }
        
        \Yii::trace('Erreur lors du traitement: Rien traité ?', 'calendarSync');
        return 1;
    }
}
