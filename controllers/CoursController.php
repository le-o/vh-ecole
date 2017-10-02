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
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
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
                        'actions' => ['getcoursjson'],
//                        'ips' => ['127.0.0.1'],
                    ],
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
            [
                'class' => 'yii\filters\PageCache',
                'only' => ['getcoursjson'],
                'duration' => 60,
                'dependency' => [
                    'class' => 'yii\caching\DbDependency',
                    'sql' => 'SELECT * FROM cours WHERE is_actif = 1 AND is_publie = 1',
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
        } elseif ($msg === 'inscrip') {
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'Toutes les modifications sur les inscriptions ont été enregistrées !');
        } elseif ($msg === 'monit') {
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'Toutes les modifications sur les moniteurs ont été enregistrées !');
        } elseif ($msg === 'presence') {
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'Toutes les modifications sur les présences ont été enregistrées !');
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
                        $modelClientsHasCoursDate->fk_statut = Yii::$app->params['partInscrit'];
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
                    // petite astuce pour enregistrer comme il faut le tableau des jours dans la bdd
                    $model->fk_jours = Yii::$app->request->post()['Cours']['fk_jours'];
                    $model->fk_categories = Yii::$app->request->post()['Cours']['fk_categories'];
                    $model->image_hidden = Yii::$app->request->post()['Cours']['image_hidden'];
                    
                    // on s'occupe de sauver l'image si elle existe
                    if ($image = UploadedFile::getInstance($model, 'image')) {
                        // store the source file extension
                        $ext = end((explode(".", $image->name)));
                        // generate a unique file name
                        $model->image_web = Yii::$app->security->generateRandomString().".{$ext}";
                        $path = Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web;
                        if (!$image->saveAs($path)) {
                            $alerte = Yii::t('app', 'Problème lors de la sauvegarde de l\'image.');
                        }
                    } elseif ($model->image_hidden == '') {
                        if (is_file(Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web)) {
                            unlink(Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web);
                        }
                        $model->image_web = null;
                    }
                    
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
        
        // on assigne la valeur de l'image au champ caché
        $model->image_hidden = $model->image_web;

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
            // petite astuce pour enregistrer comme il faut le tableau des jours dans la bdd
            $model->fk_jours = Yii::$app->request->post()['Cours']['fk_jours'];
            $model->fk_categories = Yii::$app->request->post()['Cours']['fk_categories'];

            // on s'occupe de sauver l'image si elle existe
            if ($image = UploadedFile::getInstance($model, 'image')) {
                // store the source file extension
                $ext = end((explode(".", $image->name)));
                // generate a unique file name
                $model->image_web = Yii::$app->security->generateRandomString().".{$ext}";
                $path = Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web;
                if (!$image->saveAs($path)) {
                    $alerte = Yii::t('app', 'Problème lors de la sauvegarde de l\'image.');
                }
            }
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
            // petite astuce pour enregistrer comme il faut le tableau des jours dans la bdd
            $model->fk_jours = Yii::$app->request->post()['Cours']['fk_jours'];
            $model->fk_categories = Yii::$app->request->post()['Cours']['fk_categories'];
                    
            // on s'occupe de sauver l'image si elle existe
            if ($image = UploadedFile::getInstance($model, 'image')) {
                // store the source file extension
                $ext = end((explode(".", $image->name)));
                // generate a unique file name
                $model->image_web = Yii::$app->security->generateRandomString().".{$ext}";
                $path = Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web;
                if (!$image->saveAs($path)) {
                    $alerte = Yii::t('app', 'Problème lors de la sauvegarde de l\'image.');
                }
            } elseif ($model->image == '') {
                if (is_file(Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web)) {
                    unlink(Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web);
                }
                $model->image_web = null;
            }
            
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
     * Deletes an existing ClientsHasCoursDate model.
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
                    ClientsHasCoursDate::updateAll(['fk_statut' => Yii::$app->params['partStatutDesinscritFutur']], ['fk_personne' => $personne_id, 'fk_cours_date' => $c->cours_date_id]);
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
            $dates = CoursDate::findAll(['fk_cours' => $id]);
            foreach ($dates as $date) {
                CoursHasMoniteurs::deleteAll(['fk_cours_date' => $date->cours_date_id]);
                ClientsHasCoursDate::deleteAll(['fk_cours_date' => $date->cours_date_id]);
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
     * Affichage de la page de gestion globale des inscriptions.
     * Permet la mise à jour des données des inscriptions pour un cours (toutes les dates)
     * @param integer $id
     * @return mixed
     */
    public function actionGestioninscriptions($cours_id) {
        $model = $this->findModel($cours_id);
        $alerte = '';
        
        if (!empty(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            
            if (isset($post['new_inscription']) && !empty($post['new_inscription'])) {
                $dejaParticipants[] = $post['new_inscription'];
                $newParticipant = new ClientsHasCoursDate();
                $newParticipant->fk_personne = $post['new_inscription'];
            } else {
            
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    // on supprime toutes les inscriptions pour les cours en question
                    foreach ($model->coursDates as $coursDate) {
                        foreach ($coursDate->clientsHasCoursDate as $participant) {
                            $saveStatut[$participant->fk_personne]['part'] = $participant;
                        }
                        ClientsHasCoursDate::deleteAll('fk_cours_date = :cours_date_id', ['cours_date_id' => $coursDate->cours_date_id]);
                    }

                    // on reconstruit la liste d'après la saisie
                    foreach ($post['dateparticipant'] as $parDate) {
                        foreach ($parDate as $key => $v) {
                            $ids = explode('|', $key);
                            $addParticipant = new ClientsHasCoursDate();
                            $addParticipant->fk_personne = $ids[1];
                            $addParticipant->fk_cours_date = $ids[0];
                            $addParticipant->is_present = 1;
                            $addParticipant->fk_statut= (isset($saveStatut[$ids[1]])) ? $saveStatut[$ids[1]]['part']->fk_statut : Yii::$app->params['partInscrit'];
                            if (!($flag = $addParticipant->save(false))) {
                                throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du participant (ID '.$ids[1].'.'));
                            }
                        }
                    }

                    $transaction->commit();
                    // on redirige vers la page du cours
                    $msg = 'inscrip';
                    return $this->redirect(['/cours/view', 'id' => $cours_id, 'msg' => $msg]);
                    
                } catch (Exception $e) {
                    $alerte = $e->getMessage();
                    $transaction->rollBack();
                }
            }
        }
        
        // préparation des data
        foreach ($model->coursDates as $coursDate) {
            $arrayData[$coursDate->cours_date_id]['model'] = $coursDate;
            foreach ($coursDate->clientsHasCoursDate as $participant) {
                $arrayParticipants[$participant->fk_personne] = $participant;
                $dejaParticipants[] = $participant->fk_personne;
                $arrayData[$coursDate->cours_date_id]['participants'][$participant->fk_personne] = $participant;
            }
        }
        
        $modelParticipants = Personnes::find()->where(['<>', 'fk_type', Yii::$app->params['typeEncadrant']])->andWhere(['not in', 'personne_id', $dejaParticipants])->orderBy('nom, prenom')->all();
        foreach ($modelParticipants as $participant) {
            $dataParticipants[$participant->fkStatut->nom][$participant->personne_id] = $participant->NomPrenom;
        }
        
        // on ajoute au tableau le nouveau participant choisi
        if (isset($newParticipant)) $arrayParticipants[$newParticipant->fk_personne] = $newParticipant;
        
        return $this->render('inscriptions', [
	        'alerte' => $alerte,
            'model' => $model,
            'dataParticipants' => $dataParticipants,
            'arrayParticipants' => $arrayParticipants,
            'arrayData' => $arrayData,
        ]);
    }
    
    /**
     * Affichage de la page de gestion globale des inscriptions.
     * Permet la mise à jour des données des personnes inscrites
     * @param integer $id
     * @return mixed
     */
    public function actionGestionmoniteurs($cours_id) {
        $model = $this->findModel($cours_id);
        $alerte = '';
        
        if (!empty(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            
            if (isset($post['new_moniteur']) && !empty($post['new_moniteur'])) {
                $dejaMoniteurs[] = $post['new_moniteur'];
                $newMoniteur = new CoursHasMoniteurs();
                $newMoniteur->fk_moniteur = $post['new_moniteur'];
            } else {
            
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    // on supprime tous les moniteurs pour les cours en question
                    foreach ($model->coursDates as $coursDate) {
                        $existeMoniteurs = CoursHasMoniteurs::find()->where('fk_cours_date = :cours_date_id', ['cours_date_id' => $coursDate->cours_date_id])->all();
                        CoursHasMoniteurs::deleteAll('fk_cours_date = :cours_date_id', ['cours_date_id' => $coursDate->cours_date_id]);
                        
                        foreach ($existeMoniteurs as $monit) {
                            $emails[$monit->fk_moniteur] = $monit->fkMoniteur->email;
                        }
                    }
                    
                    // on met les dates dans le bon ordre avant le traitement
                    ksort($post['datemoniteur']);

                    // on reconstruit la liste d'après la saisie
                    foreach ($post['datemoniteur'] as $parDate) {
                        foreach ($parDate as $key => $v) {
                            $ids = explode('|', $key);
                            $addMoniteur = new CoursHasMoniteurs();
                            $addMoniteur->fk_cours_date = $ids[0];
                            $addMoniteur->fk_moniteur = $ids[1];
                            $addMoniteur->is_responsable = 0;
                            if (!($flag = $addMoniteur->save(false))) {
                                throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
                            }
                            $emails[$addMoniteur->fk_moniteur] = $addMoniteur->fkMoniteur->email;

                            $dates[$addMoniteur->fk_cours_date]['date'] = $addMoniteur->fkCoursDate->date;
                            $dates[$addMoniteur->fk_cours_date]['heure'] = substr($addMoniteur->fkCoursDate->heure_debut, 0, 5);
                            $dates[$addMoniteur->fk_cours_date]['moniteurs'][] = $addMoniteur->fkMoniteur->prenom.' '.$addMoniteur->fkMoniteur->nom;
                            $dates[$addMoniteur->fk_cours_date]['remarque'] = $addMoniteur->fkCoursDate->remarque;
                        }
                    }
                    
                    $valeurEmail = 'Des modifications ont été apportées aux cours suivants. Prière de prendre bonne note.<br />Merci et à bientôt.<br /><br />
                            Cours : '.$model->fkNom->nom.' <br />
                            Session : '.$model->session.'<br />
                            Année : '.$model->annee;
                    foreach ($dates as $date) {
                        $valeurEmail .= '<br />Date, heure | moniteur(s) : '.$date['date'].', '.$date['heure'].' | '.  implode(', ', $date['moniteurs']);
                        if ($date['remarque'] != '') $valeurEmail .= ' - <i>Infos : '.$date['remarque'].'</i>';
                    }
                    
                    // on envoi l'email à tous les moniteurs - y compris ceux qui ont été supprimé
                    $contenu = ['nom' => $model->fkNom->nom.' - modifications', 'valeur' => $valeurEmail];
                    SiteController::actionEmail($contenu, $emails);

                    $transaction->commit();
                    // on redirige vers la page du cours
                    $msg = 'monit';
                    return $this->redirect(['/cours/view', 'id' => $cours_id, 'msg' => $msg]);
                    
                } catch (Exception $e) {
                    $alerte = $e->getMessage();
                    $transaction->rollBack();
                }
            }
        }
        
        // préparation des data
        foreach ($model->coursDates as $coursDate) {
            $arrayData[$coursDate->cours_date_id]['model'] = $coursDate;
            foreach ($coursDate->coursHasMoniteurs as $moniteur) {
                $arrayMoniteurs[$moniteur->fk_moniteur] = $moniteur;
                $dejaMoniteurs[] = $moniteur->fk_moniteur;
                $arrayData[$coursDate->cours_date_id]['moniteurs'][$moniteur->fk_moniteur] = $moniteur;
            }
        }
        
        $modelMoniteurs = Personnes::find()->where(['fk_type' => Yii::$app->params['typeEncadrant']])->andWhere(['not in', 'personne_id', $dejaMoniteurs])->orderBy('nom, prenom')->all();
        foreach ($modelMoniteurs as $moniteur) {
            $dataMoniteurs[$moniteur->fkStatut->nom][$moniteur->personne_id] = $moniteur->NomPrenom;
        }
        
        // on ajoute au tableau le nouveau moniteur choisi
        if (isset($newMoniteur)) $arrayMoniteurs[$newMoniteur->fk_moniteur] = $newMoniteur;
        
        return $this->render('moniteurs', [
	        'alerte' => $alerte,
            'model' => $model,
            'dataMoniteurs' => $dataMoniteurs,
            'arrayMoniteurs' => $arrayMoniteurs,
            'arrayData' => $arrayData,
        ]);
    }
    
    /**
     * Affichage de la page de gestion globale des présences.
     * Permet la mise à jour des données des présences pour un cours
     * @param integer $id
     * @return mixed
     */
    public function actionGestionpresences($cours_id) {
        $model = $this->findModel($cours_id);
        $alerte = '';
        
        if (!empty(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                // on met toutes les présences à 0 pour les cours en question
                foreach ($model->coursDates as $coursDate) {
                    ClientsHasCoursDate::updateAll(['is_present' => 0], 'fk_cours_date = '.$coursDate->cours_date_id);
                }

                // on reconstruit la liste d'après la saisie
                foreach ($post['dateparticipant'] as $parDate) {
                    foreach ($parDate as $key => $v) {
                        $ids = explode('|', $key);
                        $myClientCours = ClientsHasCoursDate::find()->where('fk_personne = :personne_id AND fk_cours_date = :cours_date_id', ['personne_id' => $ids[1], 'cours_date_id' => $ids[0]])->one();
                        $myClientCours->is_present = 1;
                        if (!($flag = $myClientCours->save())) {
                            throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde de la présence (IDs '.$ids[1].'|'.$ids[0].'.'));
                        }
                    }
                }

                $transaction->commit();
                // on redirige vers la page du cours
                $msg = 'presence';
                return $this->redirect(['/cours/view', 'id' => $cours_id, 'msg' => $msg]);

            } catch (Exception $e) {
                $alerte = $e->getMessage();
                $transaction->rollBack();
            }
        }
        
        // préparation des data
        foreach ($model->coursDates as $coursDate) {
            $arrayData[$coursDate->cours_date_id]['model'] = $coursDate;
            foreach ($coursDate->clientsHasCoursDate as $participant) {
                $arrayParticipants[$participant->fk_personne] = $participant;
                $arrayData[$coursDate->cours_date_id]['participants'][$participant->fk_personne] = $participant;
            }
        }
        
        return $this->render('presences', [
	        'alerte' => $alerte,
            'model' => $model,
            'arrayParticipants' => $arrayParticipants,
            'arrayData' => $arrayData,
        ]);
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
    
    public function actionGetcoursjson() {
        $searchModel = new CoursSearch();
        $searchModel->is_actif = 1;
        $searchModel->is_publie = 1;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        if ($dataProvider->count == 0) {
            $data = ["Aucune donnée trouvée"];
        } else {
            foreach ($dataProvider->getModels() as $c) {
                $jours = [];
                foreach ($c->fkJours as $j) {
                    $jours[] = $j->nom;
                }
                $categories = [];
                foreach ($c->fkCategories as $cat) {
                    $categories[] = $cat->nom;
                }
                $dates = [];
                foreach ($c->coursDates as $d) {
                    $dates[] = date('Y-m-d', strtotime($d->date)).' '.$d->heure_debut;
                }
                $data[] = [
                    'id' => $c->cours_id,
                    'nom' => $c->fkNom->nom,
                    'niveau' => $c->fkNiveau->nom,
                    'semestre' => ($c->fk_semestre != '') ? $c->fkSemestre->nom : '',
                    'saison' => ($c->fk_saison != '') ? $c->fkSaison->nom : '',
                    'session' => $c->session,
                    'jours_semaine' => $jours,
                    'type' => $c->fkType->nom,
                    'annee' => $c->annee,
                    'duree' => $c->duree,
                    'prix' => $c->prix,
                    'participant_max' => $c->participant_max,
                    'nombre_inscrit' => $c->NombreClientsInscrits,
                    'tranche_age' => $c->fkAge->nom,
                    'materiel_compris' => ($c->is_materiel_compris == true) ? 'Oui' : 'Non',
                    'entree_compris' => ($c->is_entree_compris == true) ? 'Oui' : 'Non',
                    'offre_speciale' => $c->offre_speciale,
                    'premier_jour_session' => date('Y-m-d', strtotime($c->FirstCoursDate->date)),
                    'toutes_les_dates' => $dates,
                    'extrait' => $c->extrait,
                    'description' => $c->description,
                    'offre_speciale' => $c->offre_speciale,
                    'categories' => $categories,
                    'image_web' => ($c->image_web != '') ? Url::home(true).'/../../_files/images/'.$c->image_web : '',
                ];
            }
        }
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        header('Content-Type: application/json; charset=utf-8');
        return $data;
        exit;
    }
}
