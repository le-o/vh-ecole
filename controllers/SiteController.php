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
        
        // 408 = Groupe sans moniteurs / 352 et 448 = Cours annulé
        $toExclude = array_merge(Yii::$app->params['sansEncadrant'], [408, 352, 448]);
        // liste des moniteurs actifs
        $modelMoniteurs = Personnes::find()->where(['fk_type' => Yii::$app->params['typeEncadrantActif']])->andWhere(['NOT IN', 'personne_id', $toExclude])->orderBy('nom, prenom')->all();
        foreach ($modelMoniteurs as $moniteur) {
            $dataMoniteurs[$moniteur->personne_id] = $moniteur->NomPrenom;
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
