<?php

namespace app\controllers;

use Yii;
use app\models\Cours;
use app\models\CoursSearch;
use app\models\CoursDate;
use app\models\ClientsHasCoursDate;
use app\models\ClientsHasCours;
use app\models\CoursHasMoniteurs;
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
class CoursController extends CommonController
{
    
    public $freeAccessActions = ['getcoursjson'];
    
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
                'class' => 'leo\modules\UserManagement\components\GhostAccessControl',
            ],
            [
                'class' => 'yii\filters\PageCache',
                'only' => ['getcoursjson'],
                'duration' => 60,
                'dependency' => [
                    'class' => 'yii\caching\DbDependency',
                    'sql' => 'SELECT * FROM cours WHERE fk_statut = ' . Yii::$app->params['coursActif'] . ' AND is_publie = 1',
                ],
            ],
        ];
    }
    
    // for route purpose only
    public function actionAdvanced() {}

    /**
     * Lists all Cours models.
     * @return mixed
     */
    public function actionIndex($salle = null, $onlyForWeb = null)
    {
        $searchModel = new CoursSearch();
        $dataSalles = Parametres::findAll(['class_key' => 16]);
        
        $filterSalle = Yii::$app->session['salles'];

        // on sauve les filtres et la pagination
        $params = Yii::$app->request->queryParams;
        if (count($params) <= 1) {
            if (isset(Yii::$app->session['CoursSearch'])) {
                $params = Yii::$app->session['CoursSearch'];
            } else {
                Yii::$app->session['CoursSearch'] = $params;
            }
        } else {
            if (isset(Yii::$app->request->queryParams['CoursSearch'])) {
                Yii::$app->session['CoursSearch'] = $params;
            } else {
                $params = Yii::$app->session['CoursSearch'];
            }
        }

        if (empty($salle) && $onlyForWeb === null) {
            if ($onlyForWeb === null) {
                $filterSalle = [];
                foreach ($dataSalles as $s) {
                    $filterSalle[] = $s->parametre_id;
                }
            }
        } elseif (!empty($salle)) {
            $plusOuMoins = substr($salle, 0, 1);
            $salleID = substr($salle, 1);
            if ('+' == $plusOuMoins) {
                $filterSalle[] = (int)$salleID;
            } elseif ('-' == $plusOuMoins) {
                if (null != $filterSalle && ($key = array_search($salleID, $filterSalle)) !== false) {
                    unset($filterSalle[$key]);
                }
            }
        }
        
        $searchModel->bySalle = $filterSalle;
        if ($onlyForWeb !== null) {
            Yii::$app->session['onlyForWeb'] = $onlyForWeb;
        } else {
            $onlyForWeb = Yii::$app->session['onlyForWeb'];
        }
        $searchModel->isPriorise = $onlyForWeb;
        Yii::$app->session['salles'] = $filterSalle;
        $dataProvider = $searchModel->search($params);
        
        foreach ($dataSalles as $s) {
            $btnSalle[] = [
                'salleID' => (in_array($s->parametre_id, $searchModel->bySalle)) ? '-' . $s->parametre_id : '+' . $s->parametre_id,
                'label' => $s->nom,
                'class' => (in_array($s->parametre_id, $searchModel->bySalle)) ? ' btn-info' : '',
            ];
        }
        $btnClassPriorise = ($onlyForWeb ? ' btn-info' : '');
        
        $parametre = new Parametres();
        $saisonFilter = $parametre->optsSaison();
        $statutFilter = $parametre->optsStatutCours();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'saisonFilter' => $saisonFilter,
            'statutFilter' => $statutFilter,
            'btnSalle' => $btnSalle,
            'btnClassPriorise' => $btnClassPriorise,
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
        } elseif ($msg === 'clone') {
            $alerte['class'] = 'success';
            $alerte['message'] = Yii::t('app', 'Le cours a été dupliqué avec succès.');
        } elseif ($msg === 'mailKo') {
            $alerte['class'] = 'warning';
            $alerte['message'] = Yii::t('app', 'Cours sauvé, mais problème lors de l\'envoi du mail au moniteur.');
        } elseif ($msg !== '') {
            $alerte['class'] = 'danger';
            $alerte['message'] = $msg;
        }
        
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
                    ->orderBy('date')
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
                    $alerte = $this->addClientToCours($modelDate, $new['new_participant'], $model->cours_id);
                }
            } elseif (!empty($new['Parametres'])) {
                // soit on envoi un email !
                $this->actionEmail($new['Parametres'], explode(', ', $new['Parametres']['listeEmails']));
                $alerte['class'] = 'info';
                $alerte['message'] = Yii::t('app', 'Email envoyé à tous les participants');
            } elseif (!empty($new['Cours'])) {
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
                            $alerte['class'] = 'warning';
                            $alerte['message'] = Yii::t('app', 'Problème lors de la sauvegarde de l\'image.');
                        }
                    } elseif ($model->image_hidden == '') {
                        if (is_file(Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web)) {
                            unlink(Yii::$app->basePath . Yii::$app->params['uploadPath'] . $model->image_web);
                        }
                        $model->image_web = null;
                    }

                    // on gère le changement de prix indépendamment
                    // on sauve le prix si différent, pour modifier les instance cours_date
                    $newPrice = null;
                    if ((float)$model->attributes['prix'] != (float)$model->oldAttributes['prix'] && '' !== Yii::$app->request->post()['editPrice']) {
                        $newPrice = $model->attributes['prix'];
                    }

                    if (!$model->save()) {
                        $alerte['class'] = 'warning';
                        $alerte['message'] = Yii::t('app', 'Problème lors de la sauvegarde du cours.');
                    } else {
                        if (null !== $newPrice) {
                            CoursDate::updateAll(['prix' => $newPrice], 'fk_cours = ' . $model->cours_id);
                        }
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
        
        $participants = Personnes::find()->distinct()->joinWith('clientsHasCours', false)->where(['IN', 'clients_has_cours.fk_cours', $model->cours_id])->orderBy('clients_has_cours.fk_statut ASC');
        $listParticipants = $participants->all();
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

        // liste des dates de cours
        $coursDate = CoursDate::find()->where(['fk_cours' => $model->cours_id])->orderBy('date');
        $coursDateProvider = new ActiveDataProvider([
            'query' => $coursDate,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $participantDataProvider = new ActiveDataProvider([
            'query' => $participants,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        foreach($participantDataProvider->models as $part) {
            $part->statutPart = ClientsHasCours::findOne(['fk_personne' => $part->personne_id, 'fk_cours' => $model->cours_id])->fkStatut->nom;
            $part->statutPartID = ClientsHasCours::findOne(['fk_personne' => $part->personne_id, 'fk_cours' => $model->cours_id])->fk_statut;
        }

        $parametre = new Parametres();
        $parametre->listeEmails = implode(', ', $listeEmails);
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();
        
        // pour l'affichage des paramètres en mode édition
        $modelParams = new Parametres;
	    
        return $this->render('view', [
            'alerte' => $alerte,
            'model' => $model,
            'modelParams' => $modelParams,
            'coursDateProvider' => $coursDateProvider,
            'dataClients' => $dataClients,
            'participantDataProvider' => $participantDataProvider,
            'participantIDs' => $excludePart,
            'parametre' => $parametre,
            'emails' => $emails,
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
     * Clone a Cours model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionClone($id)
    {
        $model = $this->findModel($id);
        $clone = new Cours();
        $clone->attributes = $model->attributes;
        $clone->fk_jours = $model->fk_jours;
        $clone->fk_categories = $model->fk_categories;
        $clone->isNewRecord = true;
        $clone->save();
        return $this->redirect(['view', 'id' => $clone->cours_id, 'msg' => 'clone']);
    }

    /**
     * Updates an existing Cours model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @deprecated Remplacée par actionView
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
                $clientHasCoursDate = ClientsHasCoursDate::findOne(['fk_personne' => $personne_id, 'fk_cours_date' => $cours_ou_date_id]);
                if (Yii::$app->params['coursUnique'] == $clientHasCoursDate->fkCoursDate->fkCours->fk_type) {
                    $clientsHasCours = ClientsHasCours::findOne(['fk_cours' => $clientHasCoursDate->fkCoursDate->fk_cours, 'fk_personne' => $personne_id]);
                    if (!empty($clientsHasCours)) {
                        $clientsHasCours->delete();
                    }
                }
                $clientHasCoursDate->delete();
            } else {
                if ($from == 'coursfutur') {
                    $coursDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $cours_ou_date_id])
                        ->andWhere(['>=', 'date', date('Y-m-d')])
                        ->all();
                    $from = 'cours';
                    $clientsHasCours = ClientsHasCours::findOne(['fk_cours' => $cours_ou_date_id, 'fk_personne' => $personne_id]);
                    $clientsHasCours->fk_statut = Yii::$app->params['partDesinscrit'];
                    $clientsHasCours->save();
                } elseif ($from == 'cours-datefutur') {
                    $coursDateBase = CoursDate::find()
                        ->where(['=', 'cours_date_id', $cours_ou_date_id])
                        ->one();
                    $coursDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $coursDateBase->fk_cours])
                        ->andWhere(['>=', 'date', date('Y-m-d', strtotime($coursDateBase->date))])
                        ->all();
                    $from = 'cours-date';
                    $clientsHasCours = ClientsHasCours::findOne(['fk_cours' => $coursDateBase->fk_cours, 'fk_personne' => $personne_id]);
                    $clientsHasCours->fk_statut = Yii::$app->params['partDesinscrit'];
                    $clientsHasCours->save();
                } else {
                    $coursDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $cours_ou_date_id])
                        ->all();
                    $clientsHasCours = ClientsHasCours::findOne(['fk_cours' => $cours_ou_date_id, 'fk_personne' => $personne_id]);
                    $clientsHasCours->delete();
                }
                // on supprime les dates du client
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
        $firstDate = null;
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $dates = CoursDate::findAll(['fk_cours' => $id]);
            foreach ($dates as $date) {
                if ($firstDate === null) {
                    $firstDate = $date;
                }
                $allDate[] = $date;
                foreach ($date->coursHasMoniteurs as $moniteur) {
                    $emails[$moniteur->fk_moniteur] = $moniteur->fkMoniteur->email;
                    $nomMoniteurs[$moniteur->fk_moniteur] = $moniteur->fkMoniteur->prenom.' '.$moniteur->fkMoniteur->nom;
                }
                
                CoursHasMoniteurs::deleteAll(['fk_cours_date' => $date->cours_date_id]);
                ClientsHasCoursDate::deleteAll(['fk_cours_date' => $date->cours_date_id]);
            }
            CoursDate::deleteAll(['fk_cours' => $id]);
            
            // on envoi l'email à tous les moniteurs
            if (!empty($emails)) {
                $contenu = $this->generateMoniteurEmail($firstDate, $nomMoniteurs, 'delete', $allDate);
                $this->actionEmail($contenu, $emails);
            }
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
                
                $allParticipants = json_decode($post['allParticipants']);
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    // on gère le lien client-has-cours
                    foreach ($allParticipants as $partID => $vide) {
                        $myClientsHasCours = ClientsHasCours::findOne(['fk_personne' => $partID, 'fk_cours' => $cours_id]);
                        if (null === $myClientsHasCours) {
                            $myClientsHasCours = new ClientsHasCours;
                            $myClientsHasCours->fk_personne = $partID;
                            $myClientsHasCours->fk_cours = $cours_id;
                            $myClientsHasCours->fk_statut = Yii::$app->params['partInscrit'];
                            $myClientsHasCours->save();
                        }
                    }
                    foreach ($model->coursDates as $coursDate) {
                        $laDate = date('Ymd', strtotime($coursDate->date));

                        // on test si l'entrée existe déjà
                        // si oui, on choisit si il faut laisser l'entrée (inscrit = coche) ou la supprimer (pas inscrit)
                        // si non, on créé l'entrée dans le cas ou il y a la coche
                        foreach ($allParticipants as $partID => $vide) {
                            // ensuite on s'occupe du lien client-has-cours-date
                            $key = $coursDate->cours_date_id . '|' . $partID;
                            $myClientsHasCoursDate = ClientsHasCoursDate::findOne(['fk_cours_date' => $coursDate->cours_date_id, 'fk_personne' => $partID]);
                            if (null === $myClientsHasCoursDate) {
                                if (isset($post['dateparticipant'][$laDate]) && in_array($key, $post['dateparticipant'][$laDate])) {
                                    $myClientsHasCoursDate = new ClientsHasCoursDate;
                                    $myClientsHasCoursDate->fk_cours_date = $coursDate->cours_date_id;
                                    $myClientsHasCoursDate->fk_personne = $partID;
                                    $myClientsHasCoursDate->is_present = 1;
                                    $myClientsHasCoursDate->save(false);
                                }
                            } else {
                                if (!isset($post['dateparticipant']) || !isset($post['dateparticipant'][$laDate]) || !in_array($key, $post['dateparticipant'][$laDate])) {
                                    $myClientsHasCoursDate->delete();
                                }
                                // si plus aucune inscription pour le client, on supprime aussi le clientHasCours
                                if (!isset($post['dateparticipant'])) {
                                    ClientsHasCours::deleteAll(['fk_cours' => $coursDate->fk_cours, 'fk_personne' => $partID]);
                                }
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
        
        $modelParticipants = Personnes::find()->where(['<>', 'fk_type', Yii::$app->params['typeEncadrantActif']])->andWhere(['not in', 'personne_id', $dejaParticipants])->orderBy('nom, prenom')->all();
        foreach ($modelParticipants as $participant) {
            $dataParticipants[$participant->fkStatut->nom][$participant->personne_id] = $participant->NomPrenom;
        }
        
        // on ajoute au tableau le nouveau participant choisi
        if (isset($newParticipant)) {
            $arrayParticipants[$newParticipant->fk_personne] = $newParticipant;
        }
        
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
                $withNotification = (isset($post['withNotification']) && $post['withNotification']);
                $arrayBefore = [];
                // préparation du tableau de comparaison
                foreach ($model->coursDates as $coursDate) {
                    $dateCours = date('Ymd', strtotime($coursDate->date));
                    foreach ($coursDate->coursHasMoniteurs as $moniteur) {
                        $arrayBefore[$dateCours][$moniteur->fk_cours_date.'|'.$moniteur->fk_moniteur] = true;
                        $arrayClone[$moniteur->fk_cours_date.'|'.$moniteur->fk_moniteur] = clone($moniteur);
                    }
                }
            
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    // on supprime tous les moniteurs pour les cours en question
                    foreach ($model->coursDates as $coursDate) {
                        CoursHasMoniteurs::deleteAll('fk_cours_date = :cours_date_id', ['cours_date_id' => $coursDate->cours_date_id]);
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

                            if (isset($arrayClone[$key])) {
                                $addMoniteur->fk_bareme = $arrayClone[$key]->fk_bareme;
                            }

                            if (!$addMoniteur->save()) {
                                throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des moniteur(s).'));
                            }
                            if ($withNotification) {
                                $dates[$addMoniteur->fk_cours_date]['date'] = $addMoniteur->fkCoursDate->date;
                                $dates[$addMoniteur->fk_cours_date]['heure'] = substr($addMoniteur->fkCoursDate->heure_debut, 0, 5);
                                $dates[$addMoniteur->fk_cours_date]['moniteurs'][] = $addMoniteur->fkMoniteur->prenom . ' ' . $addMoniteur->fkMoniteur->nom;
                                $dates[$addMoniteur->fk_cours_date]['remarque'] = $addMoniteur->fkCoursDate->remarque;
                            }
                        }
                    }
                    if ($withNotification) {
                        $this->sendNotifications($dates, $arrayBefore, $post['datemoniteur']);
                    }

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
        $arrayMoniteurs = [];
        $dejaMoniteurs = [];
        foreach ($model->coursDates as $coursDate) {
            $arrayData[$coursDate->cours_date_id]['model'] = $coursDate;
            foreach ($coursDate->coursHasMoniteurs as $moniteur) {
                $arrayMoniteurs[$moniteur->fk_moniteur] = $moniteur;
                $dejaMoniteurs[] = $moniteur->fk_moniteur;
                $arrayData[$coursDate->cours_date_id]['moniteurs'][$moniteur->fk_moniteur] = $moniteur;
            }
        }
        
        $modelMoniteurs = Personnes::find()->where(['fk_type' => Yii::$app->params['typeEncadrantActif']])->andWhere(['not in', 'personne_id', $dejaMoniteurs])->orderBy('nom, prenom')->all();
        foreach ($modelMoniteurs as $moniteur) {
            $dataMoniteurs[$moniteur->fkStatut->nom][$moniteur->personne_id] = $moniteur->NomPrenom . ' ' . $moniteur->getLetterBaremeFromDate(date('Y-m-d'));
        }
        
        // on ajoute au tableau le nouveau moniteur choisi
        if (isset($newMoniteur)) {
            $arrayMoniteurs[$newMoniteur->fk_moniteur] = $newMoniteur;
        }
        
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
                        if (!$myClientCours->save()) {
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
        $participants = Personnes::find()->distinct()->joinWith('clientsHasCours')->where(['IN', 'fk_cours', $model->cours_id])->orderBy('clients_has_cours.fk_statut ASC')->all();
        
        // on cherche à définir le statut actuelle des participants
        foreach($participants as $part) {
            $toGroup = ClientsHasCours::findOne(['fk_personne' => $part->personne_id, 'fk_cours' => $model->cours_id]);
            $part->statutPart = $toGroup->fkStatut->nom;
            
            ${'allParticipants'.$toGroup->fk_statut}[] = $part;
        }
        
        $allParticipants = [];
        $lesStatuts = Parametres::find()->where(['class_key' => 9])->orderBy('tri')->all();
        foreach ($lesStatuts as $statut) {
            if (isset(${'allParticipants'.$statut->parametre_id})) {
                $allParticipants = array_merge($allParticipants, ${'allParticipants'.$statut->parametre_id});
            }
        }

        // get your HTML raw content without any layouts or scripts
        $content = $this->renderPartial('_presence', [
            'model' => $model,
            'participants' => $allParticipants,
            'decoupage' => $decoupage,
        ]);

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
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
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
    
    /**
     * Fonction API qui permet de retrouver la liste des cours à publier sur le
     * site internet
     * @return json La liste des cours
     */
    public function actionGetcoursjson() {
        $params = [
            'fk_statut' => Yii::$app->params['coursActif'],
            'is_publie' => 1,
        ];
        $arrayModelCours = Cours::findAll($params);
        
        if (empty($arrayModelCours)) {
            $data = ["Aucune donnée trouvée"];
        } else {
            foreach ($arrayModelCours as $c) {
                // quelle langue ?
                $language = (isset(Yii::$app->params['interface_language_label'][$c->fk_langue])) ? Yii::$app->params['interface_language_label'][$c->fk_langue] : 'fr-CH';
                $jours = [];
                foreach ($c->fkJours as $j) {
                    $jours[] = Yii::t('app', $j->nom, [], $language);
                }
                $categories = [];
                foreach ($c->fkCategories as $cat) {
                    $categories[] = Yii::t('app', $cat->nom, [], $language);
                }
                $dates = [];
                $datesLieux = [];
                $premierJourSession = '';
                $isUnique = Yii::$app->params['coursUnique'] == $c->fk_type;
                // pour les cours anniversaires (type 304 unique, on les mets en type sur demande 15)
                // cela permet de corriger l'affichage sur le site internet
                if ($isUnique) {
                    $param = Parametres::findOne(Yii::$app->params['coursPonctuel']);
                    $typeNom = $param->nom;
                } else {
                    $typeNom = $c->fkType->nom;
                }

                foreach ($c->coursDates as $d) {
                    if (!$isUnique || ($isUnique && empty($d->clientsHasCoursDate))) {
                        if (empty($premierJourSession)) {
                            $premierJourSession = date('Y-m-d', strtotime($d->date));
                        }
                        $dates[] = date('r', strtotime($d->date.' '.$d->heure_debut));
                        $datesLieux[] = ['ident' =>  $d->cours_date_id, 'date' => date('r', strtotime($d->date.' '.$d->heure_debut)), 'lieu' => $d->fkLieu->nom];
                    }
                }
                $data[] = [
                    'id' => $c->cours_id,
                    'nom' => $c->fkNom->nom,
                    'nom_id' => $c->fk_nom,
                    'niveau' => Yii::t('app', $c->fkNiveau->nom, [], $language),
                    'semestre' => (isset($c->fkSemestre)) ? $c->fkSemestre->nom : '',
                    'saison' => (isset($c->fkSaison)) ? $c->fkSaison->nom : '',
                    'session' => $c->session,
                    'salle' => Yii::t('app', $c->fkSalle->nom, [], $language),
                    'jours_semaine' => $jours,
                    'type' => $typeNom,
                    'annee' => $c->annee,
                    'duree' => $c->duree,
                    'prix' => $c->prix,
                    'participant_max' => $c->participant_max,
                    'nombre_inscrit' => $c->getNombreClientsInscritsForExport(),
                    'tranche_age' => Yii::t('app', $c->fkAge->nom, [], $language),
                    'materiel_compris' => ($c->is_materiel_compris) ? Yii::t('app', 'Oui', [], $language) : Yii::t('app', 'Non', [], $language),
                    'entree_compris' => ($c->is_entree_compris) ? Yii::t('app', 'Oui', [], $language) : Yii::t('app', 'Non', [], $language),
                    'infos_tarifs' => $c->offre_speciale,
                    'premier_jour_session' => $premierJourSession,
                    'toutes_les_dates' => $dates,
                    'toutes_les_dates_avec_lieu' => $datesLieux,
                    'extrait' => $c->extrait,
                    'description' => $c->description,
                    'offre_speciale' => $c->offre_speciale,
                    'categories' => $categories,
                    'image_web' => ($c->image_web != '') ? Url::home(true).'/../../_files/images/'.$c->image_web : '',
                    'langue' => $c->fkLangue->nom,
                    'tri' => $c->fkNom->tri,
                    'tri_mise_en_avant' => $c->tri_internet,
                ];
            }
        }
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        header('Content-Type: application/json; charset=utf-8');
        return $data;
    }
    
    /**
     * 
     * @param array $arrayMoniteursBefore
     * @param array $arrayMoniteursAfter
     * @return void
     */
    private function sendNotifications($dates, $arrayMoniteursBefore, $arrayMoniteursAfter) {
        // suppression
        $arraySupprime = $this->checkDiffMulti($arrayMoniteursBefore, $arrayMoniteursAfter);
        // ajout
        $arrayAjoute = $this->checkDiffMulti($arrayMoniteursAfter, $arrayMoniteursBefore);

        // on gère les suppressions
        foreach ($arraySupprime as $parDate) {
            foreach ($parDate as $key => $v) {
                $ids = explode('|', $key);
                if (isset($dates[$ids[0]])) {
                    $modelCoursDate = CoursDate::findOne($ids[0]);
                    $modelMoniteur = Personnes::findOne($ids[1]);

                    // on envoi un email au moniteur
                    if (!empty($modelMoniteur->email)) {
                        $contenu = $this->generateMoniteurEmail($modelCoursDate, $dates[$ids[0]]['moniteurs'], 'delete');
                        $this->actionEmail($contenu, [$modelMoniteur->email]);
                    }
                }
            }
        }
        
        // on gère les ajouts
        foreach ($arrayAjoute as $parDate) {
            foreach ($parDate as $key => $v) {
                $ids = explode('|', $key);
                if (isset($dates[$ids[0]])) {
                    $modelCoursDate = CoursDate::findOne($ids[0]);
                    $modelMoniteur = Personnes::findOne($ids[1]);

                    // on envoi un email au moniteur
                    if (!empty($modelMoniteur->email)) {
                        $contenu = $this->generateMoniteurEmail($modelCoursDate, $dates[$ids[0]]['moniteurs'], 'create');
                        $this->actionEmail($contenu, [$modelMoniteur->email]);
                    }
                }
            }
        }
    }
}
