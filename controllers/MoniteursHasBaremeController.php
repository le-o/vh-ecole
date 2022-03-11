<?php

namespace app\controllers;

use app\models\Parametres;
use Yii;
use app\models\MoniteursHasBareme;
use app\models\MoniteursHasBaremeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * MoniteursHasBaremeController implements the CRUD actions for MoniteursHasBareme model.
 */
class MoniteursHasBaremeController extends Controller
{
    /**
     * {@inheritdoc}
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
        ];
    }

    /**
     * Lists all MoniteursHasBareme models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new MoniteursHasBaremeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single MoniteursHasBareme model.
     * @param integer $fk_personne
     * @param integer $fk_bareme
     * @param string $date_debut
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($fk_personne, $fk_bareme, $date_debut)
    {
        return $this->render('view', [
            'model' => $this->findModel($fk_personne, $fk_bareme, $date_debut),
        ]);
    }

    /**
     * Creates a new MoniteursHasBareme model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($fk_personne)
    {
        $model = new MoniteursHasBareme();
        $model->fk_personne = $fk_personne;

        if ($model->load(Yii::$app->request->post())) {
            // on modifie la date de fin du barème précédent et la date de début du suivant
            $saveOtherOk = $this->editOtherBaremeDate($model);

            if ($saveOtherOk && $model->save()) {
                return $this->redirect(['/personnes/view', 'id' => $model->fk_personne]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'modelParams' => new Parametres(),
        ]);
    }

    /**
     * Updates an existing MoniteursHasBareme model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param json $jsonData
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($jsonData)
    {
        $jsonData = json_decode($jsonData, true);
        $model = $this->findModel($jsonData['fk_personne'], $jsonData['fk_bareme'], $jsonData['date_debut']);

        if ($model->load(Yii::$app->request->post())) {
            // on modifie la date de fin du barème précédent et la date de début du suivant
            $saveOtherOk = $this->editOtherBaremeDate($model);

            if ($saveOtherOk && $model->save()) {
                return $this->redirect(['/personnes/view', 'id' => $model->fk_personne]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'modelParams' => new Parametres(),
        ]);
    }

    /**
     * Deletes an existing MoniteursHasBareme model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $fk_personne
     * @param integer $fk_bareme
     * @param string $date_debut
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($fk_personne, $fk_bareme, $date_debut)
    {
        $this->findModel($fk_personne, $fk_bareme, $date_debut)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the MoniteursHasBareme model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $fk_personne
     * @param integer $fk_bareme
     * @param string $date_debut
     * @return MoniteursHasBareme the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($fk_personne, $fk_bareme, $date_debut)
    {
        if (($model = MoniteursHasBareme::findOne(['fk_personne' => $fk_personne, 'fk_bareme' => $fk_bareme, 'date_debut' => $date_debut])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    private function editOtherBaremeDate(MoniteursHasBareme $model): bool
    {
        $savePrevious = $this->editPreviousBaremeDate($model);
        $saveNext = $this->editNextBaremeDate($model);
        return $savePrevious && $saveNext;
    }

    /**
     * @param MoniteursHasBareme $model
     * @return bool
     */
    private function editPreviousBaremeDate(MoniteursHasBareme $model): bool
    {
        $previous = $model->getPreviousBareme();
        if (!empty($previous)) {
            $previous->date_fin = date('d.m.Y', strtotime($model->date_debut . ' - 1 days'));
            return $previous->save();
        }
        return true;
    }

    /**
     * @param MoniteursHasBareme $model
     * @return bool
     */
    private function editNextBaremeDate(MoniteursHasBareme $model): bool
    {
        $next = $model->getNextBareme();
        if (!empty($next)) {
            $next->date_debut = date('d.m.Y', strtotime($model->date_fin . ' + 1 days'));
            return $next->save();
        }
        return true;
    }
}
