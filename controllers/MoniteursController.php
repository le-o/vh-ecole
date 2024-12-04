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

    private static $SEXETOCIVILITE = [
        410 => 'Monsieur',
        411 => 'Madame'
    ];
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
    public function actionExport()
    {
        $this->layout = 'main_full.php';
        $searchModel = new MoniteursSearch();

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $gridColumnsASSE = $this->getGridColumnsForASSE();
        $gridColumnsMP = $this->getGridColumnsForMP();

        return $this->render('export', [
            'dataProvider' => $dataProvider,
            'gridColumnsMP' => $gridColumnsMP,
            'gridColumnsASSE' => $gridColumnsASSE,
        ]);
    }

    private function getGridColumnsForMP():array {
        return [
            [
                'label' => 'Civilité',
                'value' => function($model) {
                    if (isset($model->fkPersonne->fk_sexe)) {
                        return self::$SEXETOCIVILITE[$model->fkPersonne->fk_sexe];
                    }
                    return '';
                }
            ],
            ['label' => 'Nom', 'attribute' => 'fkPersonne.nom'],
            ['label' => 'Prénom', 'attribute' => 'fkPersonne.prenom'],
            ['label' => 'Téléphone', 'attribute' => 'fkPersonne.telephone'],
            ['label' => 'Date de naissance', 'attribute' => 'fkPersonne.date_naissance'],
        ];
    }

    private function getGridColumnsForASSE():array {
        $columns = [
            [
                'label' => 'Kletteranlage',
                'value' => function($model) {
                    if (isset($model->fkPersonne->fk_salle_admin)) {
                        return 'Vertic-Halle - ' . $model->fkPersonne->fkSalleadmin->nom;
                    }
                    return '';
                }
            ],
            [
                'label' => 'Rolle',
                'value' => function($model) {
                    return $model->moniteursRole;
                }
            ],
            [
                'label' => 'Status',
                'value' => function($model) {
                    if (isset($model->fkPersonne->fk_type)) {
                        return Yii::t('app', $model->fkPersonne->fkType->nom, [], 'de-CH');
                    }
                    return '';
                }
            ],
            ['label' => 'Name', 'attribute' => 'fkPersonne.nom'],
            ['label' => 'Vorname', 'attribute' => 'fkPersonne.prenom'],
            [
                'label' => 'Strasse',
                'value' => function($model) {
                    return $model->fkPersonne->adresse1  . ' ' . $model->fkPersonne->numeroRue;
                }
            ],
            ['label' => 'PLZ', 'attribute' => 'fkPersonne.npa'],
            ['label' => 'Ort', 'attribute' => 'fkPersonne.localite'],
            [
                'label' => 'Geschlecht',
                'value' => function($model) {
                    if (isset($model->fkPersonne->fk_sexe)) {
                        return Yii::t('app', $model->fkPersonne->fkSexe->nom, [], 'de-CH');
                    }
                    return '';
                }
            ],
            ['label' => 'Geb. Datum', 'attribute' => 'fkPersonne.date_naissance'],
            ['label' => 'Tel. ', 'attribute' => 'fkPersonne.telephone'],
            ['label' => 'email', 'attribute' => 'fkPersonne.email'],
            [
                'label' => 'Prüfung',
                'value' => function($model) {
                    return $model->moniteursExamDate;
                }
            ],
            ['label' => 'Bemerkung', 'attribute' => 'remarque'],
        ];

        foreach ((new Parametres())->listFormations() as $key => $f) {
            $columns[] = [
                'label' => $f,
                'value' => function($model) use ($key) {
                    return ($model->checkMoniteursHasOneFormation($model->moniteur_id, $key) ? 'OK' : 'FAUX');
                },
            ];
        }
        return $columns;
    }
}
