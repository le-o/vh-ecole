<?php

namespace app\controllers;

use app\models\ClientsHasCoursDate;
use app\models\CoursHasMoniteurs;
use Yii;
use app\models\Cours;
use app\models\CoursSearch;
use app\models\CoursDate;
use app\models\Personnes;
use app\models\Parametres;
use app\models\ClientsHasCours;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\db\Exception;
use yii\data\ActiveDataProvider;
use kartik\mpdf\Pdf;

/**
 * CoursController implements the CRUD actions for Cours model.
 */
class CoursController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['view'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if ($action->id == 'presence' && Yii::$app->user->identity->id == 1001) return true;
                            return (Yii::$app->user->identity->id < 1000) ? true : false;
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Cours models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CoursSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Cours model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $msg = '')
    {
	    $model = $this->findModel($id);
        $alerte = [];
        $session = Yii::$app->session;

        if ($msg === 'supp') {
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'La personne a bien été supprimée du cours !');
        } elseif ($msg === 'suppdate') {
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'La planification a bien été supprimée !');
        } elseif ($msg !== '') {
            $alerte['class'] = 'danger';
            $alerte['message'] = $msg;
        }

        $sendEmail = false;
	    if (!empty(Yii::$app->request->post()) || $session->getFlash('newParticipant') != '') {
            // soit on force toutes les dates, soit on prend que le futur (mode normal !)
            if ($session->getFlash('newParticipant') != '') {
                $modelDate = CoursDate::find()
                    ->where(['=', 'fk_cours', $model->cours_id])
                    ->all();
                $new = ['new_participant' => $session->getFlash('newParticipant')];
            } else {
                $modelDate = CoursDate::find()
                    ->where(['=', 'fk_cours', $model->cours_id])
                    ->andWhere(['>=', 'date', date('Y-m-d')])
                    ->all();
                $new = Yii::$app->request->post();
            }
            
            if (!empty($new['new_participant'])) {
                // soit on ajoute un participant
                if (empty($modelDate)) {
                    $alerte['class'] = 'warning';
                    $alerte['message'] = Yii::t('app', 'Inscription impossible - aucune date dans le futur');
                    $alerte['message'] .= '<a class="btn btn-link" href="'.Url::to(['/cours/view', 'id' => $id]).'">'.Yii::t('app', 'Forcer l\'inscription à toutes les dates ?').'</a>';
                    $session->setFlash('newParticipant', $new['new_participant']);
                } else {
                    $participant = Personnes::findOne(['personne_id' => $new['new_participant']]);
                    foreach ($modelDate as $date) {
                        $modelClientsHasCoursDate = new ClientsHasCoursDate();
                        $modelClientsHasCoursDate->fk_cours_date = $date->cours_date_id;
                        $modelClientsHasCoursDate->fk_personne = $new['new_participant'];
                        $modelClientsHasCoursDate->is_present = true;
                        $modelClientsHasCoursDate->fk_statut = (in_array($participant->fk_statut, Yii::$app->params['groupePersStatutNonActif'])) ? Yii::$app->params['persStatutInscrit'] : $participant->fk_statut;
                        $modelClientsHasCoursDate->save(false);
                    }
                    $alerte['class'] = 'success';
                    $alerte['message'] = Yii::t('app', 'La personne a bien été enregistrée comme participante !');
                    
                    // on passe la personne au statut inscrit si non actif
                    if (in_array($participant->fk_statut, Yii::$app->params['groupePersStatutNonActif'])) {
                        $participant->fk_statut = Yii::$app->params['persStatutInscrit'];
                        $participant->save();
                        $alerte['message'] .= '<br />'.Yii::t('app', 'Son statut a été modifié en inscrit.');
                    }
                }
            } elseif (!empty($new['Parametres'])) {
                // soit on envoi un email !
                // on le fait après avoir cherché la liste des participants
                $sendEmail = true;
                $alerte['class'] = 'info';
                $alerte['message'] = Yii::t('app', 'Email envoyé à tous les participants');
            } elseif (!empty($new['Cours'])) {
                $alerte = '';
                if ($model->load(Yii::$app->request->post())) {
                    if (!$model->save()) {
                        $alerte = Yii::t('app', 'Problème lors de la sauvegarde du cours.');
                    }
                }
            } else {
                // dans ce cas on ajoute un participant sans en avoir sélectionné
                $alerte['class'] = 'warning';
                $alerte['message'] = Yii::t('app', 'Vous devez sélectionner un participant pour pouvoir l\'ajouter.');
            }
	    }

        // liste des dates de cours
        $listeCoursDate = [];
        $coursDate = CoursDate::find()->where(['fk_cours' => $model->cours_id])->orderBy('date');
        foreach ($coursDate->all() as $date) {
            $listeCoursDate[] = $date->cours_date_id;
        }
	    $participants = Personnes::find()->distinct()->joinWith('clientsHasCoursDate', false)->where(['IN', 'clients_has_cours_date.fk_cours_date', $listeCoursDate])->orderBy('clients_has_cours_date.fk_statut ASC');
	    $listParticipants = $participants->all();
	    $excludePart = [];
        $listeEmails = [];
	    foreach ($listParticipants as $participant) {
	        $excludePart[] = $participant->personne_id;
            
            if (strpos($participant->email, '@') !== false) {
                $listeEmails[$participant->email] = $participant->email;
            }
            
            foreach ($participant->personneHasInterlocuteurs as $pi) {
                $listeEmails[$pi->fkInterlocuteur->email] = $pi->fkInterlocuteur->email;
            }
	    }

        if ($sendEmail == true) {
            SiteController::actionEmail($new['Parametres'], $listeEmails);
        }
	    
        $dataClients = Personnes::getClientsNotInCours($excludePart);

		$coursDateProvider = new ActiveDataProvider([
		    'query' => $coursDate,
		    'pagination' => [
		        'pageSize' => 20,
		    ],
		    'sort' =>false
		]);

		$participantDataProvider = new ActiveDataProvider([
		    'query' => $participants,
		    'pagination' => [
		        'pageSize' => 20,
		    ],
		]);
        foreach($participantDataProvider->models as $part) {
            foreach ($coursDate->all() as $date) {
                $pres = $date->getForPresence($part->personne_id);
                if (isset($pres->fk_statut)) {
                    $part->statutPart = $pres->fkStatut->nom;
                    break;
                }
            }
        }

        $parametre = new Parametres();
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();
        
        // gestion affichage bouton
        $displayActions = (Yii::$app->user->identity->id < 1000) ? '' : ' hidden';
        // gestion affichage bouton recursive
        $createR = ($participantDataProvider->totalCount == 0) ? '' : ' hidden';
        
        // pour l'affichage des paramètres en mode édition
        $modelParams = new Parametres;
	    
        return $this->render('view', [
	        'alerte' => $alerte,
            'model' => $model,
            'modelParams' => $modelParams,
            'coursDateProvider' => $coursDateProvider,
            'dataClients' => $dataClients,
            'participantDataProvider' => $participantDataProvider,
            'parametre' => $parametre,
            'emails' => $emails,
            'displayActions' => $displayActions,
            'createR' => $createR,
            'listeEmails' => $listeEmails,
        ]);
    }

    /**
     * Creates a new Cours model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Cours();
		$alerte = '';
        if ($model->load(Yii::$app->request->post())) {
            $model->fk_type = $model->fkNom->info_special;
	        if (!$model->save()) {
		        $alerte = Yii::t('app', 'Problème lors de la sauvegarde du cours.');
		    } else {
	            return $this->redirect(['view', 'id' => $model->cours_id]);
	        }
        }
        $modelParams = new Parametres;
        return $this->render('create', [
	        'alerte' => $alerte,
            'model' => $model,
            'modelParams' => $modelParams,
        ]);
    }

    /**
     * Updates an existing Cours model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
		$alerte = '';
        if ($model->load(Yii::$app->request->post())) {
            if (!$model->save()) {
		        $alerte = Yii::t('app', 'Problème lors de la sauvegarde du cours.');
		    } else {
	            return $this->redirect(['view', 'id' => $model->cours_id]);
	        }
        }
        $modelParams = new Parametres;
        return $this->render('update', [
	        'alerte' => $alerte,
            'model' => $model,
            'modelParams' => $modelParams,
        ]);
    }

    /**
     * Deletes an existing ClientsHasCours model.
     * If deletion is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionParticipantDelete($personne_id, $cours_ou_date_id, $from)
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if ($from == 'cours-date') {
                ClientsHasCoursDate::deleteAll(['fk_personne' => $personne_id, 'fk_cours_date' => $cours_ou_date_id]);
            } else {
                if ($from == 'coursfutur') {
                    $coursDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $cours_ou_date_id])
                        ->andWhere(['>=', 'date', date('Y-m-d')])
                        ->all();
                    $from = 'cours';
                    $coursDateAll = CoursDate::find()
                        ->where(['=', 'fk_cours', $cours_ou_date_id])
                        ->all();
                } elseif ($from == 'cours-datefutur') {
                    $coursDateBase = CoursDate::find()
                        ->where(['=', 'cours_date_id', $cours_ou_date_id])
                        ->one();
                    $coursDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $coursDateBase->fk_cours])
                        ->andWhere(['>=', 'date', date('Y-m-d', strtotime($coursDateBase->date))])
                        ->all();
                    $from = 'cours-date';
                    $coursDateAll = CoursDate::find()
                        ->where(['=', 'fk_cours', $coursDateBase->fk_cours])
                        ->all();
                } else {
                    $coursDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $cours_ou_date_id])
                        ->all();
                    $coursDateAll = [];
                }
                // on modifie le statut de toutes les dates du client
                foreach($coursDateAll as $c) {
                    ClientsHasCoursDate::updateAll(['fk_statut' => Yii::$app->params['persStatutDesinscritFutur']], ['fk_personne' => $personne_id, 'fk_cours_date' => $c->cours_date_id]);
                }
                foreach ($coursDate as $date) {
                    ClientsHasCoursDate::deleteAll(['fk_personne' => $personne_id, 'fk_cours_date' => $date->cours_date_id]);
                }
            }
            
            // on modifie le statut 
            $transaction->commit();
            $msg = 'supp';
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $transaction->rollBack();
        }

        return $this->redirect(['/'.$from.'/view', 'id' => $cours_ou_date_id, 'msg' => $msg]);
    }

    /**
     * Deletes an existing Cours model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            ClientsHasCours::deleteAll(['fk_cours' => $id]);
            $dates = CoursDate::findAll(['fk_cours' => $id]);
            foreach ($dates as $date) {
                CoursHasMoniteurs::deleteAll(['fk_cours_date' => $date->cours_date_id]);
            }
            CoursDate::deleteAll(['fk_cours' => $id]);

            $this->findModel($id)->delete();
            $transaction->commit();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $transaction->rollBack();
            return $this->redirect(['view', 'id' => $id, 'msg' => $msg]);
        }

        return $this->redirect(['index']);
    }

    /**
     * Displays a single Cours model.
     * @param integer $id
     * @return mixed
     */
    public function actionPresence($id, $msg = '')
    {
        $model = $this->findModel($id);

        // chargement du paramétrage
        $param = \app\models\Parametres::findOne(Yii::$app->params['colMaxPrint']);
        $nbmax = strip_tags($param->valeur);
        
        // liste des dates de cours
        $listeCoursDate = [];
        $coursDate = CoursDate::find()->where(['fk_cours' => $model->cours_id])->orderBy('date');
        $i = 0;
        $todec = [];
        foreach ($coursDate->all() as $date) {
            $listeCoursDate[] = $date->cours_date_id;
            $todec[] = $date;
            $i++;
            if ($i == $nbmax && $i < $coursDate->count()) {
                $i = 0;
                $decoupage[] = $todec;
                $todec = [];
            }
        }
        while ($i < $nbmax) {
            $i++;
            $todec[] = new CoursDate();
        }
        $decoupage[] = $todec;
        $participants = Personnes::find()->distinct()->joinWith('clientsHasCoursDate')->where(['IN', 'clients_has_cours_date.fk_cours_date', $listeCoursDate])->orderBy('clients_has_cours_date.fk_statut ASC');

        // get your HTML raw content without any layouts or scripts
        $content = $this->renderPartial('_presence', [
            'model' => $model,
            'participants' => $participants->all(),
            'decoupage' => $decoupage,
        ]);
//        return $content;

        // setup kartik\mpdf\Pdf component
        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_LANDSCAPE,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => '
                table { width:100%; border-collapse:collapse; }
                table, th, td { border:1px solid slategrey; }
                tr.entete td, td.entete { background-color:lightgrey; font-weight:bold; text-align:center; }
                td.date { width:35px; }
                td.num { width:25px; }
                .kv-heading-1{font-size:18px}
            ',
            // set mPDF properties on the fly
//            'options' => ['title' => Yii::t('app', 'Liste des présences')],
            // call mPDF methods on the fly
            'methods' => [
//                'SetHeader'=>[Yii::t('app', 'Liste des présences')],
                'SetFooter'=>['{PAGENO}'],
            ]
        ]);

        // return the pdf output as per the destination setting
        return $pdf->render();
    }

    /**
     * Finds the Cours model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Cours the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Cours::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
