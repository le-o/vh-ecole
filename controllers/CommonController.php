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
use app\models\ClientsHasCours;
use app\models\ClientsHasCoursDate;

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
    public function generateMoniteurEmail($model, $nomMoniteurs, $cud, $allDate = []) {
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

            $link = Link::create($calNom, $from, $to, false, $model->cours_date_id)
                ->description($model->remarque)
                ->address($model->fkLieu->nom);
            
            // Generate a link to create an event on Google calendar
            $calLink = '<br /><a href="'.$link->google().'" target="_blank"><img src="'.\yii\helpers\Url::base(true).'/images/cal-bw-01.png" style="width:20px;" /> Ajouter au calendrier google</a>'
                    . '<br /><a href="'.$link->ics().'" target="_blank"><img src="'.\yii\helpers\Url::base(true).'/images/cal-bw-01.png" style="width:20px;" /> Ajouter un événement iCal/Outlook</a>';
        }
        
        if (!empty($allDate)) {
            foreach($allDate as $date) {
                $datesCours[] = date('d.m.Y', strtotime($date->date)).' '.substr($model->heure_debut, 0, 5);
            }
            $dateheure = 'Date : '.implode(', ', $datesCours).'<br />';
        } else {
            $dateheure = 'Date : '.date('d.m.Y', strtotime($model->date)).'<br />';
            $dateheure .= 'Heure : '.substr($model->heure_debut, 0, 5).'<br />';
        }
        
        // on génère l'email à envoyer
        return ['nom' => $model->fkCours->fkNom->nom.' - '.$cudObjet, 
            'valeur' => $cudContent.'<br /><br />
                '.$calNom.'<br />
                '.$dateheure.'
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
    
    /**
     * Permet l'envoi d'un email à tous les clients
     * et stock l'email dans le dossier "Sent Items" sur le serveur infomaniak
     *
     * @param array $mail Le contenu du message
     * @param array $adresses Liste de emails
     * @param bool $public True si on se trouve sur une page public
     */
    public function actionEmail($mail, $adresses, $public = false)
    {
        $originEmails = [];
        foreach ($adresses as $a) {
            if ($a !== 'none' && $a !== 'interloc.') {
                $originEmails[] = $a;
            }
        }
        if (YII_ENV === 'dev') {
            $emails[] = Yii::$app->params['testEmail'];
            $beginMail = '<i><b>Email non envoyé !</b><br />L\'email original était destiné à : ' . implode(', ', $originEmails) . '</i><br /><br />';
        } else {
            $emails = $originEmails;
            $beginMail = '';
        }
            
        $content = $beginMail . $mail['valeur'];
        
        if (isset($mail['personne_id']) && !empty($mail['personne_id'])) {
            $myPersonne = Personnes::findOne($mail['personne_id']);
            
            $content = str_replace(
                ['#prenom#', '#nom#', ' #tous-les-participants#'], 
                [$myPersonne->prenom, $myPersonne->nom, ''], 
                $content);
        }
        
        if (isset($mail['listePersonneId']) && !empty($mail['listePersonneId'])) {
            $ids = explode('|', $mail['listePersonneId']);
            $participants = Personnes::find()->where(['IN', 'personne_id', $ids])->all();
            
            foreach ($participants as $p) {
                $noms[] = $p->prenom.' '.$p->nom;
            }
            
            $content = str_replace(
                ['#tous-les-participants#', ' #prenom#', ' #nom#'],
                [implode(', ', $noms), '', ''],
                $content);
        }
        
        if (isset($mail['keyForMail']) && !empty($mail['keyForMail'])) {
            $indexs = explode('|', $mail['keyForMail']);
            $myCours = Cours::findOne($indexs[0]);
            // on a un cours vide, on va essayer de le trouver via les dates
            if (empty($myCours)) {
                $myCoursDate = CoursDate::findOne($indexs[0]);
                $myCours = Cours::findOne($myCoursDate->fk_cours);
            }
            $saison = (isset($myCours->fkSaison)) ? $myCours->fkSaison->nom : '';
            $dateCours = $myCours->nextCoursDate;
            $allDatesCours = $myCours->coursDates;
            $datesCours = [];
            $datesCoursLieux = [];
            $datesCoursInscrit = [];
            $datesCoursInscritLieux = [];
            foreach ($allDatesCours as $date) {
                if (isset($mail['personne_id']) && $date->getForPresence($mail['personne_id'])) {
                    $datesCoursInscrit[] = $date->date;
                    $datesCoursInscritLieux[] = $date->date . ' - ' . $date->fkLieu->nom;
                }
                $datesCours[] = $date->date;
                $datesCoursLieux[] = $date->date . ' - ' . $date->fkLieu->nom;
            }
            // on traite le statut du participant
            $statutInscription = 'n/a';
            if (isset($myPersonne)) {
                $myClientsHasCours = ClientsHasCours::findOne(['fk_personne' => $myPersonne->personne_id, 'fk_cours' => $myCours->cours_id]);
                $statutInscription = $myClientsHasCours->fkStatut->nom;
            }
            if (isset($myCoursDate)) {
                $heure_debut = $myCoursDate->heure_debut;
                $heure_fin = $myCoursDate->heureFin;
                $date = $myCoursDate->date;
                $jour_cours = Yii::$app->params['joursSemaine'][date('w', strtotime($date))];
            } else {
                $heure_debut = isset($dateCours->heure_debut) ? $dateCours->heure_debut : $allDatesCours[0]->heure_debut;
                $heure_fin = isset($dateCours->heureFin) ? $dateCours->heureFin : $allDatesCours[0]->heureFin;
                $date = isset($dateCours->date) ? $dateCours->date : '<b>jj.mm.aaaa</b>';
                $jour_cours = $myCours->FkJoursNoms;
            }
            
            $content = str_replace(
                ['#nom-du-cours#', '#jour-du-cours#', '#heure-debut#', '#heure-fin#', '#salle-cours#',
                    '#nom-de-session#', '#nom-de-saison#', '#prix-du-cours#', '#date-prochain#',
                    '#toutes-les-dates#', '#toutes-les-dates-avec-lieux#', '#dates-inscrit#', '#dates-inscrit-avec-lieux#', 
                    '#statut-inscription#'], 
                [$myCours->fkNom->nom, $jour_cours, $heure_debut, $heure_fin, $myCours->fkSalle->nom,
                    $myCours->session, $saison, ($myCours->fk_type == Yii::$app->params['coursPonctuel'] ? $myCoursDate->prix : $myCours->prix), $date,
                    implode(', ', $datesCours), implode(', ', $datesCoursLieux), implode(', ', $datesCoursInscrit), implode(', ', $datesCoursInscritLieux), 
                    $statutInscription], 
                $content
            );
        }
        
        if (isset($emails) && !empty($emails)) {
            if ($public || count($originEmails) == 1) {
                $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmail'])
                    ->setTo($emails)
                    ->setSubject($mail['nom'])
                    ->setHtmlBody($content);
            } else {
                $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmail'])
                    ->setTo(Yii::$app->params['noreplyEmail'])
                    ->setBcc($emails)
                    ->setSubject($mail['nom'])
                    ->setHtmlBody($content);
            }

            // we send the message !
            $message->send();
            if (YII_ENV != 'dev') {
                //  (this creates the full MIME message required for imap_append()
                $msg = $message->toString();

                //  After this you can call imap_append like this:
                // connect to IMAP (port 143)
                $stream = imap_open("{mail.infomaniak.ch:143/imap}", Yii::$app->params['adminEmail'], "V-HSaxon2012");
                // Saves message to Sent folder and marks it as read
                imap_append($stream,"{mail.infomaniak.ch:143/imap}Envoyes appli",$msg."\r\n","\\Seen");
                // Close connection to the server when you're done
                imap_close($stream);
            }
        }
    }
    
    protected function addClientToCours($modelDate, $personneID, $coursID) {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $modelClientsHasCours = new ClientsHasCours();
            $modelClientsHasCours->fk_personne = $personneID;
            $modelClientsHasCours->fk_cours = $coursID;
            $modelClientsHasCours->fk_statut = Yii::$app->params['partInscrit'];

            $existe = ClientsHasCours::find()
                ->where([ 'fk_personne' => $personneID, 'fk_cours' => $coursID])
                ->exists();
            if (!$existe) {
                if (!$modelClientsHasCours->save()) {
                    throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du lien client-cours.'));
                }
            }
            foreach ($modelDate as $date) {
                $modelClientsHasCoursDate = new ClientsHasCoursDate();
                $modelClientsHasCoursDate->fk_cours_date = $date->cours_date_id;
                $modelClientsHasCoursDate->fk_personne = $personneID;
                $modelClientsHasCoursDate->is_present = true;
                if (!$modelClientsHasCoursDate->save(false)) {
                    throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du lien client-date de cours.'));
                }
                
                // si cours ponctuel, on n'inscrit à une seul date
                if ($date->fkCours->fk_type == Yii::$app->params['coursPonctuel']) {
                    break;
                }
            }
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'La personne a bien été enregistrée comme participante !');
            $transaction->commit();
        } catch (Exception $e) {
            $alerte['class'] = 'danger';
            $alerte['message'] = Yii::t('app', 'Inscription impossible - erreur inattendue, veuillez contactez le support.') . '<br />' . $e->getMessage();
            $transaction->rollBack();
        }
        return $alerte;
    }
}