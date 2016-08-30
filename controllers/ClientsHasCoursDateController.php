<?php

namespace app\controllers;

use Yii;
use app\models\ClientsHasCoursDate;
use app\models\ClientsHasCoursDateSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ClientsHasCoursDateController implements the CRUD actions for ClientsHasCoursDate model.
 */
class ClientsHasCoursDateController extends Controller
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
     * Lists all ClientsHasCoursDate models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ClientsHasCoursDateSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ClientsHasCoursDate model.
     * @param integer $fk_personne
     * @param integer $fk_cours_date
     * @return mixed
     */
    public function actionView($fk_personne, $fk_cours_date)
    {
        return $this->render('view', [
            'model' => $this->findModel($fk_personne, $fk_cours_date),
        ]);
    }

    /**
     * Creates a new ClientsHasCoursDate model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ClientsHasCoursDate();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'fk_personne' => $model->fk_personne, 'fk_cours_date' => $model->fk_cours_date]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing ClientsHasCoursDate model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $fk_personne
     * @param integer $fk_cours_date
     * @return mixed
     */
    public function actionUpdate($fk_personne, $fk_cours_date)
    {
        $model = $this->findModel($fk_personne, $fk_cours_date);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'fk_personne' => $model->fk_personne, 'fk_cours_date' => $model->fk_cours_date]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing ClientsHasCoursDate model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $fk_personne
     * @param integer $fk_cours_date
     * @return mixed
     */
    public function actionDelete($fk_personne, $fk_cours_date)
    {
        $this->findModel($fk_personne, $fk_cours_date)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the ClientsHasCoursDate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $fk_personne
     * @param integer $fk_cours_date
     * @return ClientsHasCoursDate the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($fk_personne, $fk_cours_date)
    {
        if (($model = ClientsHasCoursDate::findOne(['fk_personne' => $fk_personne, 'fk_cours_date' => $fk_cours_date])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
