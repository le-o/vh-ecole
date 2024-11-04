<?php

namespace app\controllers;

use webvimark\modules\UserManagement\models\rbacDB\Role;
use webvimark\modules\UserManagement\models\User;
use Yii;
use app\models\Personnes;
use app\models\PersonnesSearch;
use app\models\PersonnesHasInterlocuteurs;
use app\models\Parametres;
use app\models\Cours;
use app\models\CoursDate;
use app\models\ClientsHasCours;
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
class PersonnesController extends CommonController
{

    private $filtres = [];

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'ghost-access'=> [
                'class' => 'webvimark\modules\UserManagement\components\GhostAccessControl',
            ],
        ];
    }

    public function __construct($id, $module)
    {
        $this->filtres = [
            'moniteursActifs' => Yii::$app->params['typeEncadrantActif'],
            'moniteurs' => Yii::$app->params['typeEncadrant'],
            'personnes' => false,
        ];
        parent::__construct($id, $module);
    }
    
    // for route purpose only
    public function actionAdvanced() {}

    /**
     * Lists all Personnes models.
     * @return mixed
     */
    public function actionIndex($filtre = 'personnes')
    {
        $alerte = [];
        $searchModel = new PersonnesSearch();
        $searchModel->fk_type = $this->filtres[$filtre];

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProviderAll = $searchModel->search(Yii::$app->request->queryParams, false);
        
        $listeEmails = [];
        foreach ($dataProviderAll->models as $myPersonne) {
            if (strpos($myPersonne->email, '@') !== false) {
                $listeEmails[$myPersonne->email] = trim($myPersonne->email);
            }
            
            foreach ($myPersonne->personneHasInterlocuteurs as $pi) {
                $listeEmails[$pi->fkInterlocuteur->email] = trim($pi->fkInterlocuteur->email);
            }
        }

        if (!empty(Yii::$app->request->post()) && isset(Yii::$app->request->post()['checkedEmails'])) {
            $mail = Yii::$app->request->post();
            $this->actionEmail($mail['Parametres'], explode(', ', $mail['checkedEmails']));

            $alerte['class'] = 'info';
            $alerte['message'] = Yii::t('app', 'Email envoyé à toutes les personnes sélectionnées');
        }
        
        $parametre = new Parametres();
        $statutFilter = $parametre->optsStatut();
        $salleFilter = $parametre->optsSalle();
        $financeFilter = $parametre->optsFinance();
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();

        $buttonFilter = [
            [
                'label' => 'Personnes',
                'filtre' => 'personnes',
                'class' => ($filtre == 'personnes') ? ' btn-info' : '',
            ],
            [
                'label' => 'Moniteurs actifs',
                'filtre' => 'moniteursActifs',
                'class' => ($filtre == 'moniteursActifs') ? ' btn-info' : '',
            ],
            [
                'label' => 'Tous les moniteurs',
                'filtre' => 'moniteurs',
                'class' => ($filtre == 'moniteurs') ? ' btn-info' : '',
            ],
        ];

        return $this->render('index', [
            'alerte' => $alerte,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'statutFilter' => $statutFilter,
            'salleFilter' => $salleFilter,
            'financeFilter' => $financeFilter,
            'buttonFilter' => $buttonFilter,
            'parametre' => $parametre,
            'emails' => $emails,
            'listeEmails' => $listeEmails,
        ]);
    }

    /**
     * Lists all Personnes models.
     * @return mixed
     */
    public function actionMoniteurs($isMoniteur = false)
    {
        $this->layout = 'main_full';
        $searchModel = new PersonnesSearch();

        // si profil moniteur, accès uniquement à ses données
        if ($isMoniteur) {
            $searchModel->personne_id = Yii::$app->user->fkpersonne;
        } elseif (!isset(Yii::$app->request->queryParams['PersonnesSearch'])) {
            $searchModel->personne_id = -1;
        }
        $dataProvider = $searchModel->searchMoniteurs(Yii::$app->request->queryParams, false);
        
        $searchParams = Yii::$app->request->queryParams;
        $searchParCours = (isset($searchParams['list_cours']) && $searchParams['list_cours'] !== '') ? true : false;
        if (!isset($searchParams['from_date'])) {
            $searchParams['from_date'] = date('01.01.Y');
        }
        if (!isset($searchParams['to_date'])) {
            $searchParams['to_date'] = date('31.12.Y');
        }
        $searchFrom = (isset($searchParams['from_date']) && $searchParams['from_date'] !== '') ? date('Y-m-d', strtotime($searchParams['from_date'])) : '1970-01-01';
        $searchTo = (isset($searchParams['to_date']) && $searchParams['to_date'] !== '') ? date('Y-m-d', strtotime($searchParams['to_date'])) : '9999-12-31';
        
        $dataMoniteurs = [];
        $heuresTotal = 0;
        $baremes = (new Parametres())->optsBaremeMoniteur();
        $baremes[-1] = 'Non défini'; // pour reprendre les heures si paramétrage inexistant
        if (!$isMoniteur || !is_null($searchModel->personne_id)) {
            foreach ($dataProvider->models as $moniteur) {
                $heures = 0;
                foreach ($baremes as $key => $bareme) {
                    $heuresBareme[$key] = 0;
                }
                foreach ($moniteur->moniteurHasCoursDate as $mcd) {
                    $coursDate = CoursDate::findOne($mcd->fk_cours_date);

                    if ($this->keepDateInStatistics($coursDate)) {
                        $dateRef = date('Y-m-d', strtotime($coursDate->date));
                        if ($dateRef >= $searchFrom && $dateRef <= $searchTo) {
                            if ($searchParCours) {
                                if ($coursDate->fkCours->fk_nom == $searchParams['list_cours']) {
                                    $heures += $coursDate->duree;
                                    // total par barème
                                    $key = $this->getBaremeID($mcd, $moniteur->fk_formation);
                                    if (!isset($heuresBareme[$key])) {
                                        $heuresBareme[$key] = 0;
                                    }
                                    $heuresBareme[$key] += $coursDate->duree;
                                }
                            } else {
                                $heures += $coursDate->duree;
                                // total par barème
                                $key = $this->getBaremeID($mcd, $moniteur->fk_formation);

                                if (!isset($heuresBareme[$key])) {
                                    $heuresBareme[$key] = 0;
                                }
                                $heuresBareme[$key] += $coursDate->duree;
                            }
                        }
                    }
                }
                if (!$searchParCours || ($searchParCours && $heures !== 0)) {
                    $dataMoniteurs[$moniteur->personne_id]['personne_id'] = $moniteur->personne_id;
                    $dataMoniteurs[$moniteur->personne_id]['no_cresus'] = (isset($moniteur->moniteurInfo) ? $moniteur->moniteurInfo->no_cresus : '');
                    $dataMoniteurs[$moniteur->personne_id]['type'] = $moniteur->fkType->nom;
                    $dataMoniteurs[$moniteur->personne_id]['nom'] = $moniteur->nom;
                    $dataMoniteurs[$moniteur->personne_id]['prenom'] = $moniteur->prenom;
                    $dataMoniteurs[$moniteur->personne_id]['adresse1'] = $moniteur->adresse1;
                    $dataMoniteurs[$moniteur->personne_id]['adresse2'] = $moniteur->adresse2;
                    $dataMoniteurs[$moniteur->personne_id]['npa'] = $moniteur->npa;
                    $dataMoniteurs[$moniteur->personne_id]['localite'] = $moniteur->localite;
                    $dataMoniteurs[$moniteur->personne_id]['date_naissance'] = $moniteur->date_naissance;
                    $dataMoniteurs[$moniteur->personne_id]['fk_langues'] = $moniteur->fkLanguesNoms;
                    $dataMoniteurs[$moniteur->personne_id]['email'] = $moniteur->email;
                    $dataMoniteurs[$moniteur->personne_id]['telephone'] = $moniteur->telephone;
                    if (empty($moniteur->currentBareme)) {
                        $dataMoniteurs[$moniteur->personne_id]['fk_formation'] = ($moniteur->fk_formation == 0 || is_null($moniteur->fk_formation) || !isset($moniteur->fkFormation)) ? 'Non défini' : '**Ancienne configuration : ' . $moniteur->fkFormation->nom;
                    } else {
                        $dataMoniteurs[$moniteur->personne_id]['fk_formation'] = $moniteur->currentBareme->fkBareme->nom;
                    }
                    $dataMoniteurs[$moniteur->personne_id]['heures'] = number_format($heures, 2, '.', '\'');
                    foreach ($baremes as $key => $bareme) {
                        $dataMoniteurs[$moniteur->personne_id][$bareme] = number_format($heuresBareme[$key], 2, '.', '\'');
                    }
                    $heuresTotal += $heures;
                }
            }
        }
        $moniteursProvider = new ArrayDataProvider([
            'key' => 'personne_id',
            'allModels' => $dataMoniteurs,
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
        
        // gestion du tri ici, car on a reconstruit le dataprovider manuellement
        $moniteursProvider->setSort([
            'attributes' => [
//                'statut' => [
//                    'asc' => ['statut' => SORT_ASC],
//                    'desc' => ['statut' => SORT_DESC],
//                ],
                'type' => [
                    'asc' => ['type' => SORT_ASC],
                    'desc' => ['type' => SORT_DESC],
                ],
//                'societe' => [
//                    'asc' => ['societe' => SORT_ASC],
//                    'desc' => ['societe' => SORT_DESC],
//                ],
                'nom' => [
                    'asc' => ['nom' => SORT_ASC],
                    'desc' => ['nom' => SORT_DESC],
                ],
                'prenom' => [
                    'asc' => ['prenom' => SORT_ASC],
                    'desc' => ['prenom' => SORT_DESC],
                ],
//                'localite' => [
//                    'asc' => ['localite' => SORT_ASC],
//                    'desc' => ['localite' => SORT_DESC],
//                ],
            ],
            'defaultOrder' => [
                'type' => SORT_ASC,
                'nom' => SORT_ASC
            ]
        ]);
        
        $modelParams = new Parametres();
        $dataCours = $modelParams->optsNomCours();
        $selectedCours = (isset($searchParams['list_cours'])) ? $searchParams['list_cours'] : '';
        
        $dataLangues = $modelParams->optsLangue();
        $selectedLangue = (isset($searchParams['fk_langues'])) ? $searchParams['fk_langues'] : '';
        
        $fromData = serialize(['selectedCours' => $selectedCours, 'searchFrom' => $searchFrom, 'searchTo' => $searchTo]);

        return $this->render('moniteurs', [
            'searchModel' => $searchModel,
            'searchFrom' => ($searchFrom == '1970-01-01') ? '' : date('d.m.Y', strtotime($searchFrom)),
            'searchTo' => ($searchTo == '9999-12-31') ? '' : date('d.m.Y', strtotime($searchTo)),
            'selectedCours' => $selectedCours,
            'dataCours' => $dataCours,
            'selectedLangue' => $selectedLangue,
            'dataLangues' => $dataLangues,
            'moniteursProvider' => $moniteursProvider,
            'heuresTotal' => number_format($heuresTotal, 2, '.', '\''),
            'fromData' => $fromData,
            'isMoniteur' => $isMoniteur,
            'baremes' => $baremes,
        ]);
    }

    /**
     * @return mixed
     */
    public function actionMycours() {
        return $this->actionMoniteurs(true);
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

        $listeCoursDate = [];
        foreach ($model->moniteurHasCoursDate as $mcd) {
            if (($fromData['selectedCours'] == '' ||
                ($fromData['selectedCours'] != '' && $mcd->fkCoursDate->fkCours->fk_nom == $fromData['selectedCours'])) &&
                $this->keepDateInStatistics($mcd->fkCoursDate)
            ) {
                $listeCoursDate[] = $mcd->fk_cours_date;
                if (null != $mcd->fk_bareme) {
                    $baremeCours[$mcd->fk_cours_date] = '*' . $mcd->fkBareme->nom;
                } else {
                    $isBaremeSet = $mcd->fkMoniteur->getMoniteursHasBaremeFromDate(date('Y-m-d', strtotime($mcd->fkCoursDate->date)));
                    $baremeCours[$mcd->fk_cours_date] = (null != $isBaremeSet ? $isBaremeSet->fkBareme->nom : $model->fkFormation->nom);
                }
            }
        }
        $coursDate = CoursDate::find()
            ->where(['in', 'cours_date_id', $listeCoursDate])
            ->andWhere(['between', 'date', $fromData['searchFrom'], $fromData['searchTo']])
            ->orderBy(['date' => SORT_DESC]);
        $arrayCoursDate = [];
        foreach($coursDate->all() as $cd) {
            $arrayCoursDate[] = [
                'date' => $cd->date,
                'nom' => $cd->fkCours->fkNom->nom,
                'heure_debut' => $cd->heure_debut,
                'heure_fin' => $cd->heureFin,
                'lieu' => $cd->fkLieu->nom,
                'duree' => $cd->duree,
                'bareme' => $baremeCours[$cd->cours_date_id],
            ];
        }
        $coursDateDataProvider = new ArrayDataProvider([
            'allModels' => $arrayCoursDate,
            'pagination' => [
                'pageSize' => false,
            ],
        ]);

        if (!$print) {
            return $this->render('viewmoniteur', [
                'model' => $model,
                'coursDateDataProvider' => $coursDateDataProvider,
                'fromData' => $fromData,
                'sum' => $coursDate->sum('duree'),
            ]);
        }

        $content = $this->renderPartial('viewmoniteur', [
            'model' => $model,
            'coursDateDataProvider' => $coursDateDataProvider,
            'fromData' => $fromData,
            'sum' => $coursDate->sum('duree'),
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
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
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
     * @param string $tab active tab
     * @return mixed
     */
    public function actionView($id, $tab = 'client')
    {
        $alerte = [];
        $model = $this->findModel($id);
        
        if (!empty(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            
            if (!empty($post['new_cours'])) {
                // soit on ajoute un cours
                $newCours = explode('|', $post['new_cours']);
                if (in_array($newCours[1], Yii::$app->params['coursPlanifieS'])) {
                    $modelDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $newCours[0]])
                        ->andWhere(['>=', 'date', date('Y-m-d')])
                        ->all();
                    if (empty($modelDate)) {
                        $alerte['class'] = 'warning';
                        $alerte['message'] = Yii::t('app', 'Inscription impossible - aucune date dans le futur');
                    } else {
                        $alerte = $this->addClientToCours($modelDate, $id, $newCours[0]);
                    }
                } elseif (in_array($newCours[1], Yii::$app->params['coursPonctuelUnique'])) {
                    $modelDate = CoursDate::findOne(['cours_date_id' => $newCours[0]]);
                    $alerte = $this->addClientToCours([$modelDate], $id, $modelDate->fk_cours);
                }
            } elseif (!empty($post['Parametres'])) {
                // soit on envoi un email
                $post['Parametres']['personne_id'] = $id;
                
                // email interloc. = pas d'envoi sinon message d'erreur :(, donc on cherche les emails des interlocuteurs
                if (strpos($model->email, '@') !== false) {
                    $listeEmails[$model->email] = trim($model->email);
                }
                foreach ($model->personneHasInterlocuteurs as $pi) {
                    $listeEmails[$pi->fkInterlocuteur->email] = trim($pi->fkInterlocuteur->email);
                }
                $this->actionEmail($post['Parametres'], $listeEmails);
                $alerte['class'] = 'info';
                $alerte['message'] = Yii::t('app', 'Email envoyé');
            } else {
                // dans ce cas on ajoute un participant sans en avoir sélectionné
                $alerte['class'] = 'warning';
                $alerte['message'] = Yii::t('app', 'L\'action à réaliser n\'a pas pu être définie.');
            }
        }
        
        $coursDateDataProvider = [];
        $moniteursHasBaremeDataProvider = [];
        if (in_array($model->fk_type, Yii::$app->params['typeEncadrant'])) {
            // on retrouve les barèmes
            $moniteursHasBaremeDataProvider = new ActiveDataProvider([
                'query' => $model->getMoniteursHasBareme()
            ]);

            // on retrouve les cours
            $listeCoursDate = [];
            foreach ($model->moniteurHasCoursDate as $mcd) {
                $coursDate = CoursDate::findOne($mcd->fk_cours_date);
                if ($this->keepDateInStatistics($coursDate)) {
                    $listeCoursDate[] = $mcd->fk_cours_date;
                }
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
            
            if (in_array($clientCoursDate->fkCoursDate->fkCours->fk_type, Yii::$app->params['coursPlanifieS'])) {
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

            $isInscrit = ClientsHasCours::findOne(['fk_personne' => $model->personne_id, 'fk_cours' => $clientCoursDate->fkCoursDate->fk_cours]);
            if (isset($isInscrit->fk_statut)) {
                $dataCoursDate[$cle]['statutPartID'] = $isInscrit->fk_statut;
            }
        }
        $coursDataProvider = new ArrayDataProvider([
            'allModels' => $dataCoursDate,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        
        $coursNot = Cours::find()->where(['not in', 'cours_id', $listeCours])->andWhere(['fk_statut' => [Yii::$app->params['coursActif']]])->all();
        $dataCours = [];
        foreach ($coursNot as $c) {
            if (in_array($c->fk_type, Yii::$app->params['coursPlanifieS'])) {
                $dataCours[$c->fkType->nom][$c->cours_id.'|'.$c->fk_type] = $c->fkNom->nom.' '.$c->fkNiveau->nom.' '.$c->session.' '.$c->fkSaison->nom.' '.$c->fkSalle->nom;
            } else {
                foreach ($c->coursDates as $coursDate) {
                    $dataCours[$c->fkType->nom][$coursDate->cours_date_id.'|'.$c->fk_type] =
                            $c->fkNom->nom.' '.
                            $c->fkNiveau->nom.' '.
                            $c->session.' '.
                            (!isset($c->fkSaison) ? 'none' : $c->fkSaison->nom).'-'.
                            $coursDate->date;
                }
            }
        }
        
        $parametre = new Parametres();
        $emails = ['' => Yii::t('app', 'Faire un choix ...')] + $parametre->optsEmail();
        
        return $this->render('view', [
            'alerte' => $alerte,
            'model' => $model,
            'moniteursHasBaremeDataProvider' => $moniteursHasBaremeDataProvider,
            'coursDateDataProvider' => $coursDateDataProvider,
            'dataCours' => $dataCours,
            'coursDataProvider' => $coursDataProvider,
            'parametre' => $parametre,
            'emails' => $emails,
            'activeTab' => $tab,
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
            if (isset(Yii::$app->request->post()['Personnes']['fk_langues'])) {
                $model->fk_langues = Yii::$app->request->post()['Personnes']['fk_langues'];
            }
            
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
                    if (!$addInterlocuteur->save(false)) {
                        throw new Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des interlocuteur(s).'));
                    }
                }

                $transaction->commit();
                return $this->redirect(['view', 'id' => $model->personne_id]);
            } catch (Exception $e) {
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
     * @param string $from
     * @return mixed
     */
    public function actionUpdate($id, $from = '')
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            if (isset(Yii::$app->request->post()['Personnes']['fk_langues'])) {
                $model->fk_langues = Yii::$app->request->post()['Personnes']['fk_langues'];
            }
            
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde de la personne.'));
                }
                
                $interlocuteurs = (isset($post['list_interlocuteurs'])) ? $post['list_interlocuteurs'] : [];
                PersonnesHasInterlocuteurs::deleteAll('fk_personne = ' . $model->personne_id);
                foreach ($interlocuteurs as $interlocuteur_id) {
                    $addInterlocuteur = new PersonnesHasInterlocuteurs();
                    $addInterlocuteur->fk_personne = $model->personne_id;
                    $addInterlocuteur->fk_interlocuteur = $interlocuteur_id;
                    if (!$addInterlocuteur->save(false)) {
                        throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde du/des interlocuteur(s).'));
                    }
                }

                $transaction->commit();

                $url = json_decode($from);
                if (is_object($url)) {
                    return $this->redirect([$url->url]);
                }
                return $this->redirect(['view', 'id' => $model->personne_id]);
            } catch (Exception $e) {
                $transaction->rollBack();
            }
        }
        
        $myInterlocuteurs = PersonnesHasInterlocuteurs::find()->where(['fk_personne' => $model->personne_id])->all();
        foreach ($myInterlocuteurs as $interlocuteur) {
            $selectedInterlocuteurs[] = $interlocuteur->fk_interlocuteur;
        }
        $modelInterlocuteurs = Personnes::find()->where(['!=', 'personne_id', $model->personne_id])->orderBy('nom, prenom')->all();
        foreach ($modelInterlocuteurs as $interlocuteur) {
            $dataInterlocuteurs[$interlocuteur->personne_id] = $interlocuteur->NomPrenom;
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
     * @param $coursDate
     * @return bool
     */
    protected function keepDateInStatistics($coursDate) {
        $keep = false;
        if (Yii::$app->params['coursUnique'] == $coursDate->fkCours->fk_type) {
            $hasClient = $coursDate->clientsHasCoursDate;
            if (count($hasClient) > 0) {
                $keep = true;
            }
        } else {
            $keep = true;
        }
        return $keep;
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

    /**
     * @param $mcd
     * @return array
     */
    private function getBaremeID($mcd, $default): int
    {
        if (null != $mcd->fk_bareme) {
            $key = $mcd->fk_bareme;
        } else {
            $isBaremeSet = $mcd->fkMoniteur->getMoniteursHasBaremeFromDate(date('Y-m-d', strtotime($mcd->fkCoursDate->date)));
            $key = (!is_null($isBaremeSet) ? $isBaremeSet->fk_bareme : $default);
        }
        return (!is_null($key) ? $key : -1);
    }
}
