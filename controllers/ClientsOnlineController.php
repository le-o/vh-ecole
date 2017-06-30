<?php

namespace app\controllers;

use Yii;
use app\models\ClientsOnline;
use app\models\ClientsOnlineSearch;
use app\models\Cours;
use app\models\Model;
use app\models\Parametres;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Exception;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ClientsOnlineController implements the CRUD actions for ClientsOnline model.
 */
class ClientsOnlineController extends Controller
{
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
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return (Yii::$app->user->identity->id < 1000) ? true : false;
                        }
                    ],
                ],
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
//        $searchModel->is_actif = true;
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
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new ClientsOnline model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $this->layout = "main_1";
        
        $model = new ClientsOnline();
        $modelsClient = [new ClientsOnline];

        if ($model->load(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            $model->fk_cours = $post['list_cours'];
            $model->is_actif = true;
            
            // gestion des options supp
            if (isset($post['offre_annuelle']))
                $model->informations .= '
                    + '.Yii::t('app', 'Je souhaite profiter de l’offre annuelle (inscription aux semestres 1 et 2 avec abonnement annuel offert)');
            if (isset($post['pmt_tranche']))
                $model->informations .= '
                    + '.Yii::t('app', 'Je souhaite étaler le paiement du cours en plusieurs tranches (10.- frais administratifs)');

            $modelsClient = Model::createMultiple(ClientsOnline::classname(), [], 'client_online_id');
            Model::loadMultiple($modelsClient, $post);
            
            if ($model->validate()) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save()) {
                        throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde de la personne.'));
                    }
                    
                    // tout est ok pour le client principal, on sauve les clients liés
                    foreach ($modelsClient as $client) {
                        if ($client->nom != '' && $client->prenom != '') {
                            $client->fk_cours = $model->fk_cours;
                            $client->fk_parent = $model->client_online_id;
                            $client->adresse = $model->adresse;
                            $client->npa = $model->npa;
                            $client->localite = $model->localite;
                            $client->telephone = $model->telephone;
                            $client->email = $model->email;
                            $client->is_actif = true;

                            if (!$client->save()) {
                                throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde du client lié.'));
                            }
                        }
                    }

                    $transaction->commit();

                    $contenu = \app\models\Parametres::findOne(Yii::$app->params['texteEmailInscriptionOnline']);
                    SiteController::actionEmail($contenu, [$model->email], true);

                    return $this->render('confirmation');
                } catch (\Exception $e) {
                    $alerte = $e->getMessage();
                    $transaction->rollBack();
                }
            }
        }
        
        $modelCours = Cours::find()->distinct()->JoinWith(['fkNom'])->orderBy('nom, tri')->all();
        foreach ($modelCours as $cours) {
            $dataCours[$cours->fkNiveau->nom][$cours->fkNom->parametre_id] = $cours->fkNom->nom;
        }
        
        return $this->render('create', [
            'model' => $model,
            'modelsClient' => $modelsClient,
            'dataCours' => $dataCours,
            'selectedCours' => [],
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
                'selectedCours' => [$model->fk_cours],
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
        
        $p = new \app\models\Personnes;
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
        $p->informations = Yii::t('app', 'Intéressé par le cours').' '.$model->fkParametre->nom;
        $p->informations .= "\r\n".Yii::t('app', 'Date d\'inscription').': '.$model->date_inscription;
        if ($model->informations != '') $p->informations .= "\r\n\r\n".$model->informations;
        
        $clients = ClientsOnline::findAll(['fk_parent' => $model->client_online_id]);
        
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if ($p->save()) {
                foreach ($clients as $client) {
                    $c = new \app\models\Personnes;
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
                    $c->save();
                    $client->is_actif = false;
                    $client->save(false);

                    $i = new \app\models\PersonnesHasInterlocuteurs;
                    $i->fk_personne = $c->personne_id;
                    $i->fk_interlocuteur = $p->personne_id;
                    $i->save();
                }
                $model->is_actif = false;
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
}
