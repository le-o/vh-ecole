<?php

namespace app\controllers;

use Yii;
use app\models\Personnes;
use app\models\PersonnesSearch;
use app\models\PersonnesHasInterlocuteurs;
use app\models\Parametres;
use app\models\Cours;
use app\models\CoursDate;
use app\models\ClientsHasCoursDate;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\data\ArrayDataProvider;
use yii\data\ActiveDataProvider;
use kartik\mpdf\Pdf;

/**
 * PersonnesController implements the CRUD actions for Personnes model.
 */
class PersonnesController extends Controller
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
                        'actions' => ['index', 'view', 'setemail'],
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return (Yii::$app->user->identity->id < 1100) ? true : false;
                        }
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
     * Lists all Personnes models.
     * @return mixed
     */
    public function actionIndex()
    {
        $alerte = [];
        $searchModel = new PersonnesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProviderAll = $searchModel->search(Yii::$app->request->queryParams, false);
        
        $listeEmails = [];
        foreach ($dataProviderAll->models as $myPersonne) {
            if (strpos($myPersonne->email, '@') !== false) {
                $listeEmails[$myPersonne->email] = $myPersonne->email;
            }
            
            foreach ($myPersonne->personneHasInterlocuteurs as $pi) {
                $listeEmails[$pi->fkInterlocuteur->email] = $pi->fkInterlocuteur->email;
            }
        }

        if (!empty(Yii::$app->request->post())) {
            $mail = Yii::$app->request->post();
            SiteController::actionEmail($mail['Parametres'], explode(', ', $mail['checkedEmails']));

            $alerte['class'] = 'info';
            $alerte['message'] = Yii::t('app', 'Email envoyé à toutes les personnes sélectionnées');
        }
        
        $parametre = new Parametres();
        $typeStatut = $parametre->optsStatut();
        $typeFilter = $parametre->optsType();
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();

        return $this->render('index', [
            'alerte' => $alerte,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'typeStatut' => $typeStatut,
            'typeFilter' => $typeFilter,
            'parametre' => $parametre,
            'emails' => $emails,
            'listeEmails' => $listeEmails,
        ]);
    }

    /**
     * Lists all Personnes models.
     * @return mixed
     */
    public function actionMoniteurs()
    {
        $searchModel = new PersonnesSearch();
        $searchModel->fk_type = Yii::$app->params['typeEncadrant'];
        $dataProvider = $searchModel->searchMoniteurs(Yii::$app->request->queryParams, false);
        
        $searchParams = Yii::$app->request->queryParams;
        $searchParCours = (isset($searchParams['list_cours']) && $searchParams['list_cours'] !== '') ? true : false;
        if (!isset($searchParams['from_date'])) $searchParams['from_date'] = date('01.01.Y');
        if (!isset($searchParams['to_date'])) $searchParams['to_date'] = date('31.12.Y');
        $searchFrom = (isset($searchParams['from_date']) && $searchParams['from_date'] !== '') ? date('Y-m-d', strtotime($searchParams['from_date'])) : '1970-01-01';
        $searchTo = (isset($searchParams['to_date']) && $searchParams['to_date'] !== '') ? date('Y-m-d', strtotime($searchParams['to_date'])) : '9999-12-31';
        
        $dataMoniteurs = [];
        $heuresTotal = 0;
        foreach ($dataProvider->models as $moniteur) {
            $heures = 0;
            foreach ($moniteur->moniteurHasCoursDate as $mcd) {
                $coursDate = CoursDate::findOne($mcd->fk_cours_date);
                
                $dateRef = date('Y-m-d', strtotime($coursDate->date));
                if ($dateRef >= $searchFrom && $dateRef <= $searchTo) {
                    if ($searchParCours) {
                        if ($coursDate->fkCours->fk_nom == $searchParams['list_cours']) $heures += $coursDate->duree;
                    } else $heures += $coursDate->duree;
                }
            }
            if (!$searchParCours || ($searchParCours && $heures !== 0)) {
                $dataMoniteurs[$moniteur->personne_id]['statut'] = $moniteur->fkStatut->nom;
                $dataMoniteurs[$moniteur->personne_id]['societe'] = $moniteur->societe;
                $dataMoniteurs[$moniteur->personne_id]['nom'] = $moniteur->nom;
                $dataMoniteurs[$moniteur->personne_id]['prenom'] = $moniteur->prenom;
                $dataMoniteurs[$moniteur->personne_id]['localite'] = $moniteur->localite;
                $dataMoniteurs[$moniteur->personne_id]['email'] = $moniteur->email;
                $dataMoniteurs[$moniteur->personne_id]['telephone'] = $moniteur->telephone;
                $dataMoniteurs[$moniteur->personne_id]['heures'] = number_format($heures, 2, '.', '\'');
                $heuresTotal += $heures;
            }
        }
        $moniteursProvider = new ArrayDataProvider([
            'allModels' => $dataMoniteurs,
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
        
        $modelParams = new Parametres();
        $dataCours = $modelParams->optsNomCours();
        $selectedCours = (isset($searchParams['list_cours'])) ? $searchParams['list_cours'] : '';
        
        $fromData = serialize(['selectedCours' => $selectedCours, 'searchFrom' => $searchFrom, 'searchTo' => $searchTo]);

        return $this->render('moniteurs', [
            'searchModel' => $searchModel,
            'searchFrom' => ($searchFrom == '1970-01-01') ? '' : date('d.m.Y', strtotime($searchFrom)),
            'searchTo' => ($searchTo == '9999-12-31') ? '' : date('d.m.Y', strtotime($searchTo)),
            'selectedCours' => $selectedCours,
            'dataCours' => $dataCours,
            'moniteursProvider' => $moniteursProvider,
            'heuresTotal' => number_format($heuresTotal, 2, '.', '\''),
            'fromData' => $fromData,
        ]);
    }
    
    /**
     * Displays a list of Cours model.
     * @param integer $id
     * @param mixed $fromData
     * @return mixed
     */
    public function actionViewmoniteur($id, $fromData, $print = false)
    {
        $model = $this->findModel($id);
        
        $fromData = unserialize($fromData);
        
        $coursDateDataProvider = [];
        $listeCoursDate = [];
        foreach ($model->moniteurHasCoursDate as $mcd) {
            if ($fromData['selectedCours'] != '' && $mcd->fkCoursDate->fkCours->fk_nom == $fromData['selectedCours']) {
                $listeCoursDate[] = $mcd->fk_cours_date;
            } elseif ($fromData['selectedCours'] == '') {
                $listeCoursDate[] = $mcd->fk_cours_date;
            }
        }
        $coursDate = CoursDate::find()
            ->where(['in', 'cours_date_id', $listeCoursDate])
            ->andWhere(['between', 'date', $fromData['searchFrom'], $fromData['searchTo']])
            ->orderBy(['date' => SORT_DESC]);
        $coursDateDataProvider = new ActiveDataProvider([
            'query' => $coursDate,
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
        
        if (!$print) {
            return $this->render('viewmoniteur', [
                'model' => $model,
                'coursDateDataProvider' => $coursDateDataProvider,
                'fromData' => $fromData,
            ]);
        }
        
        $content = $this->renderPartial('viewmoniteur', [
            'model' => $model,
            'coursDateDataProvider' => $coursDateDataProvider,
            'fromData' => $fromData,
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
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => '
                table { width:100%; border-collapse:collapse; }
                table, th, td { border:1px solid slategrey; }
                tr.entete td, td.entete { background-color:lightgrey; font-weight:bold; text-align:center; }
                td.date { width:35px; }
                td.num { width:25px; }
                .kv-heading-1{font-size:18px}
                .hide-print { display: none; }
            ',
            // set mPDF properties on the fly
//            'options' => ['title' => Yii::t('app', 'Liste des présences')],
            // call mPDF methods on the fly
            'methods' => [
                'SetFooter'=>['{PAGENO}'],
            ]
        ]);

        // return the pdf output as per the destination setting
        return $pdf->render();
    }

    /**
     * Displays a single Personnes model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $alerte = [];
        $model = $this->findModel($id);
        
        if (!empty(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            
            if (!empty($post['new_cours'])) {
                // soit on ajoute un cours
                $newCours = explode('|', $post['new_cours']);
                if ($newCours[1] == Yii::$app->params['coursPlanifie']) {
                    $modelDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $newCours[0]])
                        ->andWhere(['>=', 'date', date('Y-m-d')])
                        ->all();
                    if (empty($modelDate)) {
                        $alerte['class'] = 'warning';
                        $alerte['message'] = Yii::t('app', 'Inscription impossible - aucune date dans le futur');
                    } else {
                        foreach ($modelDate as $date) {
                            $modelClientsHasCoursDate = new ClientsHasCoursDate();
                            $modelClientsHasCoursDate->fk_cours_date = $date->cours_date_id;
                            $modelClientsHasCoursDate->fk_personne = $id;
                            $modelClientsHasCoursDate->is_present = true;
                            $modelClientsHasCoursDate->fk_statut = Yii::$app->params['partInscrit'];
                            $modelClientsHasCoursDate->save(false);
                        }
                        $alerte['class'] = 'success';
                        $alerte['message'] = Yii::t('app', 'La personne a bien été enregistrée comme participante !');
                    }
                } elseif ($newCours[1] == Yii::$app->params['coursPonctuel']) {
                    $modelClientsHasCoursDate = new ClientsHasCoursDate();
                    $modelClientsHasCoursDate->fk_personne = $id;
                    $modelClientsHasCoursDate->fk_cours_date = $newCours[0];
                    $modelClientsHasCoursDate->is_present = true;
                    $modelClientsHasCoursDate->fk_statut = Yii::$app->params['partInscrit'];
                    if (!$modelClientsHasCoursDate->save()) {
                        $alerte['class'] = 'danger';
                        $alerte['message'] = Yii::t('app', 'Inscription impossible - erreur inattendue, veuillez contactez le support.');
                    } else {
                        $alerte['class'] = 'success';
                        $alerte['message'] = Yii::t('app', 'La personne a bien été enregistrée comme participante !');
                    }
                }
                if (in_array($model->fk_statut, Yii::$app->params['groupePersStatutNonActif']) && $alerte['class'] == 'success') {
                    $model->fk_statut = Yii::$app->params['persStatutInscrit'];
                    $model->save();
                    $alerte['message'] .= '<br />'.Yii::t('app', 'Son statut a été modifié en inscrit.');
                }
            } elseif (!empty($post['Parametres'])) {
                // soit on envoi un email
                SiteController::actionEmail($post['Parametres'], [$model->email => $model->email]);
                $alerte['class'] = 'info';
                $alerte['message'] = Yii::t('app', 'Email envoyé');
            } else {
                // dans ce cas on ajoute un participant sans en avoir sélectionné
                $alerte['class'] = 'warning';
                $alerte['message'] = Yii::t('app', 'L\'action à réaliser n\'a pas pu être définie.');
            }
        }
        
        $coursDateDataProvider = [];
        if ($model->fk_type == Yii::$app->params['typeEncadrant']) {
            $listeCoursDate = [];
            foreach ($model->moniteurHasCoursDate as $mcd) {
                $listeCoursDate[] = $mcd->fk_cours_date;
            }
            $coursDate = CoursDate::find()->where(['in', 'cours_date_id', $listeCoursDate])->orderBy(['date' => SORT_DESC]);
            $coursDateDataProvider = new ActiveDataProvider([
                'query' => $coursDate,
                'pagination' => [
                    'pageSize' => 20,
                ],
            ]);
        }
        
        $listeCours = [];
        $dataCoursDate = [];
        foreach ($model->clientsHasCoursDate as $clientCoursDate) {
            $listeCours[] = $clientCoursDate->fkCoursDate->fk_cours;
            
            if ($clientCoursDate->fkCoursDate->fkCours->fk_type == Yii::$app->params['coursPlanifie']) {
                $cle = $clientCoursDate->fkCoursDate->fk_cours.'|'.$clientCoursDate->fkCoursDate->fkCours->fk_type;
                $dataCoursDate[$cle]['duree'] = $clientCoursDate->fkCoursDate->fkCours->duree;
                $dataCoursDate[$cle]['linkid'] = $clientCoursDate->fkCoursDate->fk_cours;
                $dataCoursDate[$cle]['date'] = Yii::t('app', 'n/a');
            } else {
                $cle = $clientCoursDate->fk_cours_date.'|'.$clientCoursDate->fkCoursDate->fkCours->fk_type;
                $dataCoursDate[$cle]['duree'] = $clientCoursDate->fkCoursDate->duree;
                $dataCoursDate[$cle]['linkid'] = $clientCoursDate->fk_cours_date;
                $dataCoursDate[$cle]['date'] = $clientCoursDate->fkCoursDate->date;
            }
            $dataCoursDate[$cle]['fk_type'] = $clientCoursDate->fkCoursDate->fkCours->fk_type;
            $dataCoursDate[$cle]['fkType.nom'] = $clientCoursDate->fkCoursDate->fkCours->fkType->nom;
            $dataCoursDate[$cle]['fkNom.nom'] = $clientCoursDate->fkCoursDate->fkCours->fkNom->nom.' '.$clientCoursDate->fkCoursDate->fkCours->fkNiveau->nom;
            $dataCoursDate[$cle]['session'] = $clientCoursDate->fkCoursDate->fkCours->session;
            $dataCoursDate[$cle]['annee'] = $clientCoursDate->fkCoursDate->fkCours->annee;
            $dataCoursDate[$cle]['fkSaison.nom'] = isset($clientCoursDate->fkCoursDate->fkCours->fkSaison) ? $clientCoursDate->fkCoursDate->fkCours->fkSaison->nom : '';
        }
        $coursDataProvider = new ArrayDataProvider([
		    'allModels' => $dataCoursDate,
		    'pagination' => [
		        'pageSize' => 20,
		    ],
		]);
        
        $coursNot = Cours::find()->where(['not in', 'cours_id', $listeCours])->andWhere(['is_actif' => [1]])->all();
        foreach ($coursNot as $c) {
            if ($c->fk_type == Yii::$app->params['coursPlanifie']) {
                $dataCours[$c->fkType->nom][$c->cours_id.'|'.$c->fk_type] = $c->fkNom->nom.' '.$c->fkNiveau->nom.' '.$c->fkSaison->nom.' '.$c->session;
            } else {
                foreach ($c->coursDates as $coursDate) {
                    $dataCours[$c->fkType->nom][$coursDate->cours_date_id.'|'.$c->fk_type] = $c->fkNom->nom.' '.$c->fkNiveau->nom.' '.$c->fkSaison->nom.' '.$c->session.'-'.$coursDate->date;
                }
            }
        }
        
        $parametre = new Parametres();
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();
        
        return $this->render('view', [
            'alerte' => $alerte,
            'model' => $this->findModel($id),
            'coursDateDataProvider' => $coursDateDataProvider,
            'dataCours' => $dataCours,
            'coursDataProvider' => $coursDataProvider,
            'parametre' => $parametre,
            'emails' => $emails,
        ]);
    }

    /**
     * Creates a new Personnes model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Personnes();

        if ($model->load(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde de la personne.'));
                }
                
                $interlocuteurs = (isset($post['list_interlocuteurs'])) ? $post['list_interlocuteurs'] : [];
                foreach ($interlocuteurs as $interlocuteur_id) {
                    $addInterlocuteur = new PersonnesHasInterlocuteurs();
                    $addInterlocuteur->fk_personne = $model->personne_id;
                    $addInterlocuteur->fk_interlocuteur = $interlocuteur_id;
                    if (!($flag = $addInterlocuteur->save(false))) {
                        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des interlocuteur(s).'));
                    }
                }

                $transaction->commit();
                return $this->redirect(['view', 'id' => $model->personne_id]);
            } catch (Exception $e) {
                $alerte = $e->getMessage();
                $transaction->rollBack();
            }
        }
        
        $modelInterlocuteurs = Personnes::find()->orderBy('nom, prenom')->all();
        foreach ($modelInterlocuteurs as $interlocuteur) {
            $dataInterlocuteurs[$interlocuteur->fkStatut->nom][$interlocuteur->personne_id] = $interlocuteur->NomPrenom;
        }
        
        return $this->render('create', [
            'model' => $model,
            'modelParams' => new Parametres,
            'dataInterlocuteurs' => $dataInterlocuteurs,
            'selectedInterlocuteurs' => [],
        ]);
    }

    /**
     * Updates an existing Personnes model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde de la personne.'));
                }
                
                $interlocuteurs = (isset($post['list_interlocuteurs'])) ? $post['list_interlocuteurs'] : [];
                PersonnesHasInterlocuteurs::deleteAll('fk_personne = ' . $model->personne_id);
                foreach ($interlocuteurs as $interlocuteur_id) {
                    $addInterlocuteur = new PersonnesHasInterlocuteurs();
                    $addInterlocuteur->fk_personne = $model->personne_id;
                    $addInterlocuteur->fk_interlocuteur = $interlocuteur_id;
                    if (!($flag = $addInterlocuteur->save(false))) {
                        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des interlocuteur(s).'));
                    }
                }

                $transaction->commit();
                return $this->redirect(['view', 'id' => $model->personne_id]);
            } catch (Exception $e) {
                $alerte = $e->getMessage();
                $transaction->rollBack();
            }
        }
        
        $myInterlocuteurs = PersonnesHasInterlocuteurs::find()->where(['fk_personne' => $model->personne_id])->all();
        foreach ($myInterlocuteurs as $interlocuteur) {
	        $selectedInterlocuteurs[] = $interlocuteur->fk_interlocuteur;
        }
        $modelInterlocuteurs = Personnes::find()->where(['!=', 'personne_id', $model->personne_id])->orderBy('nom, prenom')->all();
        foreach ($modelInterlocuteurs as $interlocuteur) {
            $dataInterlocuteurs[$interlocuteur->fkStatut->nom][$interlocuteur->personne_id] = $interlocuteur->NomPrenom;
        }
        
        return $this->render('update', [
            'model' => $model,
            'modelParams' => new Parametres,
            'dataInterlocuteurs' => $dataInterlocuteurs,
            'selectedInterlocuteurs' => (isset($selectedInterlocuteurs)) ? $selectedInterlocuteurs : [],
        ]);
    }
    
    /**
     * Permet de lister les emails des personnes (ajax)
     * @return json
     */
    public function actionSetemail() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (isset($_POST['keylist'])) {
            $keys = $_POST['keylist'];
            foreach ($keys as $k) {
                $arrKeys = explode('*', $k);
                $ids[] = (isset($arrKeys[1])) ? $arrKeys[1] : $arrKeys[0];
            }
            $listeEmails = [];
            $models = Personnes::find()->where(['IN', 'personne_id', $ids])->all();
            foreach ($models as $myPersonne) {
                if (strpos($myPersonne->email, '@') !== false) {
                    $listeEmails[$myPersonne->email] = $myPersonne->email;
                }

                foreach ($myPersonne->personneHasInterlocuteurs as $pi) {
                    $listeEmails[$pi->fkInterlocuteur->email] = $pi->fkInterlocuteur->email;
                }
            }
        } else {
            $listeEmails = explode(', ', $_POST['allEmails']);
        }
        
        return ['emails' => implode(', ', $listeEmails)];
    }

    /**
     * Deletes an existing Personnes model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        PersonnesHasInterlocuteurs::deleteAll('fk_personne = ' . $id);
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Personnes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Personnes the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Personnes::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
