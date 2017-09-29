<?php

namespace app\controllers;

use Yii;
use app\models\CoursDate;
use app\models\CoursDateSearch;
use app\models\Cours;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\data\SqlDataProvider;

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
        $searchModel = new CoursDateSearch();
        $searchModel->depuis = date('d.m.Y');
        $searchModel->homepage = true;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        // set la valeur de la date début du calendrier
        if (Yii::$app->session->get('home-cal-debut') === null) Yii::$app->session->set('home-cal-debut', date('Y-m-d'));
        if (Yii::$app->session->get('home-cal-view') === null) Yii::$app->session->set('home-cal-view', 'agendaWeek');
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
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
            	if ($a !== 'none' && $a !== 'interloc.')
	                $emails[] = $a;
            }
        }
        
        if (isset($emails)) {
            if ($public) {
                $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmail'])
                    ->setTo($emails)
                    ->setSubject($mail['nom'])
                    ->setHtmlBody($mail['valeur']);
            } else {
                $message = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['adminEmail'])
                    ->setTo(Yii::$app->params['adminEmail'])
                    ->setBcc($emails)
                    ->setSubject($mail['nom'])
                    ->setHtmlBody($mail['valeur']);
            }

            //  (this creates the full MIME message required for imap_append()
            $msg = $message->toString();
            if (YII_ENV === 'dev') {
                echo "<pre>";
                print_r($msg);
                echo "</pre>";
            } else {
                // we send the message !
                $message->send();

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
