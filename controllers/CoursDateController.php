<?php

namespace app\controllers;

use Yii;
use app\models\CoursDate;
use app\models\CoursDateSearch;
use app\models\ClientsHasCoursDate;
use app\models\Cours;
use app\models\CoursHasMoniteurs;
use app\models\Personnes;
use app\models\Parametres;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\db\Exception;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;

/**
 * CoursDateController implements the CRUD actions for CoursDate model.
 */
class CoursDateController extends Controller
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
                        'actions' => ['view', 'liste', 'jsoncalendar'],
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
     * Lists all CoursDate models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CoursDateSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CoursDate model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $msg = '')
    {
        $model = $this->findModel($id);
        $alerte = [];

        if ($msg === 'supp') {
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'La personne a bien été supprimée du cours !');
        }

        $sendEmail = false;
        if ($post = Yii::$app->request->post()) {
            if (!empty($post['new_participant'])) {
                // soit on ajoute un participant
                $participant = Personnes::findOne(['personne_id' => $post['new_participant']]);
                $modelClientsHasCoursDate = new ClientsHasCoursDate();
                $modelClientsHasCoursDate->fk_cours_date = $model->cours_date_id;
                $modelClientsHasCoursDate->fk_personne = $post['new_participant'];
                $modelClientsHasCoursDate->is_present = true;
                $modelClientsHasCoursDate->fk_statut = Yii::$app->params['partInscrit'];
                $modelClientsHasCoursDate->save(false);
                $alerte['class'] = 'success';
                $alerte['message'] = Yii::t('app', 'La personne a bien été enregistrée comme participante !');
                
                // on passe la personne au statut inscrit
                if (in_array($participant->fk_statut, Yii::$app->params['groupePersStatutNonActif'])) {
                    $participant->fk_statut = Yii::$app->params['persStatutInscrit'];
                    $participant->save();
                    $alerte['message'] .= '<br />'.Yii::t('app', 'Son statut a été modifié en inscrit.');
                }
            } elseif (!empty($post['CoursDate'])) {
                $model->load(Yii::$app->request->post());
                $moniteurs = (isset($post['list_moniteurs'])) ? $post['list_moniteurs'] : [];

                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save()) {
                        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du cours.'));
                    }

                    CoursHasMoniteurs::deleteAll('fk_cours_date = ' . $model->cours_date_id);
                    foreach ($moniteurs as $moniteur_id) {
                        $addMoniteur = new CoursHasMoniteurs();
                        $addMoniteur->fk_cours_date = $model->cours_date_id;
                        $addMoniteur->fk_moniteur = $moniteur_id;
                        $addMoniteur->is_responsable = 0;
                        if (!($flag = $addMoniteur->save(false))) {
                            throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
                        }
                    }
                    
                    $myCours = Cours::findOne($model->fk_cours);
                    // on réactive le cours si il ne l'est pas déjà et si on saisi une date dans le futur
                    if ($myCours->is_actif == false && date('Y-m-d', strtotime($model->date)) >= date('Y-m-d')) {
                        $myCours->is_actif = true;
                        $myCours->save();
                    }

                    $transaction->commit();
                    if ($myCours->fk_type == Yii::$app->params['coursPonctuel']) {
                        return $this->redirect(['cours-date/view', 'id' => $model->cours_date_id]);
                    } else {
                        return $this->redirect(['cours/view', 'id' => $model->fk_cours]);
                    }
                } catch (Exception $e) {
                    $alerte = $e->getMessage();
                    $transaction->rollBack();
                }
            } else {
                // soit on envoi un email !
                // on le fait après avoir cherché la liste des participants
                $sendEmail = true;
                $alerte['class'] = 'info';
                $alerte['message'] = Yii::t('app', 'Email envoyé à tous les participants');
            }
        }

        foreach ($model->coursHasMoniteurs as $myMoniteur) {
            $moniteurs[] = $myMoniteur->fkMoniteur->nomPrenom;
        }
        $listeMoniteurs = implode(', ', $moniteurs);

        // Gestion des participants
        $participants = Personnes::find()->joinWith('clientsHasCoursDate', false)->where(['IN', 'clients_has_cours_date.fk_cours_date', $model->cours_date_id])->orderBy('clients_has_cours_date.fk_statut ASC');
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
            SiteController::actionEmail($post['Parametres'], $listeEmails);
        }
        
        $dataClients = Personnes::getClientsNotInCours($excludePart);
        
        $arrayParticipants = $participants->all();
        for ($i=0; $i<$model->nb_client_non_inscrit; $i++) {
            $fake = new Personnes();
            $fake->nom = 'Participant';
            $fake->prenom = 'non inscrit';
            
            $arrayParticipants[] = $fake;
        }
        
        $myCours = Cours::findOne($model->fk_cours);
        $dataCours = [$model->fk_cours => $myCours->fkNom->nom];
        $myMoniteurs = CoursHasMoniteurs::find()->where(['fk_cours_date' => $model->cours_date_id])->all();
        foreach ($myMoniteurs as $moniteur) {
	        $selectedMoniteurs[] = $moniteur->fk_moniteur;//.' '.$moniteur->fkMoniteur->prenom;
        }
        $modelMoniteurs = Personnes::find()->where(['fk_type' => Yii::$app->params['typeEncadrant']])->orderBy('nom, prenom')->all();
        foreach ($modelMoniteurs as $moniteur) {
            $dataMoniteurs[$moniteur->fkStatut->nom][$moniteur->personne_id] = $moniteur->NomPrenom;
        }
        
        $participantDataProvider = new ArrayDataProvider([
            'allModels' => $arrayParticipants,
            'key' => 'personne_id',
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        foreach($participantDataProvider->allModels as $part) {
            $pres = $model->getForPresence($part->personne_id);
            if (isset($pres->fk_statut)) {
                $part->statutPart = $pres->fkStatut->nom;
            }
        }
        
        $parametre = new Parametres();
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();

        return $this->render('view', [
            'alerte' => $alerte,
            'model' => $model,
            'listeMoniteurs' => (isset($listeMoniteurs)) ? $listeMoniteurs : '',
            
            'dataCours' => $dataCours,
            'dataMoniteurs' => $dataMoniteurs,
            'selectedMoniteurs' => (isset($selectedMoniteurs)) ? $selectedMoniteurs : [],

            'isInscriptionOk' => ($participantDataProvider->totalCount < $model->fkCours->participant_max) ? true : false,
            'dataClients' => $dataClients,
            'participantDataProvider' => $participantDataProvider,
            'parametre' => $parametre,
            'emails' => $emails,
            'listeEmails' => $listeEmails,
        ]);
    }
    
    public function actionListe($msg = '')
    {
        $searchModel = new CoursDateSearch();
        $searchModel->depuis = date('d.m.Y');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $alerte = [];
        
        if ($msg === 'suppdate') {
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'La planification a bien été supprimée !');
        } elseif ($msg !== '') {
            $alerte['class'] = 'danger';
            $alerte['message'] = $msg;
        }
        
        return $this->render('liste', [
	        'alerte' => $alerte,
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Creates a new CoursDate model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($cours_id = '')
    {
        $model = new CoursDate();
        $alerte = [];
        $model->fk_cours = $cours_id;
        $myCours = Cours::findOne($cours_id);

        if ($model->load(Yii::$app->request->post())) {
	        
	        $post = Yii::$app->request->post();
	        $moniteurs = (isset($post['list_moniteurs'])) ? $post['list_moniteurs'] : [];
	        
	        $transaction = \Yii::$app->db->beginTransaction();
	        try {
		        if (!$model->save()) {
			        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du cours.'));
			    }
		        
		        foreach ($moniteurs as $moniteur_id) {
			        $addMoniteur = new CoursHasMoniteurs();
			        $addMoniteur->fk_cours_date = $model->cours_date_id;
			        $addMoniteur->fk_moniteur = $moniteur_id;
			        $addMoniteur->is_responsable = 0;
			        if (! ($flag = $addMoniteur->save(false))) {
				        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
                    }
		        }
                
                // on inscrit les participants déjà existant pour les autres planifications de ce cours
                // seulement pour les cours planifié
                if ($myCours->fk_type == Yii::$app->params['coursPlanifie']) {
                    $coursDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $model->fk_cours])
                        ->andWhere(['!=', 'cours_date_id', $model->cours_date_id])
                        ->orderBy('cours_date_id DESC')
                        ->one();
                    if (!empty($coursDate)) {
                        $participants = ClientsHasCoursDate::find()
                            ->where(['=', 'fk_cours_date', $coursDate->cours_date_id])
                            ->all();
                        foreach ($participants as $part) {
                            $cc = new ClientsHasCoursDate;
                            $cc->fk_personne = $part->fk_personne;
                            $cc->fk_cours_date = $model->cours_date_id;
                            $cc->is_present = true;
                            $cc->save();
                        }
                    }
                }
                
                // on réactive le cours si il ne l'est pas déjà et si on saisi une date dans le futur
                if ($myCours->is_actif == false && date('Y-m-d', strtotime($model->date)) >= date('Y-m-d')) {
                    $myCours->is_actif = true;
                    $myCours->save();
                }
		        
		        $transaction->commit();
		        if ($myCours->fk_type == Yii::$app->params['coursPonctuel']) {
                    return $this->redirect(['cours-date/view', 'id' => $model->cours_date_id]);
                } else {
                    return $this->redirect(['cours/view', 'id' => $model->fk_cours]);
                }
		    } catch (Exception $e) {
			    $alerte['class'] = 'danger';
                $alerte['message'] = $e->getMessage();
                $transaction->rollBack();
            }
        }
        
        $dataCours = [$cours_id => $myCours->fkNom->nom];
        $modelMoniteurs = Personnes::find()->where(['fk_type' => 2])->orderBy('nom, prenom')->all();
        foreach ($modelMoniteurs as $moniteur) {
            $dataMoniteurs[$moniteur->fkStatut->nom][$moniteur->personne_id] = $moniteur->NomPrenom;
        }
        
        if ($model->duree == '') $model->duree = $myCours->duree;
        if ($model->prix == '') $model->prix = $myCours->prix;
        
        return $this->render('create', [
	        'alerte' => $alerte,
            'model' => $model,
            'dataCours' => $dataCours,
            'dataMoniteurs' => $dataMoniteurs,
            'selectedMoniteurs' => [],
        ]);
    }
    
    /**
     * Displays a single CoursDate model.
     * @param integer $id
     * @return mixed
     */
    public function actionRecursive($cours_id = '')
    {
        $model = new CoursDate();
        $alerte = [];
        $model->fk_cours = $cours_id;
        
        $date_range = '';

        if ($model->load(Yii::$app->request->post())) {
	        
	        $post = Yii::$app->request->post();
            $date_range = $post['date_range_1'];
            $date_exclude = $post['date_exclude_1'];
            
	        $moniteurs = (isset($post['list_moniteurs'])) ? $post['list_moniteurs'] : [];
            $dateRange = explode(Yii::t('app', ' au '), $post['date_range_1']);
            $date_debut = date('Y-m-d', strtotime($dateRange[0]));
            $date_fin = date('Y-m-d', strtotime($dateRange[1]));
            $date_exclude = explode(Yii::t('app', ' + '), $post['date_exclude_1']);
            foreach ($date_exclude as $key => $date) {
                $date_exclude[$key] = date('Y-m-d', strtotime($date));
            }
            $transaction = \Yii::$app->db->beginTransaction();
	        try {
                while ($date_debut <= $date_fin) {
                    if (in_array(date('N', strtotime($date_debut)), $post['jour_semaine']) && !in_array($date_debut, $date_exclude)) {
                        $modelDate = new CoursDate;
                        $modelDate->attributes = $model->attributes;
                        $modelDate->date = $date_debut;
                        if (!$modelDate->save()) {
                            throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du cours. '.$date_debut));
                        }

                        foreach ($moniteurs as $moniteur_id) {
                            $addMoniteur = new CoursHasMoniteurs();
                            $addMoniteur->fk_cours_date = $modelDate->cours_date_id;
                            $addMoniteur->fk_moniteur = $moniteur_id;
                            $addMoniteur->is_responsable = 0;
                            if (! ($flag = $addMoniteur->save(false))) {
                                throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
                            }
                        }
                    }
                    $date_debut = date('Y-m-d', strtotime('+ 1 day', strtotime($date_debut)));
                }
                $transaction->commit();
		        return $this->redirect(['cours/view', 'id' => $model->fk_cours]);
            } catch (Exception $e) {
                $alerte['class'] = 'danger';
                $alerte['message'] = $e->getMessage();
                $transaction->rollBack();
            }
        }
        
        $myCours = Cours::findOne($cours_id);
        $dataCours = [$cours_id => $myCours->fkNom->nom];
        $modelMoniteurs = Personnes::find()->where(['fk_type' => 2])->orderBy('nom, prenom')->all();
        foreach ($modelMoniteurs as $moniteur) {
            $dataMoniteurs[$moniteur->fkStatut->nom][$moniteur->personne_id] = $moniteur->NomPrenom;
        }
        
        if ($model->duree == '') $model->duree = $myCours->duree;
        if ($model->prix == '') $model->prix = $myCours->prix;
        
        return $this->render('recursive', [
	        'alerte' => $alerte,
            'model' => $model,
            'dataCours' => $dataCours,
            'dataMoniteurs' => $dataMoniteurs,
            'selectedMoniteurs' => [],
            'date_range' => $date_range,
        ]);
    }

    /**
     * Updates an existing CoursDate model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $alerte = [];

        if ($post = Yii::$app->request->post()) {
            if (!empty($post['new_participant'])) {
                $modelClientsHasCoursDate = new ClientsHasCoursDate();
                $modelClientsHasCoursDate->fk_cours_date = $model->cours_date_id;
                $modelClientsHasCoursDate->fk_personne = $post['new_participant'];
                $modelClientsHasCoursDate->is_present = true;
                $modelClientsHasCoursDate->save(false);
                $alerte['class'] = 'success';
                $alerte['message'] = Yii::t('app', 'La personne a bien été enregistrée comme participante !');
            } else {
                $model->load(Yii::$app->request->post());
                $moniteurs = (isset($post['list_moniteurs'])) ? $post['list_moniteurs'] : [];

                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save()) {
                        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du cours.'));
                    }

                    CoursHasMoniteurs::deleteAll('fk_cours_date = ' . $model->cours_date_id);
                    foreach ($moniteurs as $moniteur_id) {
                        $addMoniteur = new CoursHasMoniteurs();
                        $addMoniteur->fk_cours_date = $model->cours_date_id;
                        $addMoniteur->fk_moniteur = $moniteur_id;
                        $addMoniteur->is_responsable = 0;
                        if (!($flag = $addMoniteur->save(false))) {
                            throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
                        }
                    }
                    
                    $myCours = Cours::findOne($model->fk_cours);
                    // on réactive le cours si il ne l'est pas déjà et si on saisi une date dans le futur
                    if ($myCours->is_actif == false && date('Y-m-d', strtotime($model->date)) >= date('Y-m-d')) {
                        $myCours->is_actif = true;
                        $myCours->save();
                    }

                    $transaction->commit();
                    if ($myCours->fk_type == Yii::$app->params['coursPonctuel']) {
                        return $this->redirect(['cours-date/view', 'id' => $model->cours_date_id]);
                    } else {
                        return $this->redirect(['cours/view', 'id' => $model->fk_cours]);
                    }
                } catch (Exception $e) {
                    $alerte = $e->getMessage();
                    $transaction->rollBack();
                }
            }
        }
        $myCours = Cours::findOne($model->fk_cours);
        $dataCours = [$model->fk_cours => $myCours->fkNom->nom];
        $myMoniteurs = CoursHasMoniteurs::find()->where(['fk_cours_date' => $model->cours_date_id])->all();
        foreach ($myMoniteurs as $moniteur) {
	        $selectedMoniteurs[] = $moniteur->fk_moniteur;//.' '.$moniteur->fkMoniteur->prenom;
        }
        $modelMoniteurs = Personnes::find()->where(['fk_type' => Yii::$app->params['typeEncadrant']])->orderBy('nom, prenom')->all();
        foreach ($modelMoniteurs as $moniteur) {
            $dataMoniteurs[$moniteur->fkStatut->nom][$moniteur->personne_id] = $moniteur->NomPrenom;
        }

        // Gestion des participants
        $participants = Personnes::find()->joinWith('clientsHasCoursDate', false)->where(['IN', 'clients_has_cours_date.fk_cours_date', $model->cours_date_id]);
        $listParticipants = $participants->all();
        $excludePart = [];
        foreach ($listParticipants as $participant) {
            $excludePart[] = $participant->personne_id;
        }
        $dataClients = ArrayHelper::map(Personnes::find()->where(['not in', 'personne_id', $excludePart])->andWhere(['not in', 'fk_type', 2])->orderBy('nom, prenom')->all(), 'personne_id', 'NomPrenom');
        $participantDataProvider = new ActiveDataProvider([
            'query' => $participants,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        $parametre = new Parametres();
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();

        return $this->render('update', [
	        'alerte' => $alerte,
            'model' => $model,
            'dataCours' => $dataCours,
            'dataMoniteurs' => $dataMoniteurs,
            'selectedMoniteurs' => (isset($selectedMoniteurs)) ? $selectedMoniteurs : [],

            'isInscriptionOk' => ($participantDataProvider->totalCount < $model->fkCours->participant_max) ? true : false,
            'dataClients' => $dataClients,
            'participantDataProvider' => $participantDataProvider,
            'parametre' => $parametre,
            'emails' => $emails,
        ]);
    }

    /**
     * Deletes an existing CoursDate model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id, $from = null)
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            CoursHasMoniteurs::deleteAll(['fk_cours_date' => $id]);
            ClientsHasCoursDate::deleteAll(['fk_cours_date' => $id]);
            $model = $this->findModel($id);
            $fk_cours = $model->fk_cours;
            $model->delete();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            exit($e->getMessage());
        }

        if ($from == '/cours-date/liste') {
            return $this->redirect([$from, 'msg' => 'suppdate']);
        } else {
            return $this->redirect(['/cours/view', 'id' => $fk_cours, 'msg' => 'suppdate']);
        }
    }

    /**
     * Finds the CoursDate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CoursDate the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CoursDate::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Retourne un json avec les données à afficher dans le calendrier
     * @param null $start
     * @param null $end
     * @param null $_
     * @return array
     */
    public function actionJsoncalendar($start=NULL,$end=NULL,$_=NULL){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $times = CoursDate::find()
            ->where(['>=', 'date', $start])
            ->andWhere(['<=', 'date', $end])
            ->all();
        
        Yii::$app->session->set('home-cal-debut', $start);

        $events = [];
        foreach ($times AS $time){
            //Testing
            $Event = new \yii2fullcalendar\models\Event();
            $Event->id = $time->cours_date_id;
            $Event->url = Url::to(['/cours-date/view', 'id' => $time->cours_date_id]);
            
            if ($time->fkCours->fk_type == Yii::$app->params['coursPonctuel'] && $time->fkCours->fk_nom != Yii::$app->params['nomCoursDecouverte']) {
                $Event->title = (isset($time->clientsHasCoursDate[0]) ? $time->clientsHasCoursDate[0]->fkPersonne->societe.' '.$time->clientsHasCoursDate[0]->fkPersonne->nomPrenom : Yii::t('app', 'Client non défini'));
            } else {
                $Event->title = $time->fkCours->fkNom->nom.' '.$time->fkCours->session.'.'.$time->fkCours->annee;
            }

            $arrayMoniteurs = [];
            $moniteurs = $time->coursHasMoniteurs;
            foreach ($moniteurs as $m) {
                $arrayMoniteurs[] = $m->fkMoniteur->nomPrenom;
            }
            $Event->description = implode(', ', $arrayMoniteurs);
            $Event->start = date('Y-m-d\TH:i:s\Z',strtotime($time->date.' '.$time->heure_debut));
            $Event->end = date('Y-m-d\TH:i:s\Z',strtotime($time->date.' '.$time->HeureFin));
            $Event->color = $time->fkCours->fkNom->info_couleur;
            $events[] = $Event;
        }

        return $events;
    }
    
    /**
     * Gestion des présences pour une date de cours
     * @return json
     */
    public function actionPresence() {
        if (isset($_POST['personne']) && isset($_POST['coursdate'])) {
            $myClientsHasCoursDate = ClientsHasCoursDate::findOne(['fk_personne' => $_POST['personne'], 'fk_cours_date' => $_POST['coursdate']]);
            $myClientsHasCoursDate->is_present = !$myClientsHasCoursDate->is_present;
            $myClientsHasCoursDate->save(false);
            echo \yii\helpers\Json::encode([
                'status' => 'success',
                'message' => Yii::t('app', 'Modification enregistrée')
            ]);
        }
    }
}
