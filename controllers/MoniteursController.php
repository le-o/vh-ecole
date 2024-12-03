<?php

namespace app\controllers;

use app\models\CoursDateSearch;
use app\models\MoniteursHasFormations;
use app\models\MoniteursSearch;
use app\models\Parametres;
use Yii;
use app\models\Moniteurs;
use yii\data\ArrayDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * MoniteursController implements the CRUD actions for Moniteurs model.
 */
class MoniteursController extends CommonController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Creates a new Moniteurs model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($fk_personne)
    {
        $model = new Moniteurs();
        $alerte = '';

        if ($model->load(Yii::$app->request->post())) {
            $model->fk_personne = $fk_personne;

            $formationsSelected = [];
            if (isset(Yii::$app->request->post()['formationsMoniteur'])) {
                $formationsSelected = array_keys(Yii::$app->request->post()['formationsMoniteur']);
            }

            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde du moniteur.'));
                }

                $this->saveFormations($model);

                $transaction->commit();
                return $this->redirect(['/personnes/view', 'id' => $model->fk_personne, 'tab' => 'moniteur']);
            } catch (\Exception $e) {
                $alerte = $e->getMessage();
                $transaction->rollBack();
            }
        }

        return $this->render('create', [
            'alerte' => $alerte,
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Moniteurs model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $alerte = '';

        if ($model->load(Yii::$app->request->post())) {

            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde du moniteur.'));
                }

                $this->saveFormations($model);

                $transaction->commit();
                return $this->redirect(['/personnes/view', 'id' => $model->fk_personne, 'tab' => 'moniteur']);
            } catch (\Exception $e) {
                $alerte = $e->getMessage();
                $transaction->rollBack();
            }
        }

        return $this->render('update', [
            'alerte' => $alerte,
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Moniteurs model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $moniteur = $this->findModel($id);
        $formations = $moniteur->moniteursHasFormations;
        foreach ($formations as $formation) {
            $formation->delete();
        }
        $moniteur->delete();

        return $this->redirect(['/personnes/view', 'id' => $moniteur->fk_personne]);
    }

    /**
     * Finds the Moniteurs model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Moniteurs the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Moniteurs::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * @param Moniteurs $model
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function saveFormations(Moniteurs $model): void
    {
        $formationsSelected = [];
        if (isset(Yii::$app->request->post()['formationsMoniteur'])) {
            $formationsSelected = array_keys(Yii::$app->request->post()['formationsMoniteur']);
        }
        foreach ($formationsSelected as $f) {
            if (!in_array($f, $model->formationsStored)) {
                $modelMoniteursHasFormations = new MoniteursHasFormations([
                    'fk_moniteur' => $model->moniteur_id,
                    'fk_formation' => $f,
                ]);
                if (!$modelMoniteursHasFormations->save()) {
                    throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde des formations.'));
                }
            }
        }

        //remove the stored ids that are not exist in the selected ids
        $formationsToDelete = array_diff($model->formationsStored, $formationsSelected);
        foreach ($formationsToDelete as $f) {
            $modelMoniteursHasFormations = MoniteursHasFormations::findOne([
                'fk_moniteur' => $model->moniteur_id,
                'fk_formation' => $f,
            ]);
            $modelMoniteursHasFormations->delete();
        }
    }

    /**
     * List and export moniteurs.
     * @return mixed
     */
    public function actionExportasse()
    {
        $this->layout = 'main_full.php';
        $searchModel = new MoniteursSearch();
//        $searchModel->depuis = date('d.m.Y');
//
//        $searchModel->withoutMoniteur = false;
//        // on clone le searchModel pour la liste déroulante des cours actifs
//        $searchModelAllCours = clone $searchModel;
//
//        $searchModel->listCours = (isset(Yii::$app->request->queryParams['list_cours'])) ? Yii::$app->request->queryParams['list_cours'] : [];
//        $selectedCours = $searchModel->listCours;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false);

        return $this->render('/personnes/exportasse', [
            'dataProvider' => $dataProvider,
            'formations' => (new Parametres())->listFormations(),
        ]);

        $selectedFinance = (isset(Yii::$app->request->queryParams['list_finance'])) ? Yii::$app->request->queryParams['list_finance'] : '';

        $dataProviderAllCours = $searchModelAllCours->search(Yii::$app->request->queryParams, false);

        if (!empty(Yii::$app->request->post()) && isset(Yii::$app->request->post()['checkedEmails'])) {
            $mail = Yii::$app->request->post();
            $this->actionEmail($mail['Parametres'], explode(', ', $mail['checkedEmails']));

            $alerte['class'] = 'info';
            $alerte['message'] = Yii::t('app', 'Email envoyé à toutes les personnes sélectionnées');
        }

        $arrayParticipants = [];
        $listeEmails = [];
        foreach ($dataProvider->models as $data) {
            foreach ($data->clientsHasCoursDate as $client) {
                if (!isset($arrayParticipants[$client->fk_personne]) && (empty($selectedFinance) || $selectedFinance == $client->fkPersonne->fk_finance)) {
                    $arrayParticipants[$client->fk_personne]['nom'] = $client->fkPersonne->nom;
                    $arrayParticipants[$client->fk_personne]['prenom'] = $client->fkPersonne->prenom;
                    $arrayParticipants[$client->fk_personne]['finance'] = (isset($client->fkPersonne->fkFinance) ?
                        $client->fkPersonne->fkFinance->nom : '');
                    $arrayParticipants[$client->fk_personne]['suivi_client'] = $client->fkPersonne->suivi_client;
                    $arrayParticipants[$client->fk_personne]['date_naissance'] = $client->fkPersonne->date_naissance;
                    $arrayParticipants[$client->fk_personne]['cours_id'] = $data->fk_cours;
                    $arrayParticipants[$client->fk_personne]['personne_id'] = $client->fk_personne;
                    $arrayParticipants[$client->fk_personne]['avs'] = $client->fkPersonne->no_avs;
                    $arrayParticipants[$client->fk_personne]['email'] =
                        ('interloc.' == $client->fkPersonne->email && isset($client->fkPersonne->personneHasInterlocuteurs[0])) ?
                            $client->fkPersonne->personneHasInterlocuteurs[0]->fkInterlocuteur->email :
                            $client->fkPersonne->email;
                    $arrayParticipants[$client->fk_personne]['telephone'] =
                        ('interloc.' == $client->fkPersonne->telephone && isset($client->fkPersonne->personneHasInterlocuteurs[0])) ?
                            $client->fkPersonne->personneHasInterlocuteurs[0]->fkInterlocuteur->telephone :
                            $client->fkPersonne->telephone;

                    // pour export JS
                    if ($forExport) {
                        $arrayParticipants[$client->fk_personne]['nopersonnel'] = $client->fkPersonne->nopersonnel;
                        $arrayParticipants[$client->fk_personne]['fkSexe'] = $client->fkPersonne->fkSexe;
                        $arrayParticipants[$client->fk_personne]['no_avs'] = $client->fkPersonne->no_avs;
                        $arrayParticipants[$client->fk_personne]['fkNationalite'] = $client->fkPersonne->fkNationalite;
                        $arrayParticipants[$client->fk_personne]['fkLangueMat'] = $client->fkPersonne->fkLangueMat;
                        $arrayParticipants[$client->fk_personne]['rue'] = $client->fkPersonne->adresse1;
                        $arrayParticipants[$client->fk_personne]['numero'] = $client->fkPersonne->numeroRue;
                        $arrayParticipants[$client->fk_personne]['npa'] = $client->fkPersonne->npa;
                        $arrayParticipants[$client->fk_personne]['localite'] = $client->fkPersonne->localite;
                        $arrayParticipants[$client->fk_personne]['fkPays'] = $client->fkPersonne->fkPays;
                    }

                    if (strpos($client->fkPersonne->email, '@') !== false) {
                        $listeEmails[$client->fkPersonne->email] = trim($client->fkPersonne->email);
                    }
                    foreach ($client->fkPersonne->personneHasInterlocuteurs as $pi) {
                        $listeEmails[$pi->fkInterlocuteur->email] = trim($pi->fkInterlocuteur->email);
                    }
                    $arrayParticipants[$client->fk_personne]['cours_info'] =
                        (isset($data->fkCours->fk_nom) ? $data->fkCours->fkNom->nom : '') . ' ' .
                        $data->fkCours->session . ' ' .
                        (isset($data->fkCours->fk_saison) ? $data->fkCours->fkSaison->nom : '') . ' ' .
                        (isset($data->fkCours->fk_salle) ? $data->fkCours->fkSalle->nom : '');
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

        $dataCours = [];
        foreach ($dataProviderAllCours->models as $data) {
            if (!$forExport || ($forExport && in_array($data->fkCours->fkNom->info_special, Yii::$app->params["coursPlanifieS"]))) {
                $dataCours[$data->fk_cours] = $data->fkCours->fkNom->nom . ' ' . $data->fkCours->session;
            }
        }
        asort($dataCours);

        $params = [];
        if (!$forExport) {
            $parametre = new Parametres();
            $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();
            $dataFinance = $parametre->optsFinance();
            $params = [
                'selectedFinance' => $selectedFinance,
                'dataFinance' => $dataFinance,
                'parametre' => $parametre,
                'emails' => $emails,
                'listeEmails' => $listeEmails,
            ];
        }

        return $this->render($view, array_merge([
            'dataProvider' => $participantDataProvider,
            'searchModel' => $searchModel,
            'selectedCours' => $selectedCours,
            'dataCours' => $dataCours,
            'view' => $view,
        ], $params));
    }
}
