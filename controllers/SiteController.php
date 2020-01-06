<?php

namespace app\controllers;

use Yii;
use app\models\CoursDate;
use app\models\CoursDateSearch;
use app\models\Cours;
use app\models\CoursHasMoniteurs;
use app\models\Personnes;
use app\models\Parametres;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\data\ActiveDataProvider;
use yii\db\Exception;

use Spatie\CalendarLinks\Link;
use webvimark\modules\UserManagement\models\User;
use DateTime;

require_once('../vendor/le-o/simpleCalDAV/SimpleCalDAVClient.php');

class SiteController extends Controller
{
    
    public $freeAccessActions = ['index', 'login'];
    public $layout = 'main_full.php';
    
    public function behaviors()
    {
        return [
            
            'ghost-access'=> [
                'class' => 'webvimark\modules\UserManagement\components\GhostAccessControl',
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
            Yii::$app->response->cookies->remove('language');
            $model = new LoginForm();
            if ($model->load(Yii::$app->request->post()) && $model->login()) {
                return $this->goBack();
            }
            return $this->redirect(['/user-management/auth/login']);
        }
        
//        if (!empty(Yii::$app->request->post())) {
//            $post = Yii::$app->request->post();
//            
//            $addMoniteur = new CoursHasMoniteurs();
//            $addMoniteur->fk_cours_date = $post['coursDateId'];
//            $addMoniteur->fk_moniteur = $post['new_moniteur'];
//            $addMoniteur->is_responsable = 0;
//            try {
//                $transaction = \Yii::$app->db->beginTransaction();
//                
//                $existeMoniteurs = CoursHasMoniteurs::find()->where(['fk_moniteur'=>$addMoniteur->fk_moniteur])->andWhere(['fk_cours_date'=>$addMoniteur->fk_cours_date])->one();
//                if (!empty($existeMoniteurs)) {
//                    throw new Exception(Yii::t('app', 'Moniteur déjà inscrit pour ce cours.'));
//                }
//                if (!($flag = $addMoniteur->save(false))) {
//                    throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
//                }
//                // on doit supprimer le moniteur "Pas de moniteur" si il existe
//                CoursHasMoniteurs::find()->where(['IN', 'fk_moniteur', Yii::$app->params['sansEncadrant']])->andWhere(['fk_cours_date'=>$addMoniteur->fk_cours_date])->one()->delete();
//                $transaction->commit();
//                Yii::$app->session->setFlash('alerte', ['type'=>'success', 'info'=>Yii::t('app', 'Moniteur enregistré avec succès.')], false);
//            } catch (Exception $e) {
//                Yii::$app->session->setFlash('alerte', ['type'=>'danger', 'info'=>$e->getMessage()], false);
//                $transaction->rollBack();
//            }
//        }
        
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
//        $searchNoMoniteur = CoursDate::find()->distinct()->joinWith('coursHasMoniteurs', false)->where(['IN', 'cours_has_moniteurs.fk_moniteur', Yii::$app->params['sansEncadrant']])->andWhere(['>=', 'date', date('Y-m-d')])->orderBy('date ASC');
//        $dataProviderNM = new ActiveDataProvider([
//            'query' => $searchNoMoniteur,
//            'pagination' => [
//                'pageSize' => 10,
//            ],
//        ]);
        
        // 408 = Groupe sans moniteurs / 352 et 448 = Cours annulé
//        $toExclude = array_merge(Yii::$app->params['sansEncadrant'], [408, 352, 448]);
//        // liste des moniteurs actifs
//        $modelMoniteurs = Personnes::find()->where(['fk_type' => Yii::$app->params['typeEncadrantActif']])->andWhere(['NOT IN', 'personne_id', $toExclude])->orderBy('nom, prenom')->all();
//        foreach ($modelMoniteurs as $moniteur) {
//            $dataMoniteurs[$moniteur->personne_id] = $moniteur->NomPrenom;
//        }
        
        // set la valeur de la date début du calendrier
        $dataSalles = Parametres::findAll(['class_key' => 16]);
        foreach ($dataSalles as $salle) {
            if (Yii::$app->session->get('home-cal-debut-' . $salle->parametre_id) === null) Yii::$app->session->set('home-cal-debut-' . $salle->parametre_id, date('Y-m-d'));
            if (Yii::$app->session->get('home-cal-view-' . $salle->parametre_id) === null) Yii::$app->session->set('home-cal-view-' . $salle->parametre_id, 'agendaWeek');
        }
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'dataProviderNF' => $dataProviderNF,
//            'dataProviderNM' => $dataProviderNM,
//            'dataMoniteurs' => $dataMoniteurs,
            'dataSalles' => $dataSalles,
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
        return $this->redirect(['/user-management/auth/login']);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * 
     * @return type
     */
    public function actionCalendarsync()
    {   
        if (!isset(Yii::$app->params['syncCredentials'])) {
            exit('Il manque le paramétrage du compte');
        } elseif (!isset(Yii::$app->params['syncCredentials']['calendarID']) || empty(Yii::$app->params['syncCredentials']['calendarID'])) {
            // on affiche les valeurs possible pour le paramétrage
            $client = new \SimpleCalDAVClient();
            $client->connect('https://sync.infomaniak.com/calendars/' . Yii::$app->params['syncCredentials']['user'], Yii::$app->params['syncCredentials']['user'], Yii::$app->params['syncCredentials']['password']);
            $arrayOfCalendars = $client->findCalendars();
            
            echo "Valeur possible pour les identifiants de calendrier<pre>";
            print_r($arrayOfCalendars);
            echo "</pre>";
            exit;
        }
        
        $logTraitement = [];
        $model = new CoursDate();
        $nombreATraiter = $model->getDateToSync(true);
        
        if ($post = Yii::$app->request->post()) {
            $modelCoursDate = $model->getDateToSync(false, $post['nbATraiter']);
            
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $client = new \SimpleCalDAVClient();

                $client->connect('https://sync.infomaniak.com/calendars/' . Yii::$app->params['syncCredentials']['user'], Yii::$app->params['syncCredentials']['user'], Yii::$app->params['syncCredentials']['password']);
                $arrayOfCalendars = $client->findCalendars();

                $mySalle = null;
                foreach ($modelCoursDate as $coursDate) {
                    if ($coursDate->fkCours->fk_salle !== $mySalle) {
                        $mySalle = $coursDate->fkCours->fk_salle;
                        $client->setCalendar($arrayOfCalendars[Yii::$app->params['syncCredentials']['calendarID'][$mySalle]]);
                    }

                    $logTraitement[$coursDate->fkCours->fkSalle->nom][$coursDate->cours_date_id] = [
                        'calendarID' => $client->getUrl(),
                        'cours_date_id' => $coursDate->cours_date_id,
                        'fk_cours' => $coursDate->fk_cours,
                        'nom' => $coursDate->fkCours->fkNom->nom.' '.$coursDate->fkCours->session,
                        'date' => $coursDate->date,
                        'heure_debut' => $coursDate->heure_debut,
                        'heure_fin' => $coursDate->getHeureFin(),
                    ];
                    
                    $vevent = $coursDate->getVCalendarString();
                    if (CoursDate::CALENDAR_NEW == $coursDate->calendar_sync) {
                        // on ajoute un nouvel élément
                        $distantEvent = $client->create($vevent, true);
                        $logTraitement[$coursDate->fkCours->fkSalle->nom][$coursDate->cours_date_id]['statut'] = 'ajout';
                    } else {
                        // on modifie un élément existant
                        $filter = new \CalDAVFilter("VEVENT");
                        $filter->mustIncludeMatchSubstr("UID", "VH-cours-" . $coursDate->cours_date_id);
                        $events = $client->getCustomReport($filter->toXML());
                        // l'enregistrement n'est probablement jamais passé sur le serveur infomaniak
                        // on tente un nouvelle insertion !
                        if (empty($events)) {
                            $distantEvent = $client->create($vevent, true);
                            $logTraitement[$coursDate->fkCours->fkSalle->nom][$coursDate->cours_date_id]['statut'] = 'ajout';
                        } else {
                            $distantEvent = $events[0];
                            $client->change($distantEvent->getHref(), $vevent, $distantEvent->getEtag());
                            $logTraitement[$coursDate->fkCours->fkSalle->nom][$coursDate->cours_date_id]['statut']  = 'modification';
                        }
                    }

                    if (!empty($distantEvent) && null !== $distantEvent->getEtag()) {
                        $coursDate->updateSync = false;
                        $coursDate->calendar_sync = CoursDate::CALENDAR_SYNC;
                        $coursDate->save();
                    } else {
                        $logTraitement[$coursDate->fkCours->fkSalle->nom][$coursDate->cours_date_id]['statut']  .= ' !! pas sync !!';
                    }
                }
                $transaction->commit();
                Yii::$app->session->setFlash('syncOK');
            } catch (Exception $e) {
                $transaction->rollBack();
                echo $e->__toString();
                $alerte = $e->getMessage();
                echo "<pre>";
                print_r($alerte);
                echo "</pre>";
                exit;
            }
        }
        return $this->render('calendrierSync', [
            'logTraitement' => $logTraitement,
            'nombreATraiter' => $nombreATraiter,
        ]);
    }

    public function actionAbout()
    {

//        $from = DateTime::createFromFormat('Y-m-d H:i', '2018-11-01 09:00');
//        $to = DateTime::createFromFormat('Y-m-d H:i', '2018-11-01 18:00');
//
//        $link = Link::create('Test dans le mail', $from, $to)
//            ->description('Cours Koala')
//            ->address('Vertic-halle Saxon');
//
//        // Generate a link to create an event on Google calendar
//        $calLink = '<br /><a href="' . $link->google() . '" target="_blank"><img src="' . \yii\helpers\Url::base(true) . '/images/cal-bw-01.png" style="width:20px;" /> Ajouter au calendrier google</a>'
//            . '<br /><a href="' . $link->ics() . '" target="_blank"><img src="' . \yii\helpers\Url::base(true) . '/images/cal-bw-01.png" style="width:20px;" /> Ajouter un événement iCal/Outlook</a>';
//
//        $message = Yii::$app->mailer->compose()
//            ->setFrom(Yii::$app->params['adminEmails'][Yii::$app->language])
//            ->setTo(array('leo.decaillet@d-web.ch'))
//            ->setSubject('Hello')
//            ->setHtmlBody("<p>ici mon texte de test</p>" . $calLink);
//
//        $response = $message->send();

//        $coursDateID = explode(',', '7447,7197,6613,7439,7582,7601,7619,7565,7628,7650,7509,7574,7602,7629,7568,7688,7630,7651,7519,7631,7632,7510,7645,7646,7652,7520,7647,7648,7653,7511,7512,7649,7521,7633,7522,7634,7513,7635,7523,7636,7637,7524,7638,7639,7518,7640,7641,7642,7525,7643,7644,7526,7527,7463,7468,7570,7542,7563,6411,7654,6412,7444,7569,7627,6413,6414,6415,6416,6417,6418,6419,6420,6421,6422,6423,6424,6425,6426,6427,7202,7219,7372,7236,7253,7270,7287,7603,7304,7321,7338,7389,7406,7203,7220,7373,7237,7254,7271,7288,7604,7305,7322,7339,7390,7404,7204,7221,7374,7238,7255,7272,7289,7605,7306,7323,7340,7599,7405,7205,7222,7375,7239,7256,7273,7290,7606,7307,7324,7341,7395,7407,7206,7223,7376,7240,7257,7274,7291,7607,7308,7325,7342,7396,7685,7207,7224,7377,7241,7258,7275,7292,7608,7309,7326,7343,7397,7686,7408,7209,7226,7379,7243,7260,7277,7294,7609,7311,7328,7345,7392,7210,7227,7380,7244,7261,7278,7295,7610,7312,7329,7346,7393,7211,7228,7381,7245,7262,7279,7296,7611,7313,7330,7347,7394,7216,7587,7378,7575,7242,7259,7293,7581,7612,7319,7576,7336,7417,7212,7229,7382,7246,7263,7280,7297,7613,7314,7331,7348,7401,7213,7230,7383,7247,7264,7281,7298,7614,7315,7332,7349,7402,7214,7231,7384,7248,7265,7282,7299,7615,7316,7333,7350,7413,7215,7232,7385,7249,7266,7283,7300,7616,7317,7334,7351,7414,7233,7592,7386,7250,7267,7284,7301,7617,7318,7335,7352,7415,7217,7234,7387,7251,7268,7285,7302,7618,7310,7588,7353,7409,7410,7411,7412');

        /**
        $logTraitement = [];
        $model = new CoursDate();
        $nombreATraiter = $model->getDateToSyncManuel(true);

        if ($post = Yii::$app->request->post()) {
            $modelCoursDate = $model->getDateToSyncManuel(false, $post['nbATraiter']);

            try {
                echo '<br /><br /><br />';
                foreach ($modelCoursDate as $coursDate) {
                    $client = new \SimpleCalDAVClient();

                    $client->connect('https://sync.infomaniak.com/calendars/' . Yii::$app->params['syncCredentials']['user'], Yii::$app->params['syncCredentials']['user'], Yii::$app->params['syncCredentials']['password']);
                    $arrayOfCalendars = $client->findCalendars();
                    $client->setCalendar($arrayOfCalendars[Yii::$app->params['syncCredentials']['calendarID'][214]]);

                    $logTraitement[$coursDate->fkCours->fkSalle->nom][$coursDate->cours_date_id] = [
                        'calendarID' => $client->getUrl(),
                        'cours_date_id' => $coursDate->cours_date_id,
                        'fk_cours' => $coursDate->fk_cours,
                        'nom' => $coursDate->fkCours->fkNom->nom.' '.$coursDate->fkCours->session,
                        'date' => $coursDate->date,
                        'heure_debut' => $coursDate->heure_debut,
                        'heure_fin' => $coursDate->getHeureFin(),
                    ];

//                    $events = $client->GetEntryByUid("VH-cours-" . $coursDate->cours_date_id);

//                    $events = $this->doDelete($coursDate->cours_date_id, $client);
                    $filter = new \CalDAVFilter("VEVENT");
                    // on supprimer les existants pour refaire la saisie juste !
                    $filter->mustIncludeMatchSubstr("UID", "VH-cours-" . $coursDate->cours_date_id);
                    $events = $client->getCustomReport($filter->toXML());
                    if (!empty($events)) {
//                        echo '<br />pas vide '.$events[0]['href'] . '-' . $events[0]['etag'];
//                        $result = $client->delete($events[0]['href'], $events[0]['etag']);
                        echo '<br />pas vide '.$events[0]->getHref() . '-' . $events[0]->getEtag();
                        $result = $client->delete($events[0]->getHref(), $events[0]->getEtag());
                        $logTraitement[$coursDate->fkCours->fkSalle->nom][$coursDate->cours_date_id]['statut'] = 'suppression - ' . $result;
                    } else {
                        $logTraitement[$coursDate->fkCours->fkSalle->nom][$coursDate->cours_date_id]['statut'] = 'non existant';
                    }

                }
                Yii::$app->session->setFlash('syncOK');
            } catch (Exception $e) {
                echo $e->__toString();
                $alerte = $e->getMessage();
                echo "<pre>";
                print_r($alerte);
                echo "</pre>";
                exit;
            }
        }
        return $this->render('calendrierSync', [
            'logTraitement' => $logTraitement,
            'nombreATraiter' => $nombreATraiter,
        ]);
*/
        return $this->render('about');
    }

    private function doDelete($coursDateID, $client) {
        $filter = new \CalDAVFilter("VEVENT");

        $filter->mustIncludeMatchSubstr("UID", "VH-cours-" . $coursDateID);
        $i = 0;
        while ($i < 3) {
            $i++;
            $events = $client->getCustomReport($filter->toXML());
            if (empty($events)) {
                sleep($i);
            } else {
                break;
            }
        }
        if (empty($events)) {
            return null;
        }
        return $events;
    }

    public function actionTranslation()
    {
        $params = Parametres::find()->where(['fk_langue' => 253])->all();
        echo '<pre>return [';
        foreach ($params as $p) {
            echo '<br />';
            if ($p->nom === Yii::t('app/code', $p->nom, [], 'de-CH')) {
                echo "'" . $p->nom . "' => '',";
            } else {
                echo "'" . $p->nom . "' => ' " . Yii::t('app/code', $p->nom, [], 'de-CH') . "',";
            }
        }
        echo '
            ]</pre>DONE';
        exit;
    }
    
    public function actionSetcalendarview($for) {
        if (Yii::$app->request->isAjax) {
            $data = Yii::$app->request->post();
            Yii::$app->session->set('home-cal-view-' . $for, $data['view']);
            Yii::$app->session->set('home-cal-debut-' . $for, $data['start']);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
