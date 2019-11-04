<?php

namespace app\controllers;

use Yii;
use app\models\CoursDate;
use app\models\CoursDateSearch;
use app\models\ClientsHasCours;
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
use webvimark\modules\UserManagement\models\User;

/**
 * CoursDateController implements the CRUD actions for CoursDate model.
 */
class CoursDateController extends CommonController
{
    
    public $freeAccessActions = ['jsoncalendar'];
    
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'ghost-access'=> [
                'class' => 'webvimark\modules\UserManagement\components\GhostAccessControl',
            ],
        ];
    }
    
    // for route purpose only
    public function actionAdvanced() {}

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
        
        if ($post = Yii::$app->request->post()) {
            if (!empty($post['new_participant'])) {
                // soit on ajoute un participant
                $alerte = $this->addClientToCours([$model], $post['new_participant'], $model->fk_cours);
            } elseif (!empty($post['CoursDate'])) {
                $clone = clone($model);
                $model->load(Yii::$app->request->post());
                $moniteurs = (isset($post['list_moniteurs'])) ? $post['list_moniteurs'] : [];
                
                foreach ($clone->coursHasMoniteurs as $myMoniteur) {
                    $moniteursOld[] = $myMoniteur->fk_moniteur;
                }
                
                $mailToMoniteurs = false;
                if ($moniteurs != $moniteursOld) {
                    $mailToMoniteurs = true;
                } elseif ($model->date !== $clone->date) {
                    $mailToMoniteurs = true;
                } elseif ($model->heure_debut != $clone->heure_debut || $model->duree != $clone->duree) {
                    $mailToMoniteurs = true;
                }

                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save()) {
                        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du cours.'));
                    }
                    
                    if ($mailToMoniteurs == true) {
                        $infosEmail = $this->saveMoniteur($model->cours_date_id, $moniteurs, true);
                        // on envoi l'email à tous les moniteurs
                        if (!empty($infosEmail['emails'])) {
                            $contenu = $this->generateMoniteurEmail($model, $infosEmail['noms'], 'update');
                            $this->actionEmail($contenu, $infosEmail['emails']);
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
                $this->actionEmail($post['Parametres'], explode(', ', $post['Parametres']['listeEmails']));
                $alerte['class'] = 'info';
                $alerte['message'] = Yii::t('app', 'Email envoyé à tous les participants');
            }
        }
        
        foreach ($model->coursHasMoniteurs as $myMoniteur) {
            $moniteurs[] = $myMoniteur->fkMoniteur->nomPrenom;
        }
        $listeMoniteurs = (isset($moniteurs)) ? implode(', ', $moniteurs) : '';

        // Gestion des participants - différente si planifié ou sur demande
        $listParticipants = [];
        if ($model->fkCours->fk_type == Yii::$app->params['coursPonctuel']) {
            foreach ($model->clientsHasCoursDate as $c) {
                $listParticipants[] = $c->fkPersonne;
            }
        } else {
            $participants = Personnes::find()->distinct()->joinWith('clientsHasCours', false)->where(['IN', 'clients_has_cours.fk_cours', $model->fk_cours])->orderBy('clients_has_cours.fk_statut ASC');
            $listParticipants = $participants->all();
        }
        $excludePart = [];
        $listeEmails = [];
        foreach ($listParticipants as $participant) {
            $excludePart[] = $participant->personne_id;

            if (strpos($participant->email, '@') !== false) {
                $listeEmails[$participant->email] = trim($participant->email);
            }

            foreach ($participant->personneHasInterlocuteurs as $pi) {
                $listeEmails[$pi->fkInterlocuteur->email] = trim($pi->fkInterlocuteur->email);
            }
        }
        
        $dataClients = Personnes::getClientsNotInCours($excludePart);
        
        $arrayParticipants = $listParticipants;
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
        $modelMoniteurs = Personnes::find()->where(['fk_type' => Yii::$app->params['typeEncadrantActif']])->orderBy('nom, prenom')->all();
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
            $isInscrit = ClientsHasCours::findOne(['fk_personne' => $part->personne_id, 'fk_cours' => $model->fk_cours]);
            if (isset($isInscrit->fk_statut)) {
                $part->statutPart = $isInscrit->fkStatut->nom;
            }
        }
        
        $parametre = new Parametres();
        $parametre->listeEmails = implode(', ', $listeEmails);
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();

        return $this->render('view', [
            'alerte' => $alerte,
            'model' => $model,
            'listeMoniteurs' => (isset($listeMoniteurs)) ? $listeMoniteurs : '',
            
            'dataCours' => $dataCours,
            'dataMoniteurs' => $dataMoniteurs,
            'selectedMoniteurs' => (isset($selectedMoniteurs)) ? $selectedMoniteurs : [],

            'isInscriptionOk' => (User::hasRole('Admin') || $participantDataProvider->totalCount < $model->fkCours->participant_max) ? true : false,
            'dataClients' => $dataClients,
            'participantDataProvider' => $participantDataProvider,
            'participantIDs' => $excludePart,
            'parametre' => $parametre,
            'emails' => $emails,
            'modelParams' => new Parametres,
        ]);
    }
    
    /**
     * Lists all CoursDate models with participants.
     * @return mixed
     */
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
     * Lists all CoursDate models with participants in the futur.
     * @return mixed
     */
    public function actionActif()
    {
        $searchModel = new CoursDateSearch();
        $searchModel->depuis = date('d.m.Y');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        if (!empty(Yii::$app->request->post())) {
            $mail = Yii::$app->request->post();
            $this->actionEmail($mail['Parametres'], explode(', ', $mail['checkedEmails']));

            $alerte['class'] = 'info';
            $alerte['message'] = Yii::t('app', 'Email envoyé à toutes les personnes sélectionnées');
        }
        
        $arrayParticipants = [];
        $listeEmails = [];
        foreach ($dataProvider->models as $data) {
            foreach ($data->clientsHasCoursDate as $client) {
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['statutPart'] = $client->fkStatut->nom;
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['nomCours'] = $data->fkCours->fkNom->nom;
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['niveauCours'] = $data->fkCours->fkNiveau->nom;
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['nom'] = $client->fkPersonne->nom;
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['prenom'] = $client->fkPersonne->prenom;
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['suivi_client'] = $client->fkPersonne->suivi_client;
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['age'] = $client->fkPersonne->age;
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['cours_id'] = $data->fk_cours;
                $arrayParticipants[$data->fk_cours.'*'.$client->fk_personne]['personne_id'] = $client->fk_personne;
                
                if (strpos($client->fkPersonne->email, '@') !== false) {
                    $listeEmails[$client->fkPersonne->email] = trim($client->fkPersonne->email);
                }
                foreach ($client->fkPersonne->personneHasInterlocuteurs as $pi) {
                    $listeEmails[$pi->fkInterlocuteur->email] = trim($pi->fkInterlocuteur->email);
                }
            }
        }
        // pour trier, par chance c'est dans le bon ordre :)
        asort($arrayParticipants);
        
        $participantDataProvider = new ArrayDataProvider([
            'allModels' => $arrayParticipants,
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
        
        $parametre = new Parametres();
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();
        
        return $this->render('actif', [
            'dataProvider' => $participantDataProvider,
            'searchModel' => $searchModel,
            'parametre' => $parametre,
            'emails' => $emails,
            'listeEmails' => $listeEmails,
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
                
                $infosEmail = $this->saveMoniteur($model->cours_date_id, $moniteurs);
                // on envoi l'email à tous les moniteurs
                if (!empty($infosEmail['emails'])) {
                    $contenu = $this->generateMoniteurEmail($model, $infosEmail['noms'], 'create');
                    $this->actionEmail($contenu, $infosEmail['emails']);
                }
                
                // on inscrit les participants déjà existant pour les autres planifications de ce cours
                // seulement pour les cours planifiés (planifié et régulié)
                if (in_array($myCours->fk_type, Yii::$app->params['coursPlanifieS'])) {
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
                            $alerte = $this->addClientToCours([$coursDate], $part->fk_personne, $cours_id);
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
            'modelParams' => new Parametres,
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
            'modelParams' => new Parametres,
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
        $model = $this->findModel($id);
        $moniteurs = $model->coursHasMoniteurs;
        
        foreach ($model->coursHasMoniteurs as $moniteur) {
            $emails[] = $moniteur->fkMoniteur->email;
            $nomMoniteurs[] = $moniteur->fkMoniteur->prenom.' '.$moniteur->fkMoniteur->nom;
        }
        
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            CoursHasMoniteurs::deleteAll(['fk_cours_date' => $id]);
            ClientsHasCoursDate::deleteAll(['fk_cours_date' => $id]);
            $model = $this->findModel($id);
            $fk_cours = $model->fk_cours;
            $model->delete();
            $transaction->commit();
            
            // on envoi l'email à tous les moniteurs
            if (!empty($emails)) {
                $contenu = $this->generateMoniteurEmail($model, $nomMoniteurs, 'delete');
                $this->actionEmail($contenu, $emails);
            }
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
    public function actionJsoncalendar($start=NULL,$end=NULL,$_=NULL,$for){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $times = CoursDate::find()
            ->joinWith(['fkCours'])
            ->where(['>=', 'date', $start])
            ->andWhere(['<=', 'date', $end])
            ->andWhere(['cours.fk_salle' => $for])
            ->all();
        
        Yii::$app->session->set('home-cal-debut-' . $for, $start);

        $events = [];
        foreach ($times AS $time){
            //Testing
            $Event = new \yii2fullcalendar\models\Event();
            $Event->id = $time->cours_date_id;
            $Event->url = Url::to(['/cours-date/view', 'id' => $time->cours_date_id]);
            
            if ($time->fkCours->fk_type == Yii::$app->params['coursPonctuel']) {
                $Event->title = (isset($time->clientsHasCoursDate[0]) ? $time->clientsHasCoursDate[0]->fkPersonne->suivi_client.' '.$time->clientsHasCoursDate[0]->fkPersonne->societe.' '.$time->clientsHasCoursDate[0]->fkPersonne->nomPrenom : Yii::t('app', 'Client non défini'));
                $Event->title .= ' '.$time->fkCours->fkNom->nom.' '.$time->fkCours->session;
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
            
            if ($time->fkCours->fkNom->info_couleur != '' && in_array($time->fkCours->fk_nom, Yii::$app->params['coursModificationCouleur'])) {
                $Event->color = Parametres::changerTonCouleur($time->fkCours->fkNom->info_couleur, Yii::$app->params['nuanceSelonNiveau'][$time->fkCours->fkNiveau->tri]);
            } else {
                $Event->color = $time->fkCours->fkNom->info_couleur;
            }
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
