<?php

namespace app\controllers;

use Yii;
use app\models\CoursDate;
use app\models\CoursDateSearch;
use app\models\Cours;
use app\models\CoursHasMoniteurs;
use app\models\Personnes;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\data\ActiveDataProvider;
use yii\db\Exception;

use Spatie\CalendarLinks\Link;
use DateTime;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        if (\Yii::$app->user->isGuest) {
            $model = new LoginForm();
            if ($model->load(Yii::$app->request->post()) && $model->login()) {
                return $this->goBack();
            }
            return $this->render('login', [
                'model' => $model,
            ]);
        }
        
        if (!empty(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            
            $addMoniteur = new CoursHasMoniteurs();
            $addMoniteur->fk_cours_date = $post['coursDateId'];
            $addMoniteur->fk_moniteur = $post['new_moniteur'];
            $addMoniteur->is_responsable = 0;
            try {
                $transaction = \Yii::$app->db->beginTransaction();
                
                $existeMoniteurs = CoursHasMoniteurs::find()->where(['fk_moniteur'=>$addMoniteur->fk_moniteur])->andWhere(['fk_cours_date'=>$addMoniteur->fk_cours_date])->one();
                if (!empty($existeMoniteurs)) {
                    throw new Exception(Yii::t('app', 'Moniteur déjà inscrit pour ce cours.'));
                }
                if (!($flag = $addMoniteur->save(false))) {
                    throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
                }
                // on doit supprimer le moniteur "Pas de moniteur" si il existe
                CoursHasMoniteurs::find()->where(['IN', 'fk_moniteur', Yii::$app->params['sansEncadrant']])->andWhere(['fk_cours_date'=>$addMoniteur->fk_cours_date])->one()->delete();
                $transaction->commit();
                Yii::$app->session->setFlash('alerte', ['type'=>'success', 'info'=>Yii::t('app', 'Moniteur enregistré avec succès.')], false);
            } catch (Exception $e) {
                Yii::$app->session->setFlash('alerte', ['type'=>'danger', 'info'=>$e->getMessage()], false);
                $transaction->rollBack();
            }
        }
        
        $searchModel = new CoursDateSearch();
        $searchModel->depuis = date('d.m.Y');
        $searchModel->homepage = true;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        // liste de tous les cours sans date dans le futur
        $searchNoFutur = new CoursDateSearch();
        $searchNoFutur->dateA = date('d.m.Y');
        $searchNoFutur->homepage = true;
        $dataProviderNF = $searchNoFutur->search([]);
        
        // liste de tous les cours sans moniteur
        $searchNoMoniteur = CoursDate::find()->distinct()->joinWith('coursHasMoniteurs', false)->where(['IN', 'cours_has_moniteurs.fk_moniteur', Yii::$app->params['sansEncadrant']])->andWhere(['>=', 'date', date('Y-m-d')])->orderBy('date ASC');
        $dataProviderNM = new ActiveDataProvider([
            'query' => $searchNoMoniteur,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
        
        // liste des moniteurs actifs
        $modelMoniteurs = Personnes::find()->where(['fk_type' => Yii::$app->params['typeEncadrantActif']])->orderBy('nom, prenom')->all();
        foreach ($modelMoniteurs as $moniteur) {
            $dataMoniteurs[$moniteur->fkStatut->nom][$moniteur->personne_id] = $moniteur->NomPrenom;
        }
        
        // set la valeur de la date début du calendrier
        if (Yii::$app->session->get('home-cal-debut') === null) Yii::$app->session->set('home-cal-debut', date('Y-m-d'));
        if (Yii::$app->session->get('home-cal-view') === null) Yii::$app->session->set('home-cal-view', 'agendaWeek');
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'dataProviderNF' => $dataProviderNF,
            'dataProviderNM' => $dataProviderNM,
            'dataMoniteurs' => $dataMoniteurs,
        ]);
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    public function actionAbout()
    {

        $from = DateTime::createFromFormat('Y-m-d H:i', '2018-11-01 09:00');
        $to = DateTime::createFromFormat('Y-m-d H:i', '2018-11-01 18:00');

        $link = Link::create('Test dans le mail', $from, $to)
            ->description('Cours Koala')
            ->address('Vertic-halle Saxon');

        // Generate a link to create an event on Google calendar
        $calLink = '<br /><a href="'.$link->google().'" target="_blank"><img src="'.\yii\helpers\Url::base(true).'/images/cal-bw-01.png" style="width:20px;" /> Ajouter au calendrier google</a>'
                . '<br /><a href="'.$link->ics().'" target="_blank"><img src="'.\yii\helpers\Url::base(true).'/images/cal-bw-01.png" style="width:20px;" /> Ajouter un événement iCal/Outlook</a>';
        
        $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmail'])
                    ->setTo(array('leo.decaillet@d-web.ch'))
                    ->setSubject('Hello')
                    ->setHtmlBody("<p>ici mon texte de test</p>".$calLink);

    $response = $message->send();

        
        
        return $this->render('about');
    }
    
    /**
     * Permet l'envoi d'un email à tous les $clients
     * et stock l'email dans le dossier "Sent Items" sur le serveur infomaniak
     *
     * @param $mail
     * @param $adresses
     */
    public static function actionEmail($mail, $adresses, $public = false)
    {
        if (YII_ENV === 'dev') {
            $emails[] = Yii::$app->params['testEmail'];
        } else {
            foreach ($adresses as $a) {
            	if ($a !== 'none' && $a !== 'interloc.') {
                    $emails[] = $a;
                }
            }
        }
            
        $content = $mail['valeur'];
        
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
            $datesCoursInscrit = [];
            $statutTraite = false;
            $statutInscription = 'n/a';
            foreach ($allDatesCours as $date) {
                if (isset($mail['personne_id']) && $date->getForPresence($mail['personne_id'])) {
                    $datesCoursInscrit[] = $date->date;
                }
                $datesCours[] = $date->date;
                // on traite le statut du participant
                if ($statutTraite == false && isset($myPersonne)) {
                    $inscriptions = $myPersonne->getClientsHasOneCoursDate($date->cours_date_id);
                    if (!empty($inscriptions)) {
                        $statutInscription = $inscriptions->fkStatut->nom;
                        $statutTraite = true;
                    }
                }
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
                ['#nom-du-cours#', '#jour-du-cours#', '#heure-debut#', '#heure-fin#', 
                    '#nom-de-session#', '#nom-de-saison#', '#prix-du-cours#', '#date-prochain#',
                    '#toutes-les-dates#', '#dates-inscrit#', '#statut-inscription#'], 
                [$myCours->fkNom->nom, $jour_cours, $heure_debut, $heure_fin, 
                    $myCours->session, $saison, ($myCours->fk_type == Yii::$app->params['coursPonctuel'] ? $myCoursDate->prix : $myCours->prix), $date,
                    implode(', ', $datesCours), implode(', ', $datesCoursInscrit), $statutInscription], 
                $content
            );
        }
        
        if (isset($emails)) {
            if ($public) {
                $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmail'])
                    ->setTo($emails)
                    ->setSubject($mail['nom'])
                    ->setHtmlBody($content);
            } else {
                $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmail'])
                    ->setTo(Yii::$app->params['adminEmail'])
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
    
    public function actionSetcalendarview() {
        if (Yii::$app->request->isAjax) {
            $data = Yii::$app->request->post();
            Yii::$app->session->set('home-cal-view', $data['view']);
            Yii::$app->session->set('home-cal-debut', $data['start']);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
