<?php

namespace app\controllers;

use app\models\CoursDate;
use app\models\Personnes;
use app\models\PersonnesHasInterlocuteurs;
use Yii;
use app\models\ClientsOnline;
use app\models\ClientsOnlineSearch;
use app\models\Cours;
use app\models\Model;
use app\models\Parametres;
use yii\web\NotFoundHttpException;
use yii\web\Exception;
use yii\filters\VerbFilter;

/**
 * ClientsOnlineController implements the CRUD actions for ClientsOnline model.
 */
class ClientsOnlineController extends CommonController
{
    
    public $freeAccessActions = ['create'];
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'ghost-access'=> [
                'class' => 'webvimark\modules\UserManagement\components\GhostAccessControl',
            ],
        ];
    }

    /**
     * Lists all ClientsOnline models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ClientsOnlineSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ClientsOnline model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        if ($model->is_actif) {
            $doublons = Personnes::find()
                    ->where(['nom' => $model->nom, 'prenom' => $model->prenom])->all();
        }
        
        return $this->render('view', [
            'model' => $model,
            'doublon' => isset($doublons[0]) ? $doublons[0] : null,
        ]);
    }

    /**
     * Creates a new ClientsOnline model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($cours_id = null, $lang_interface = 'fr-CH', $goodlooking = false)
    {
        $this->layout = (false == $goodlooking) ? "main_1" : "main_1_logo";
        Yii::$app->language = $lang_interface;

        $model = new ClientsOnline();
        $modelsClient = [new ClientsOnline];
        if ($cours_id !== null && $cours_id !== '') {
            $modelCours = Cours::findOne($cours_id);
        }
        $alerte = '';

        if ($model->load(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            $model->is_actif = 1;
            $model->fk_cours_nom = (isset($modelCours)) ? $modelCours->fk_nom : $model->fk_cours;
            
            // gestion des options supp
            if (isset($post['offre_supp']) && in_array($model->fk_cours_nom, Yii::$app->params['nomsCoursEnfant'])) {
                if ($post['offre_supp'] == 'cours_essai') {
                    $model->informations .= '
                        + '.Yii::t('app', 'Je souhaite inscrire mon enfant pour 2 cours à l’essai (je déciderai au terme des 2 cours si j’inscris mon enfant pour un semestre ou à l’année)');
                } elseif ($post['offre_supp'] == 'semestre') {
                    $model->informations .= '
                        + '.Yii::t('app', 'Je souhaite inscrire mon enfant pour un semestre uniquement');
                } elseif ($post['offre_supp'] == 'offre_annuelle') {
                    $model->informations .= '
                        + '.Yii::t('app', 'Je souhaite profiter de l’offre annuelle (inscription aux semestres 1 et 2 avec abonnement annuel offert)');
                }
            }
            if (isset($post['pmt_tranche'])) {
                $model->informations .= '
                    + '.Yii::t('app', 'Je souhaite étaler le paiement du cours en plusieurs tranches');
            }

            $clientAuto = false;
            if (isset($modelCours)) {
                $model->fk_cours = $cours_id;
                $model->informations .= '
                    INFO: '.Yii::t('app', 'Le client souhaite être inscrit au cours suivant').': '.
                    $modelCours->cours_id.'-'.$modelCours->fkNom->nom.' '.$modelCours->fkNiveau->nom;
                $model->informations .= (isset($modelCours->fkSemestre)) ? ' - ' . $modelCours->fkSemestre->nom : ' - ';
                $model->informations .= $modelCours->fkSaison->nom.' '.$modelCours->session;
                $model->informations .= '<br />'.Yii::t('app', 'Salle concernée') . ': ' . $modelCours->fkSalle->nom;
                if (Yii::$app->params['coursRegulie'] == $modelCours->fk_type) {
                    $clientAuto = true;
                }
            } else {
                $model->fk_cours = null;
            }

            $modelsClient = Model::createMultiple(ClientsOnline::classname(), [], 'client_online_id');
            Model::loadMultiple($modelsClient, $post);
            
            if ($model->validate()) {
                $clientDirect = [];
                if (true == $clientAuto) {
                    $clientDirect[] = $this->setPersonneAttribute($model);
                    $model->is_actif = 0;
                }
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save()) {
                        throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde de la personne.'));
                    }
                    
                    // tout est ok pour le client principal, on sauve les clients liés
                    foreach ($modelsClient as $client) {
                        if ($client->nom != '' && $client->prenom != '') {
                            $client->fk_cours_nom = $model->fk_cours_nom;
                            $client->fk_cours = $model->fk_cours;
                            $client->fk_parent = $model->client_online_id;
                            $client->adresse = $model->adresse;
                            $client->npa = $model->npa;
                            $client->localite = $model->localite;
                            $client->telephone = $model->telephone;
                            $client->email = $model->email;
                            $client->is_actif = 1;

                            if (true == $clientAuto) {
                                $clientDirect[] = $this->setPersonneAttribute($client);
                                $client->is_actif = 0;
                            }

                            if (!$client->save()) {
                                throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde du client lié.'));
                            }
                        }
                    }
                    if (true == $clientAuto) {
                        $inscritCours = (1 < count($clientDirect)) ? false : true;
                        $parentID = null;
                        $modelDate = CoursDate::find()
                            ->where(['=', 'fk_cours', $modelCours->cours_id])
                            ->andWhere(['>=', 'date', date('Y-m-d')])
                            ->all();

                        foreach ($clientDirect as $cd) {
                            // si la personne existe déjà, on ne fait pas de doublon
                            $isPersonne = Personnes::find()->where(['nom' => $cd->nom, 'prenom' => $cd->prenom])->one();
                            if (null == $isPersonne) {
                                $cd->save();
                                $existeID = $cd->personne_id;
                            } else {
                                $existeID = $isPersonne->personne_id;
                            }

                            // si on a un interlocuteur, on ne sauve pas l'inscription au cours, mais juste la personne
                            if (false == $inscritCours) {
                                $inscritCours = true;
                                $parentID = $existeID;
                                continue;
                            }
                            // si on a un interlocuteur, on sauve le lien entre les personnes
                            if (!is_null($parentID)) {
                                $hasInterloc = PersonnesHasInterlocuteurs::find()->where(['fk_personne' => $existeID, 'fk_interlocuteur' => $parentID])->one();
                                if (null == $hasInterloc) {
                                    $i = new PersonnesHasInterlocuteurs;
                                    $i->fk_personne = $existeID;
                                    $i->fk_interlocuteur = $parentID;
                                    $i->save();
                                }
                            }
                            // on sauve l'inscription au cours
                            if (!empty($modelDate)) {
                                $this->addClientToCours($modelDate, $existeID, $modelCours->cours_id);
                                $sendEmailTo[] = $existeID;
                            }
                        }
                    }
                    $transaction->commit();

                    // on traite le mail après le commit, comme cela si l'envoi de l'email plante, on a quand même
                    // enregistré les données dans la base
                    if (true == $clientAuto) {
                        $emailBrut = \app\models\Parametres::findOne(Yii::$app->params['texteEmailConfirmationOnline'][Yii::$app->language]);
                        $contenu['nom'] = $emailBrut->nom;
                        $contenu['valeur'] = $emailBrut->valeur;
                        $contenu['keyForMail'] = $modelCours->cours_id;
                        foreach ($sendEmailTo as $personneID) {
                            $contenu['personne_id'] = $personneID;
                            $this->actionEmail($contenu, [$model->email], true);
                        }
                    } else {
                        $contenu = \app\models\Parametres::findOne(Yii::$app->params['texteEmailInscriptionOnline'][Yii::$app->language]);
                        $this->actionEmail($contenu, [$model->email], true);
                    }

                    return $this->render('confirmation');
                } catch (\Exception $e) {
                    $alerte = $e->getMessage();
                    $transaction->rollBack();
                }
            } else {
                $alerte = Yii::t('app', 'Une erreur est survenue lors de l\'enregistrement.');
            }
        }
        
        if (isset($modelCours)) {
            $dataCours[$modelCours->cours_id] = $modelCours->fkNom->nom . ' ' . $modelCours->fkNiveau->nom . ' ' . $modelCours->session . ' ' . $modelCours->fkSalle->nom;
            $dataCours[$modelCours->cours_id] .= (isset($modelCours->fkSemestre)) ? ' - '.$modelCours->fkSemestre->nom : '';
            $model->fk_cours = $cours_id;
            $selectedCours = [$cours_id];
        } else {
            $query = Cours::find()->distinct()->JoinWith(['fkNom'])->orderBy('parametres.tri')->where(['fk_statut' => Yii::$app->params['coursActif'], 'is_publie' => true]);
            $query->andWhere(['OR', 'date_fin_validite IS NULL', ['>=', 'date_fin_validite', 'today()']]);
            $modelCours = $query->all();
            foreach ($modelCours as $cours) {
                $dataCours[$cours->fkNom->parametre_id] = $cours->fkNom->nom;
            }
            $selectedCours = [];
        }
        
        return $this->render('create', [
            'model' => $model,
            'modelsClient' => $modelsClient,
            'dataCours' => $dataCours,
            'selectedCours' => $selectedCours,
            'params' => new Parametres,
            'alerte' => $alerte,
        ]);
    }

    /**
     * Updates an existing ClientsOnline model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $modelsClient = [new ClientsOnline];
        
        $modelCours = Cours::find()->distinct()->JoinWith(['fkNom'])->orderBy('nom, tri')->all();
        foreach ($modelCours as $cours) {
            $dataCours[$cours->fkNiveau->nom][$cours->fkNom->parametre_id] = $cours->fkNom->nom;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->client_online_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'modelsClient' => $modelsClient,
                'dataCours' => $dataCours,
                'selectedCours' => [$model->fk_cours_nom],
                'params' => new Parametres,
            ]);
        }
    }

    /**
     * Deletes an existing ClientsOnline model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }
    
    /**
     * Transform an existing ClientsOnline to a real Client (Personnes model).
     * If change is successful, the browser will display a message and reload index view.
     * @param integer $id
     * @return mixed
     */
    public function actionPushclient($id)
    {
        $model = $this->findModel($id);

        $p = $this->setPersonneAttribute($model);

        $clients = ClientsOnline::findAll(['fk_parent' => $model->client_online_id]);
        
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if ($p->save()) {
                foreach ($clients as $client) {
                    $c = new Personnes;
                    $c->fk_statut = Yii::$app->params['persStatutStandby'];
                    $c->fk_type = Yii::$app->params['typeADefinir'];
                    $c->nom = $client->nom;
                    $c->prenom = $client->prenom;
                    $c->adresse1 = $client->adresse;
                    $c->npa = $client->npa;
                    $c->localite = $client->localite;
                    $c->telephone = $client->telephone;
                    $c->email = $client->email;
                    $c->date_naissance = $client->date_naissance;
                    $c->informations = $p->informations;
                    $c->fk_salle_admin = $p->fk_salle_admin;
                    $c->save();
                    $client->is_actif = 0;
                    $client->save(false);

                    $i = new \app\models\PersonnesHasInterlocuteurs;
                    $i->fk_personne = $c->personne_id;
                    $i->fk_interlocuteur = $p->personne_id;
                    $i->save();
                }
                $model->is_actif = 0;
                $model->save(false);

                $transaction->commit();
                return $this->redirect(['index']);
            } else {
                throw new \Exception(Yii::t('app', 'Problème lors de la transformation de la personne.'));
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            exit('error');
        }
    }
    
    public function actionFusionclient($idClientOnline, $idPersonne) {
        $clientOnline = $this->findModel($idClientOnline);
        $personne = Personnes::findOne($idPersonne);
        
        $personne->fk_statut = Yii::$app->params['persStatutStandby'];
        $personne->adresse1 = $clientOnline->adresse;
        $personne->npa = $clientOnline->npa;
        $personne->localite = $clientOnline->localite;
        $personne->telephone = $clientOnline->telephone;
        $personne->email = $clientOnline->email;
        $personne->date_naissance = $clientOnline->date_naissance;
        $personne->informations .= "\r\n".Yii::t('app', 'Intéressé par le cours').' '.$clientOnline->fkCoursNom->nom;
        $personne->informations .= "\r\n".Yii::t('app', 'Date d\'inscription').': '.$clientOnline->date_inscription;
        if ($clientOnline->informations != '') $personne->informations .= "\r\n\r\n".$clientOnline->informations;
        $personne->fk_salle_admin = ($clientOnline->fkCours) ? $clientOnline->fkCours->fk_salle : Yii::$app->params['salleAdmin'][$clientOnline->fkCoursNom->fk_langue];
        
        $clientsLies = ClientsOnline::findAll(['fk_parent' => $clientOnline->client_online_id]);
        
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if ($personne->save()) {
                $clientsExistes = \app\models\PersonnesHasInterlocuteurs::findAll(['fk_interlocuteur' => $personne->personne_id]);
                $enfants = [];
                foreach ($clientsExistes as $existe) {
                    $enfants[$existe->fkPersonne->nom.$existe->fkPersonne->prenom.$existe->fkPersonne->date_naissance] = ['nom' => $existe->fkPersonne->nom, 'prenom' => $existe->fkPersonne->prenom, 'date_naissance' => $existe->fkPersonne->date_naissance];
                }
                
                foreach ($clientsLies as $client) {
                    $key = $client->nom.$client->prenom.$client->date_naissance;
                    if (!array_key_exists($key, $enfants)) {
                        $c = new Personnes;
                        $c->fk_statut = Yii::$app->params['persStatutStandby'];
                        $c->fk_type = Yii::$app->params['typeADefinir'];
                        $c->nom = $client->nom;
                        $c->prenom = $client->prenom;
                        $c->adresse1 = $client->adresse;
                        $c->npa = $client->npa;
                        $c->localite = $client->localite;
                        $c->telephone = $client->telephone;
                        $c->email = $client->email;
                        $c->date_naissance = $client->date_naissance;
                        $c->informations = $personne->informations;
                        $c->save();

                        $i = new \app\models\PersonnesHasInterlocuteurs;
                        $i->fk_personne = $c->personne_id;
                        $i->fk_interlocuteur = $personne->personne_id;
                        $i->save();
                    }
                    
                    $client->is_actif = 0;
                    $client->save(false);
                }
                $clientOnline->is_actif = 0;
                $clientOnline->save(false);

                $transaction->commit();
                return $this->redirect(['index']);
            } else {
                throw new \Exception(Yii::t('app', 'Problème lors de la transformation de la personne.'));
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            exit('error lors de la fusion des personnes :(');
        }
    }

    /**
     * Finds the ClientsOnline model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ClientsOnline the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ClientsOnline::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @param ClientsOnline $model
     * @return Personnes
     */
    private function setPersonneAttribute(ClientsOnline $model)
    {
        $p = new Personnes;
        $p->fk_statut = Yii::$app->params['persStatutStandby'];
        $p->fk_type = Yii::$app->params['typeADefinir'];
        $p->nom = $model->nom;
        $p->prenom = ($model->prenom != '') ? $model->prenom : 'non renseigné';
        $p->adresse1 = $model->adresse;
        $p->npa = $model->npa;
        $p->localite = $model->localite;
        $p->telephone = $model->telephone;
        $p->email = $model->email;
        $p->date_naissance = $model->date_naissance;
        $p->informations = Yii::t('app', 'Intéressé par le cours') . ' ' . $model->fkCoursNom->nom;
        $p->informations .= "\r\n" . Yii::t('app', 'Date d\'inscription') . ': ' . $model->date_inscription;
        if ($model->informations != '') $p->informations .= "\r\n\r\n" . $model->informations;
        $p->fk_salle_admin = ($model->fkCours) ? $model->fkCours->fk_salle : Yii::$app->params['salleAdmin'][$model->fkCoursNom->fk_langue];
        return $p;
    }
}
