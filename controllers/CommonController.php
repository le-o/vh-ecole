<?php

namespace app\controllers;

use Yii;
use app\models\CoursDate;
use app\models\CoursDateSearch;
use app\models\Cours;
use app\models\Personnes;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\data\SqlDataProvider;
use app\models\CoursHasMoniteurs;

use \Spatie\CalendarLinks\Link;
use DateTime;

class CommonController extends Controller
{
    
    /**
     * 
     * @param int $cours_date_id
     * @param array $moniteurs
     * @param boolean $delete
     * @return array(array(emails), array(nom))
     * @throws Exception
     */
    protected function saveMoniteur($cours_date_id, $moniteurs, $delete = false) {
        $emails = [];
        $nomMoniteurs = [];
        
        if ($delete == true) {
            CoursHasMoniteurs::deleteAll('fk_cours_date = ' . $cours_date_id);
        }
        foreach ($moniteurs as $moniteur_id) {
            $addMoniteur = new CoursHasMoniteurs();
            $addMoniteur->fk_cours_date = $cours_date_id;
            $addMoniteur->fk_moniteur = $moniteur_id;
            $addMoniteur->is_responsable = 0;
            if (!($flag = $addMoniteur->save(false))) {
                throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
            }
            $emails[] = $addMoniteur->fkMoniteur->email;
            $nomMoniteurs[] = $addMoniteur->fkMoniteur->prenom.' '.$addMoniteur->fkMoniteur->nom;
        }
        
        return ['emails'=>$emails, 'noms'=>$nomMoniteurs];
    }
    
    /**
     * 
     * @param object $model
     * @param string $nomMoniteurs
     * @param string $cud
     * @return array(nom, valeur)
     */
    public function generateMoniteurEmail($model, $nomMoniteurs, $cud) {
        $withLink = true;
        if ('create' == $cud) {
            $cudObjet = 'nouveauté';
            $cudContent = '<p>Un cours auquel tu es prévu comme moniteur a été créé. Prière de prendre bonne note de la nouvelle date.<br />Merci et à bientôt.';
        } elseif ('update' == $cud) {
            $cudObjet = 'modifications';
            $cudContent = '<p>Un cours auquel tu es prévu comme moniteur a été modifié. Prière de prendre bonne note des changements effectués.<br />Merci et à bientôt.';
        } elseif ('delete' == $cud) {
            $cudObjet = 'suppression';
            $cudContent = '<p>Un cours auquel tu étais prévu comme moniteur a été supprimé. Prière de prendre bonne note de l\'annulation.<br />Merci et à bientôt.';
            $withLink = false;
        } else {
            throw new Exception('Erreur typage email');
        }
        
        $calNom = $model->fkCours->fkNom->nom.' - '.$model->fkCours->session.' - '.$model->fkCours->fkSaison->nom;
        $calLink = '';
        
        // infos for addtocal link
        if ($withLink) {
            $from = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d H:i', strtotime($model->date.' '.$model->heure_debut)));
            $to = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d H:i', strtotime($model->date.' '.$model->heureFin)));

            $link = Link::create($calNom, $from, $to)
                ->description($model->remarque)
                ->address($model->lieu);

            // Generate a link to create an event on Google calendar
            $calLink = '<br /><a href="'.$link->google().'" target="_blank"><img src="'.\yii\helpers\Url::base(true).'/images/cal-bw-01.png" style="width:20px;" /> Ajouter au calendrier google</a>'
                    . '<br /><a href="'.$link->ics().'" target="_blank"><img src="'.\yii\helpers\Url::base(true).'/images/cal-bw-01.png" style="width:20px;" /> Ajouter un événement iCal/Outlook</a>';
        }
        
        // on génère l'email à envoyer
        return ['nom' => $model->fkCours->fkNom->nom.' - '.$cudObjet, 
            'valeur' => $cudContent.'<br /><br />
                '.$calNom.'<br />
                Date : '.date('d.m.Y', strtotime($model->date)).'<br />
                Heure : '.substr($model->heure_debut, 0, 5).'<br />
                Infos : '.$model->remarque.'<br />
                Moniteur(s) : '.  implode(', ', $nomMoniteurs).'</p>'.
                $calLink
        ];
    }
    
    /**
     * 
     * @param type $array1
     * @param type $array2
     * @return type
     */
    public function checkDiffMulti($array1, $array2) {
        $result = array();
        foreach($array1 as $key => $val) {
             if(isset($array2[$key])){
               if(is_array($val) && $array2[$key]) {
                   $findDiff = $this->checkDiffMulti($val, $array2[$key]);
                   if (!empty($findDiff)) {
                        $result[$key] = $findDiff;
                   }
               }
           } else {
               $result[$key] = $val;
           }
        }

        return $result;
    }
}