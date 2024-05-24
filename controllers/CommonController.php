<?php

namespace app\controllers;

use app\models\SentEmail;
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
    protected function saveMoniteur($cours_date_id, $moniteurs, $setBareme, $delete = false) {
        $emails = [];
        $nomMoniteurs = [];
        
        if ($delete) {
            CoursHasMoniteurs::deleteAll('fk_cours_date = ' . $cours_date_id);
        }
        foreach ($moniteurs as $moniteur_id) {
            $addMoniteur = new CoursHasMoniteurs();
            $addMoniteur->fk_cours_date = $cours_date_id;
            $addMoniteur->fk_moniteur = $moniteur_id;
            $addMoniteur->is_responsable = 0;

            // on gère le barème selon la saisie effectuée, si il n'est pas défini, on prend celui du moniteur
//            $addMoniteur->fk_bareme = (null != $setBareme) ? $setBareme : Personnes::findOne($moniteur_id)->fk_formation;
            $addMoniteur->fk_bareme = $setBareme;

            if (!$addMoniteur->save()) {
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
            $cudContent = '<p>Un cours auquel tu es prévu comme moniteur.rice a été créé. Prière de prendre bonne note de la nouvelle date.<br />Merci et à bientôt.';
        } elseif ('update' == $cud) {
            $cudObjet = 'modifications';
            $cudContent = '<p>Un cours auquel tu es prévu comme moniteur.rice a été modifié. Prière de prendre bonne note des changements effectués.<br />Merci et à bientôt.';
        } elseif ('delete' == $cud) {
            $cudObjet = 'suppression';
            $cudContent = '<p>Un cours auquel tu étais prévu comme moniteur.rice a été supprimé. Prière de prendre bonne note de l\'annulation.<br />Merci et à bientôt.';
            $withLink = false;
        } elseif ('birthday' == $cud) {
            $cudObjet = 'anniversaire';
            $cudContent = '<p>Un client est inscrit au cours pour lequel tu es prévu comme moniteur.rice. Prière de prendre bonne note de la date.<br />Merci et à bientôt.';
        } else {
            throw new Exception('Erreur typage email');
        }

        $saison = (isset($model->fkCours->fkSaison->nom)) ? ' - ' . Yii::t('app', $model->fkCours->fkSaison->nom) : '';
        $calNom = $model->fkCours->fkNom->nom . ' - ' . Yii::t('app', $model->fkCours->session) . $saison;
        $calLink = '';
        
        // infos for addtocal link
        if ($withLink) {
            $format = 'Y-m-d H:i';
            $from = DateTime::createFromFormat($format, date($format, strtotime($model->date.' '.$model->heure_debut)));
            $to = DateTime::createFromFormat($format, date($format, strtotime($model->date.' '.$model->heureFin)));

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
            $dateheure = 'Date : '.implode(', ', $datesCours) . '<br />';
        } else {
            $dateheure = 'Date : '.date('d.m.Y', strtotime($model->date)) . '<br />';
            $dateheure .= 'Heure : '.substr($model->heure_debut, 0, 5) . '<br />';
        }

        $baremePrestation = (!is_null($model->coursHasMoniteurs[0]->fk_bareme) ? $model->coursHasMoniteurs[0]->fkBareme->nom : 'barème par défaut');
        
        // on génère l'email à envoyer
        return ['nom' => Yii::t('app', $model->fkCours->fkNom->nom).' - '.$cudObjet,
            'valeur' => $cudContent.'<br /><br />
                '.$calNom.'<br />
                '.$dateheure.'
                Infos : '.$model->remarque.'<br />
                Moniteur(s).trice(s) : '.  implode(', ', $nomMoniteurs).'<br />
                Barème appliqué pour la prestation : ' . $baremePrestation . '</p>'.
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

        if (!Yii::$app->user->isGuest && strpos($content, '#prenom-utilisateur#')) {
            $userInfo = (isset(Yii::$app->user->identity->fkpersonne)) ? Yii::$app->user->identity->fkpersonne : '';
            $userPrenom = (!empty($userInfo)) ? $userInfo->prenom : '';
            $userNom = (!empty($userInfo)) ? $userInfo->nom : Yii::$app->user->username;
            $content = str_replace(
                ['#prenom-utilisateur#', '#nom-utilisateur#'],
                [$userPrenom, $userNom],
                $content);
        }
        
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
            if ('d' == $indexs[0]) {
                $myCoursDate = CoursDate::findOne($indexs[1]);
                $myCours = $myCoursDate->fkCours;
            } else {
                $myCours = Cours::findOne($indexs[0]);
                // on a un cours vide, on va essayer de le trouver via les dates
                if (empty($myCours)) {
                    $myCoursDate = CoursDate::findOne($indexs[0]);
                    $myCours = Cours::findOne($myCoursDate->fk_cours);
                }
            }

            $saison = (isset($myCours->fkSaison)) ? Yii::t('app', $myCours->fkSaison->nom) : '';
            $dateCours = $myCours->nextCoursDate;
            $allDatesCours = $myCours->coursDates;
            $datesCours = [];
            $datesCoursLieux = [];
            $datesCoursInscrit = [];
            $datesCoursInscritLieux = [];
            foreach ($allDatesCours as $date) {
                if (isset($mail['personne_id']) && $date->getForPresence($mail['personne_id'])) {
                    $datesCoursInscrit[] = $date->date;
                    $datesCoursInscritLieux[] = $date->date . ' - ' . Yii::t('app', $date->fkLieu->nom);
                }
                $datesCours[] = $date->date;
                $datesCoursLieux[] = $date->date . ' - ' . Yii::t('app', $date->fkLieu->nom);
            }
            // on traite le statut du participant
            $statutInscription = 'n/a';
            if (isset($myPersonne)) {
                $myClientsHasCours = ClientsHasCours::findOne(['fk_personne' => $myPersonne->personne_id, 'fk_cours' => $myCours->cours_id]);
                $statutInscription = Yii::t('app', $myClientsHasCours->fkStatut->nom);
            }
            if (isset($myCoursDate)) {
                $heure_debut = $myCoursDate->heure_debut;
                $heure_fin = $myCoursDate->heureFin;
                $date = $myCoursDate->date;
                $jour_cours = Yii::t('app', ucfirst(Yii::$app->params['joursSemaine'][date('w', strtotime($date))]));
            } else {
                $heure_debut = isset($dateCours->heure_debut) ? $dateCours->heure_debut : $allDatesCours[0]->heure_debut;
                $heure_fin = isset($dateCours->heureFin) ? $dateCours->heureFin : $allDatesCours[0]->heureFin;
                $date = isset($dateCours->date) ? $dateCours->date : '<b>jj.mm.aaaa</b>';
                $jour_cours = $myCours->FkJoursNoms;
            }

            $montantAcompte30 = round($myCours->prix * 0.3);
            
            $content = str_replace(
                ['#nom-du-cours#', '#jour-du-cours#', '#heure-debut#', '#heure-fin#', '#salle-cours#',
                    '#nom-de-session#', '#nom-de-saison#', '#prix-du-cours#', '#date-prochain#',
                    '#toutes-les-dates#', '#toutes-les-dates-avec-lieux#', '#dates-inscrit#', '#dates-inscrit-avec-lieux#', 
                    '#statut-inscription#', '#cours-acompte-30#', '#infostarifs#', '#description-cours#', '#extrait-cours#'],
                [$myCours->fkNom->nom, $jour_cours, $heure_debut, $heure_fin, $myCours->fkSalle->nom,
                    Yii::t('app', $myCours->session), $saison, ($myCours->fk_type == Yii::$app->params['coursPonctuel'] && isset($myCoursDate) ? $myCoursDate->prix : $myCours->prix), $date,
                    implode(', ', $datesCours), implode(', ', $datesCoursLieux), implode(', ', $datesCoursInscrit), implode(', ', $datesCoursInscritLieux), 
                    $statutInscription, $montantAcompte30, $myCours->offre_speciale, $myCours->description, $myCours->extrait],
                $content
            );
        }
        
        if (isset($emails) && !empty($emails)) {
            if ($public || count($originEmails) == 1) {
                $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmails'][Yii::$app->language])
                    ->setTo($emails)
                    ->setSubject($mail['nom'])
                    ->setHtmlBody($content);
                $bcc = '';
            } else {
                $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmails'][Yii::$app->language])
                    ->setTo(Yii::$app->params['noreplyEmail'])
                    ->setBcc($emails)
                    ->setSubject($mail['nom'])
                    ->setHtmlBody($content);
                $bcc = implode(', ', $emails);
            }

            // we send the message !
            if ($message->send()) {
                $modelSentEmail = new SentEmail();
                $modelSentEmail->from = Yii::$app->params['adminEmails'][Yii::$app->language];
                $modelSentEmail->to = implode(', ', $emails);
                $modelSentEmail->bcc = $bcc;
                $modelSentEmail->sent_date = date('Y-m-d H:i:s');
                $modelSentEmail->subject = $mail['nom'];
                $modelSentEmail->body = $content;
                $modelSentEmail->email_params = json_encode($mail);
                $modelSentEmail->save(false);
                return true;
            } else {
                return false;
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
            if (!$existe && !$modelClientsHasCours->save()) {
                throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du lien client-cours.'));
            }
            foreach ($modelDate as $date) {
                $existe = ClientsHasCoursDate::find()
                    ->where([ 'fk_personne' => $personneID, 'fk_cours_date' => $date->cours_date_id])
                    ->exists();
                if (!$existe) {
                    $modelClientsHasCoursDate = new ClientsHasCoursDate();
                    $modelClientsHasCoursDate->fk_cours_date = $date->cours_date_id;
                    $modelClientsHasCoursDate->fk_personne = $personneID;
                    $modelClientsHasCoursDate->is_present = true;
                    if (!$modelClientsHasCoursDate->save(false)) {
                        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du lien client-date de cours.'));
                    }
                }

                // on sauve la remarque modifiée !
                $date->save();
                
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