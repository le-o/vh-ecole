<?php

namespace app\controllers;

use Yii;
use app\models\ClientsHasCours;
use app\models\CoursDate;
use app\models\Personnes;
use app\models\Parametres;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ClientsHasCoursController implements the update actions for ClientsHasCours model.
 */
class ClientsHasCoursController extends Controller
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
     * Updates an existing ClientsHasCours model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $fk_personne
     * @param integer $fk_cours
     * @param string $from
     * @return mixed
     */
    public function actionUpdate($fk_personne, $fk_cours, $from = 'cours')
    {
        $clientsHasCours = \app\models\ClientsHasCours::find()->where(['fk_personne' => $fk_personne, 'fk_cours' => $fk_cours])->one();
        $modelPersonne = Personnes::findOne(['personne_id' => $fk_personne]);
        
        if ($clientsHasCours->load(Yii::$app->request->post())) {
            $clientsHasCours->save();
        } else {
            return $this->render('update', [
                'model' => $clientsHasCours,
                'modelPersonne' => $modelPersonne,
                'modelParams' => new Parametres,
            ]);
        }
        $url = json_decode($from);
        if (is_object($url)) {
            return $this->redirect([$url->url, 'page' => (isset($url->page)) ? $url->page : 1]);
        }
        return $this->redirect(['/cours/view', 'id' => $fk_cours]);
    }
}
