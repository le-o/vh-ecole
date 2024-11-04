<?php

namespace app\controllers;

use app\models\MoniteursHasFormations;
use Yii;
use app\models\Moniteurs;
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
}
