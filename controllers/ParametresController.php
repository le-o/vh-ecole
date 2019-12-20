<?php

namespace app\controllers;

use Yii;
use app\models\Parametres;
use app\models\ParametresSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ParametresController implements the CRUD actions for Parametres model.
 */
class ParametresController extends Controller
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
            'ghost-access'=> [
                'class' => 'webvimark\modules\UserManagement\components\GhostAccessControl',
            ],
        ];
    }

    /**
     * Lists all Parametres models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ParametresSearch();
        // on sauve les filtres et la pagination
        $params = Yii::$app->request->queryParams;
        if (count($params) <= 1) {
            if (isset(Yii::$app->session['ParametresSearch'])) {
                $params = Yii::$app->session['ParametresSearch'];
            } else {
                Yii::$app->session['ParametresSearch'] = $params;
            }
        } else {
            if (isset(Yii::$app->request->queryParams['ParametresSearch'])) {
                Yii::$app->session['ParametresSearch'] = $params;
            } else {
                $params = Yii::$app->session['ParametresSearch'];
            }
        }
        $dataProvider = $searchModel->search($params);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Parametres model.
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
     * Creates a new Parametres model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Parametres();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->parametre_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Parametres model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->parametre_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Parametres model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    public function actionGetemail() {
        $data = Yii::$app->request->post();
        $emailTemplate = Parametres::findOne($data['id']);
        return json_encode([['sujet' => $emailTemplate->nom, 'contenu' => $emailTemplate->valeur]]);
    }

    /**
     * Finds the Parametres model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Parametres the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Parametres::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
