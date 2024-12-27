<?php

namespace app\controllers;

use app\models\CoursDate;
use app\models\CoursHasMoniteurs;
use app\models\Personnes;
use app\models\PersonnesHasInterlocuteurs;
use Yii;
use app\models\ClientsOnline;
use app\models\ClientsOnlineSearch;
use app\models\Cours;
use app\models\Model;
use app\models\Parametres;
use yii\web\NotFoundHttpException;
use yii\web\Exception;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use xstreamka\mobiledetect\Device;

/**
 * ClientsOnlineController implements the CRUD actions for ClientsOnline model.
 */
class ClientsOnlineController extends CommonController
{
    
    public $freeAccessActions = ['create', 'findanniversaire', 'createanniversaire', 'depnbparticipants'];
    
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
            'ghost-access'=> [
                'class' => 'leo\modules\UserManagement\components\GhostAccessControl',
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = ($action->id !== "findanniversaire"); // <-- here
        return parent::beforeAction($action);
    }

    /**
     * Lists all ClientsOnline models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ClientsOnlineSearch();
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
        $model = $this->findModel($id);
        if ($model->is_actif) {
            $doublons = Personnes::find()
                    ->where(['nom' => $model->nom, 'prenom' => $model->prenom])->all();
        }
        
        return $this->render('view', [
            'model' => $model,
            'doublon' => isset($doublons[0]) ? $doublons[0] : null,
        ]);
    }

    /**
     * Creates a new ClientsOnline model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($cours_id = null, $lang_interface = 'fr-CH', $goodlooking = false)
    {
        $this->layout = (!$goodlooking) ? "main_1" : "main_1_logo";
        Yii::$app->language = $lang_interface;

        $model = new ClientsOnline();
        $modelsClient = [new ClientsOnline];
        if ($cours_id !== null && $cours_id !== '') {
            $modelCours = Cours::findOne($cours_id);
            if (Yii::$app->params['coursUnique'] == $modelCours->fk_type) {
                return $this->redirect(['clients-online/findanniversaire',
                    'lang_interface' => $lang_interface,
                    'salleID' => $modelCours->fk_salle,
                    'ident' => $cours_id,
                    'goodlooking' => $goodlooking]
                );
            }
        }

        if ($model->load(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            $model->is_actif = 1;
            $model->fk_cours_nom = (isset($modelCours)) ? $modelCours->fk_nom : $model->fk_cours;
            
            // gestion des options supp
            if (isset($post['offre_supp']) && in_array($model->fk_cours_nom, Yii::$app->params['nomsCoursEnfant'])) {
                if ($post['offre_supp'] == 'cours_essai') {
                    $model->informations .= '
                        + '.Yii::t('app', 'J\'aimerais que mon enfant essaie avant de l\'inscrire pour la saison et je souhaite être contacté à ce sujet');
                } elseif ($post['offre_supp'] == 'pmt_complet') {
                    $model->informations .= '
                        + '.Yii::t('app', 'J\'inscris mon enfant pour la saison et je paie le montant du cours en un seul versement');
                } elseif ($post['offre_supp'] == 'pmt_tranche') {
                    $model->informations .= '
                        + '.Yii::t('app', 'J\'inscris mon enfant pour la saison et je paie le montant du cours en plusieurs versements (+ CHF 40 de frais administratifs)');
                }
            }

            $clientAuto = false;
            if (isset($modelCours)) {
                $model->fk_cours = $cours_id;
                $model->informations .= '
                    INFO: '.Yii::t('app', 'Le client souhaite être inscrit au cours suivant').': '.
                    $modelCours->cours_id.'-'.$modelCours->fkNom->nom.' '.$modelCours->fkNiveau->nom;
                $model->informations .= (isset($modelCours->fkSemestre)) ? ' - ' . $modelCours->fkSemestre->nom : ' - ';
                $model->informations .= $modelCours->fkSaison->nom.' '.$modelCours->session;
                $model->informations .= '<br />'.Yii::t('app', 'Salle concernée') . ': ' . $modelCours->fkSalle->nom;
                if (Yii::$app->params['coursRegulie'] == $modelCours->fk_type) {
                    $clientAuto = true;
                }
            } else {
                $model->fk_cours = null;
            }

            $modelsClient = Model::createMultiple(ClientsOnline::classname(), [], 'client_online_id');
            Model::loadMultiple($modelsClient, $post);
            
            if ($model->validate()) {
                $clientDirect = [];

                if (isset($modelCours)) {
                    $modelDate = CoursDate::find()
                        ->where(['=', 'fk_cours', $modelCours->cours_id])
                        ->andWhere(['>=', 'date', date('Y-m-d')])
                        ->all();
                    // si pas de cours pour l'inscription, on reste en inscription standard
                    // si max du nombre de participants atteint, on reste en inscription standard
                    if (0 == count($modelDate) || $modelCours->getNombreClientsInscrits() >= $modelCours->participant_max) {
                        $clientAuto = false;
                    }
                }
                if ($clientAuto) {
                    $clientDirect[] = $this->setPersonneAttribute($model);
                    $model->is_actif = 0;
                }
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save()) {
                        throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde de la personne.'));
                    }
                    
                    // tout est ok pour le client principal, on sauve les clients liés
                    foreach ($modelsClient as $client) {
                        if ($client->nom != '' && $client->prenom != '') {
                            if (empty($client->no_avs)) {
                                throw new \Exception(Yii::t('app', 'Le no AVS des enfants est obligatoire.'));
                            }

                            $client->fk_cours_nom = $model->fk_cours_nom;
                            $client->fk_cours = $model->fk_cours;
                            $client->fk_parent = $model->client_online_id;
                            $client->adresse = $model->adresse;
                            $client->numeroRue = $model->numeroRue;
                            $client->npa = $model->npa;
                            $client->localite = $model->localite;
                            $client->fk_pays = $model->fk_pays;
                            $client->telephone = $model->telephone;
                            $client->email = $model->email;
                            $client->is_actif = 1;

                            if ($clientAuto) {
                                $clientDirect[] = $this->setPersonneAttribute($client);
                                $client->is_actif = 0;
                            }

                            if (!$client->save()) {
                                throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde du client lié.'));
                            }
                        }
                    }
                    if ($clientAuto) {
                        $inscritCours = (1 < count($clientDirect)) ? false : true;
                        $parentID = null;

                        foreach ($clientDirect as $cd) {
                            // si la personne existe déjà, on ne fait pas de doublon
                            $isPersonne = Personnes::find()->where(['nom' => $cd->nom, 'prenom' => $cd->prenom])->one();
                            if (null == $isPersonne) {
                                $cd->save();
                                $existeID = $cd->personne_id;
                            } else {
                                $existeID = $isPersonne->personne_id;
                                // on modifie le statut pour permettre le suivi
                                $isPersonne->fk_statut = Yii::$app->params['persStatutStandby'];
                                if (!in_array($isPersonne->getOldAttribute('fk_statut'), [
                                    Yii::$app->params['persStatutInscrit'],
                                    Yii::$app->params['persStatutStandby'],
                                ])) {
                                    $oldStatut = Parametres::findOne($isPersonne->getOldAttribute('fk_statut'));
                                    $isPersonne->suivi_client .= "\n" . Yii::t(
                                        'app',
                                        'Attention, statut du client avant fusion du {date} : {oldStatut}.',
                                        [
                                            'date' => date('d.m.Y'),
                                            'oldStatut' => $oldStatut->nom,
                                        ]
                                    );
                                }

                                // on profite de traiter l'adresse si pas en 2 parties ou si modification
                                if ($cd->adresse1 != $isPersonne->adresse1) {
                                    $isPersonne->adresse1 = $cd->adresse1;
                                    $isPersonne->numeroRue = $cd->numeroRue;
                                    $isPersonne->npa = $cd->npa;
                                    $isPersonne->localite = $cd->localite;
                                }
                                $isPersonne->fk_pays = $cd->fk_pays;
                                $isPersonne->no_avs = $cd->no_avs;
                                $isPersonne->fk_nationalite = $cd->fk_nationalite;
                                $isPersonne->fk_sexe = $cd->fk_sexe;
                                $isPersonne->fk_langue_mat = $cd->fk_langue_mat;

                                $newInfos = $cd->informations;
                                $newInfos .= "\r\n\r\n" . $isPersonne->informations;
                                $isPersonne->informations = $newInfos;
                                $isPersonne->save();
                            }

                            // si on a un interlocuteur, on ne sauve pas l'inscription au cours, mais juste la personne
                            if (!$inscritCours) {
                                $inscritCours = true;
                                $parentID = $existeID;
                                continue;
                            }
                            // si on a un interlocuteur, on sauve le lien entre les personnes
                            if (!is_null($parentID)) {
                                $hasInterloc = PersonnesHasInterlocuteurs::find()->where(['fk_personne' => $existeID, 'fk_interlocuteur' => $parentID])->one();
                                if (null == $hasInterloc) {
                                    $i = new PersonnesHasInterlocuteurs;
                                    $i->fk_personne = $existeID;
                                    $i->fk_interlocuteur = $parentID;
                                    $i->save();
                                }
                            }
                            // on sauve l'inscription au cours
                            if (!empty($modelDate)) {
                                $this->addClientToCours($modelDate, $existeID, $modelCours->cours_id);
                                $sendEmailTo[] = $existeID;
                            }
                        }
                    }
                    $transaction->commit();

                    // on traite le mail après le commit, comme cela si l'envoi de l'email plante, on a quand même
                    // enregistré les données dans la base
                    if ($clientAuto && Yii::$app->params['emailConfirmationInscription']) {
                        $emailBrut = \app\models\Parametres::findOne(Yii::$app->params['texteEmailConfirmationOnline'][Yii::$app->language]);
                        $contenu['nom'] = $emailBrut->nom;
                        $contenu['valeur'] = $emailBrut->valeur;
                        $contenu['keyForMail'] = $modelCours->cours_id;
                        foreach ($sendEmailTo as $personneID) {
                            $contenu['personne_id'] = $personneID;
                            $this->actionEmail($contenu, [$model->email], true);
                        }
                    } else {
                        $contenu = \app\models\Parametres::findOne(Yii::$app->params['texteEmailInscriptionOnline'][Yii::$app->language]);
                        $this->actionEmail($contenu, [$model->email], true);
                    }

                    return $this->render('confirmation');
                } catch (\Exception $e) {
                    Yii::$app->session->setFlash('alerte', ['type'=>'danger', 'info'=>$e->getMessage()], false);
                    $transaction->rollBack();
                }
            } else {
                Yii::$app->session->setFlash('alerte', ['type'=>'danger', 'info'=>Yii::t('app', 'Une erreur est survenue lors de l\'enregistrement.')], false);
            }
        }
        
        if (isset($modelCours)) {
            $dataCours[$modelCours->cours_id] = $modelCours->fkNom->nom . ' ' . Yii::t('app', $modelCours->fkNiveau->nom) . ' ' . $modelCours->session . ' ' . $modelCours->fkSalle->nom;
            $dataCours[$modelCours->cours_id] .= (isset($modelCours->fkSemestre)) ? ' - '.$modelCours->fkSemestre->nom : '';
            $model->fk_cours = $cours_id;
            $selectedCours = [$cours_id];
        } else {
            $query = Cours::find()->distinct()->JoinWith(['fkNom'])->orderBy('parametres.tri')->where(['fk_statut' => Yii::$app->params['coursActif'], 'is_publie' => true]);
            $query->andWhere(['OR', 'date_fin_validite IS NULL', ['>=', 'date_fin_validite', 'today()']]);
            $modelCours = $query->all();
            foreach ($modelCours as $cours) {
                $dataCours[$cours->fkNom->parametre_id] = $cours->fkNom->nom;
            }
            $selectedCours = [];
        }
        
        return $this->render('create', [
            'model' => $model,
            'modelsClient' => $modelsClient,
            'dataCours' => $dataCours,
            'selectedCours' => $selectedCours,
            'params' => new Parametres,
            'displayForm' => '_form',
        ]);
    }

    /**
     * @param string $lang_interface
     * @return string
     */
    public function actionFindanniversaire($lang_interface = 'fr-CH', $salleID = 214, $ident = null, $goodlooking = false)
    {
        $this->layout = (!$goodlooking) ? "main_1" : "main_1_logo";
        Yii::$app->language = $lang_interface;

        if (!is_null($ident)) {
            $modelCours = Cours::findOne($ident);
            $salleID = $modelCours->fk_salle;
        }

        $salleID = (Yii::$app->request->post()) ? Yii::$app->request->post()['Parametres']['parametre_id'] : $salleID;
        $model = Parametres::findOne($salleID);
        Yii::$app->language = (isset(Yii::$app->params['interface_language_label'][$model->fk_langue]) ?
            Yii::$app->params['interface_language_label'][$model->fk_langue] :
            $lang_interface);

        // set la valeur de la date début du calendrier
        if (null === Yii::$app->session->get('anni-cal-debut')) {
            Yii::$app->session->set('anni-cal-debut', date('Y-m-d'));
        }
        // et par défaut, sur mobile, en mode liste
        if (Device::$isMobile) {
            $this->layout = "main_full_logo";
            Yii::$app->session->set('anni-cal-view', 'listMonth');
        }
        if (null === Yii::$app->session->get('anni-cal-view')) {
            Yii::$app->session->set('anni-cal-view', 'agendaWeek');
        }

        return $this->render('anniversaire', [
            'model' => $model,
            'salleID' => $salleID,
            'ident' => $ident,
        ]);
    }

    /**
     * Creates a new ClientsOnline model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateanniversaire($ident = null, $lang_interface = 'fr-CH', $free = false)
    {
        $this->layout = (!$free) ? "main_1" : "main_1_logo";
        Yii::$app->language = $lang_interface;

        $model = new ClientsOnline();
        $model->setScenario('anniversaire');
        $modelsClient = [new ClientsOnline];

        $selectedCours = [];
        if (!$free) {
            $modelCoursDate = CoursDate::findOne($ident);
            $modelCours = $modelCoursDate->fkCours;
        } else {
            $modelCoursDate = new CoursDate;
            if (isset(Yii::$app->request->post()['anni-cours'])) {
                $modelCours = Cours::findOne(Yii::$app->request->post()['anni-cours']);
            } elseif (null !== $ident) {
                $modelCours = Cours::findOne($ident);
                $selectedCours = [$modelCours->cours_id];
            } else {
                $modelCours = new Cours;
            }
        }

        if (Yii::$app->params['baltschieder'] == $modelCours->fk_salle) {
            Yii::$app->language = 'de-CH';
        }

        if ($model->load(Yii::$app->request->post())) {
            $model->is_actif = 1;
            $model->fk_cours_nom = $modelCours->fk_nom;
            $model->fk_cours = $modelCours->cours_id;

            // Set de la date selon la saisie
            $date = (!$free ? $modelCoursDate->date : Yii::$app->request->post()['anni-date']);
            $heure = (!$free ? $modelCoursDate->heure_debut : Yii::$app->request->post()['anni-heure']);

            $infoAnniversaire = $model->informations . '
********************* INFO ANNIVERSAIRE *********************
* ' . Yii::t('app', 'Prénom de l\'enfant') . ' : ' . $model->prenom_enfant . '
* ' . Yii::t('app', 'Date de naissance de l\'enfant') . ' : ' . $model->date_naissance_enfant . '
* ' . Yii::t('app', 'Age moyen des enfants') . ' : ' . $model->agemoyen . '
* ' . Yii::t('app', 'Nombre de participant') . ' : ' . $model->nbparticipant . '
*
* ' . Yii::t('app', 'Date choisie') . ' : ' . $date . ' ' . $heure;

            if ($model->validate() && isset($model->inscriptionRules[$model->agemoyen][$model->nbparticipant])) {
                $clientDirect = [];

                if (in_array($model->fk_cours_nom, Yii::$app->params['anniversaireAventure'])) {
                    if (240 == $model->fk_cours_nom) {
                        $rule = (isset($model->inscriptionRules[$model->agemoyen . '-aventure-' . $model->fk_cours_nom])
                            ? $model->inscriptionRules[$model->agemoyen . '-aventure-' . $model->fk_cours_nom][$model->nbparticipant]
                            : $model->inscriptionRules[$model->agemoyen][$model->nbparticipant]
                        );
                    } else {
                        $rule = (isset($model->inscriptionRules[$model->agemoyen . '-aventure'])
                            ? $model->inscriptionRules[$model->agemoyen . '-aventure'][$model->nbparticipant]
                            : $model->inscriptionRules[$model->agemoyen][$model->nbparticipant]
                        );
                    }
                } else {
                    $rule = $model->inscriptionRules[$model->agemoyen][$model->nbparticipant];
                }
                $inscriptionAuto = !$free && $rule;

                if ($inscriptionAuto) {
                    $clientDirect[] = $this->setPersonneAttribute($model);
                    $model->is_actif = 0;
                } else {
                    $model->informations = $infoAnniversaire;
                }
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save()) {
                        throw new \Exception(Yii::t('app', 'Problème lors de la sauvegarde de la personne.'));
                    }
                    if ($inscriptionAuto) {
                        foreach ($clientDirect as $cd) {
                            // si la personne existe déjà, on ne fait pas de doublon
                            $isPersonne = Personnes::find()->where(['nom' => $cd->nom, 'prenom' => $cd->prenom])->one();
                            if (null == $isPersonne) {
                                $cd->fk_statut = Yii::$app->params['persStatutInscrit'];
                                $cd->fk_type = 1;
                                $cd->save();
                                $existeID = $cd->personne_id;
                            } else {
                                $existeID = $isPersonne->personne_id;
                                if (!in_array($isPersonne->getOldAttribute('fk_statut'), [
                                    Yii::$app->params['persStatutInscrit'],
                                    Yii::$app->params['persStatutStandby'],
                                ])) {
                                    $oldStatut = Parametres::findOne($isPersonne->getOldAttribute('fk_statut'));
                                    $isPersonne->suivi_client .= "\n" . Yii::t(
                                        'app',
                                        'Attention, statut du client avant fusion du {date} : {oldStatut}.',
                                        [
                                            'date' => date('d.m.Y'),
                                            'oldStatut' => $oldStatut->nom,
                                        ]
                                    );
                                }
                                $isPersonne->save();
                            }
                            $modelCoursDate->remarque = $infoAnniversaire;
                            // on sauve l'inscription au cours
                            $this->addClientToCours([$modelCoursDate], $existeID, $modelCours->cours_id);
                            $sendEmailTo[] = $existeID;
                        }
                    }
                    $transaction->commit();

                    // on traite le mail après le commit, comme cela si l'envoi de l'email plante, on a quand même
                    // enregistré les données dans la base
                    if ($inscriptionAuto) {
                        $emailBrut = \app\models\Parametres::findOne(Yii::$app->params['texteEmailAutoAnnivOnline'][Yii::$app->language]);
                        $contenu['nom'] = $emailBrut->nom;
                        $contenu['valeur'] = $emailBrut->valeur;
                        $contenu['keyForMail'] = 'd|' . $modelCoursDate->cours_date_id;
                        foreach ($sendEmailTo as $personneID) {
                            $contenu['personne_id'] = $personneID;
                            $this->actionEmail($contenu, [$model->email], true);
                        }

                        // on envoie aussi un email aux moniteurs de la date
                        $moniteurs = [];
                        $modelCoursHasMoniteurs = CoursHasMoniteurs::findAll($modelCoursDate->cours_date_id);
                        foreach ($modelCoursHasMoniteurs as $coursHasMoniteur) {
                            $moniteurs['emails'][] = $coursHasMoniteur->fkMoniteur->email;
                            $moniteurs['noms'][] = $coursHasMoniteur->fkMoniteur->prenom . ' ' . $coursHasMoniteur->fkMoniteur->nom;
                        }
                        if (!empty($moniteurs)) {
                            $contenu = $this->generateMoniteurEmail($modelCoursDate, $moniteurs['noms'], 'birthday');
                            $this->actionEmail($contenu, $moniteurs['emails']);
                        }
                    } else {
                        $contenu = \app\models\Parametres::findOne(Yii::$app->params['texteEmailInfoAnnivOnline'][Yii::$app->language]);
                        $this->actionEmail($contenu, [$model->email], true);
                    }

                    return $this->render('confirmation');
                } catch (\Exception $e) {
                    Yii::$app->session->setFlash('alerte', ['type'=>'danger', 'info'=>$e->getMessage()], false);
                    $transaction->rollBack();
                }
            } else {
                Yii::$app->session->setFlash('alerte', ['type'=>'danger', 'info'=>Yii::t('app', 'Une erreur est survenue lors de l\'enregistrement.')], false);
            }
        }

        if (!$free) {
            $titrePage = Yii::t('app', 'Inscription') . ' ' . $modelCours->fkNom->nom . ' ' . Yii::t('app', 'du_date') . ' ' . $modelCoursDate->date . ' ' . Yii::t('app', 'à_heure') . ' ' . $modelCoursDate->heure_debut;
        } else {
            $titrePage = Yii::t('app', 'Inscription anniversaire : date et heure à choix');
        }

        if (in_array($modelCours->fk_nom, Yii::$app->params['anniversaireLight'])) {
            $choixAge = [
                '2-12' => Yii::t('app', '{nombre} ans', ['nombre' => '2-12']),
                '12+' => Yii::t('app', '{nombre} ans et +', ['nombre' => '12']),
            ];
        } else {
            $choixAge = [
                '5-6' => Yii::t('app', '{nombre} ans', ['nombre' => '5-6']),
                '7-11' => Yii::t('app', '{nombre} ans', ['nombre' => '7-11']),
                '12+' => Yii::t('app', '{nombre} ans et +', ['nombre' => '12']),
            ];
        }

        $query = Cours::find()->distinct()->JoinWith(['fkNom'])->orderBy('parametres.tri')->where(['fk_statut' => Yii::$app->params['coursActif'], 'is_publie' => true]);
        $query->andWhere(['OR', 'date_fin_validite IS NULL', ['>=', 'date_fin_validite', 'today()']]);
        $query->andWhere(['fk_type' => Yii::$app->params['coursUnique']]);
        $modelCoursAnni = $query->all();
        foreach ($modelCoursAnni as $anni) {
            $dataCours[$anni->cours_id] = $anni->fkNom->nom;
        }

        return $this->render('create', [
            'model' => $model,
            'modelsClient' => $modelsClient,
            'dataCours' => $dataCours,
            'selectedCours' => $selectedCours,
            'params' => new Parametres,
            'displayForm' => '_anniversaire',
            'choixAge' => $choixAge,
            'titrePage' => $titrePage,
            'free' => $free,
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
                'selectedCours' => [$model->fk_cours_nom],
                'params' => new Parametres,
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

        $p = $this->setPersonneAttribute($model);

        $clients = ClientsOnline::findAll(['fk_parent' => $model->client_online_id]);
        
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if ($p->save()) {
                foreach ($clients as $client) {
                    $c = new Personnes;
                    $c->fk_statut = Yii::$app->params['persStatutStandby'];
                    $c->fk_type = Yii::$app->params['typeClient'];
                    $c->nom = $client->nom;
                    $c->prenom = $client->prenom;
                    $c->adresse1 = $client->adresse;
                    $c->numeroRue = $client->numeroRue;
                    $c->npa = $client->npa;
                    $c->localite = $client->localite;
                    $c->fk_pays = $client->fk_pays;
                    $c->telephone = $client->telephone;
                    $c->email = $client->email;
                    $c->date_naissance = $client->date_naissance;
                    $c->no_avs = $client->no_avs;
                    $c->fk_sexe = $client->fk_sexe;
                    $c->fk_langue_mat = $client->fk_langue_mat;
                    $c->informations = $p->informations;
                    $c->fk_salle_admin = $p->fk_salle_admin;
                    $c->save();
                    $client->is_actif = 0;
                    $client->save(false);

                    $i = new \app\models\PersonnesHasInterlocuteurs;
                    $i->fk_personne = $c->personne_id;
                    $i->fk_interlocuteur = $p->personne_id;
                    $i->save();
                }
                $model->is_actif = 0;
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
    
    public function actionFusionclient($idClientOnline, $idPersonne)
    {
        $clientOnline = $this->findModel($idClientOnline);
        $personne = Personnes::findOne($idPersonne);
        
        $personne->fk_statut = Yii::$app->params['persStatutStandby'];
        $personne->adresse1 = $clientOnline->adresse;
        $personne->numeroRue = $clientOnline->numeroRue;
        $personne->npa = $clientOnline->npa;
        $personne->localite = $clientOnline->localite;
        $personne->fk_pays = $clientOnline->fk_pays;
        $personne->telephone = $clientOnline->telephone;
        $personne->email = $clientOnline->email;
        $personne->date_naissance = $clientOnline->date_naissance;
        if ($personne->no_avs == '') {
            $personne->no_avs = $clientOnline->no_avs;
        }
        $personne->fk_sexe = $clientOnline->fk_sexe;
        $personne->fk_langue_mat = $clientOnline->fk_langue_mat;
        $newInfos = Yii::t('app', 'Intéressé par le cours') . ' ' . $clientOnline->fkCoursNom->nom;
        $newInfos .= "\r\n" . Yii::t('app', 'Date d\'inscription') . ': ' . $clientOnline->date_inscription;
        if ($clientOnline->informations != '') {
            $newInfos .= "\r\n\r\n" . $clientOnline->informations;
        }
        $newInfos .= "\r\n\r\n" . $personne->informations;
        $personne->informations = $newInfos;
        $personne->fk_salle_admin = ($clientOnline->fkCours) ? $clientOnline->fkCours->fk_salle : Yii::$app->params['salleAdmin'][$clientOnline->fkCoursNom->fk_langue];
        
        $clientsLies = ClientsOnline::findAll(['fk_parent' => $clientOnline->client_online_id]);
        
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if ($personne->save()) {
                $clientsExistes = \app\models\PersonnesHasInterlocuteurs::findAll(['fk_interlocuteur' => $personne->personne_id]);
                $enfants = [];
                foreach ($clientsExistes as $existe) {
                    $enfants[$existe->fkPersonne->nom.$existe->fkPersonne->prenom.$existe->fkPersonne->date_naissance] = ['nom' => $existe->fkPersonne->nom, 'prenom' => $existe->fkPersonne->prenom, 'date_naissance' => $existe->fkPersonne->date_naissance];
                }
                
                foreach ($clientsLies as $client) {
                    $key = $client->nom.$client->prenom.$client->date_naissance;
                    if (!array_key_exists($key, $enfants)) {
                        $c = new Personnes;
                        $c->fk_statut = Yii::$app->params['persStatutStandby'];
                        $c->fk_type = Yii::$app->params['typeClient'];
                        $c->nom = $client->nom;
                        $c->prenom = $client->prenom;
                        $c->adresse1 = $client->adresse;
                        $c->numeroRue = $client->numeroRue;
                        $c->npa = $client->npa;
                        $c->localite = $client->localite;
                        $c->fk_pays = $client->fk_pays;
                        $c->telephone = $client->telephone;
                        $c->email = $client->email;
                        $c->date_naissance = $client->date_naissance;
                        $c->no_avs = $client->no_avs;
                        $c->fk_sexe = $client->fk_sexe;
                        $c->fk_langue_mat = $client->fk_langue_mat;
                        $c->informations = $personne->informations;
                        $c->save();

                        $i = new \app\models\PersonnesHasInterlocuteurs;
                        $i->fk_personne = $c->personne_id;
                        $i->fk_interlocuteur = $personne->personne_id;
                        $i->save();
                    }
                    
                    $client->is_actif = 0;
                    $client->save(false);
                }
                $clientOnline->is_actif = 0;
                $clientOnline->save(false);

                $transaction->commit();
                return $this->redirect(['index']);
            } else {
                throw new \Exception(Yii::t('app', 'Problème lors de la transformation de la personne.'));
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            exit('error lors de la fusion des personnes :(');
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

    /**
     * @param ClientsOnline $model
     * @return Personnes
     */
    private function setPersonneAttribute(ClientsOnline $model)
    {
        $p = new Personnes;
        $p->fk_statut = Yii::$app->params['persStatutStandby'];
        $p->fk_type = Yii::$app->params['typeClient'];
        $p->nom = $model->nom;
        $p->prenom = ($model->prenom != '') ? $model->prenom : 'non renseigné';
        $p->adresse1 = $model->adresse;
        $p->numeroRue = $model->numeroRue;
        $p->npa = $model->npa;
        $p->localite = $model->localite;
        $p->fk_pays = $model->fk_pays;
        $p->telephone = $model->telephone;
        $p->email = $model->email;
        $p->date_naissance = (isset($model->date_naissance)) ? $model->date_naissance : null;
        $p->fk_sexe = $model->fk_sexe;
        $p->no_avs = $model->no_avs;
        $p->fk_langue_mat = $model->fk_langue_mat;
        $p->informations = Yii::t('app', 'Intéressé par le cours') . ' ' . $model->fkCoursNom->nom;
        $p->informations .= "\r\n" . Yii::t('app', 'Date d\'inscription') . ': ' . $model->date_inscription;
        if ($model->informations != '') {
            $p->informations .= "\r\n\r\n" . $model->informations;
        }
        $p->fk_salle_admin = ($model->fkCours) ? $model->fkCours->fk_salle : Yii::$app->params['salleAdmin'][$model->fkCoursNom->fk_langue];
        return $p;
    }

    public function actionDepnbparticipants($lang_interface = 'fr-CH')
    {
        if (isset($_POST['depdrop_parents'])) {
            Yii::$app->language = $lang_interface;
            $parents = $_POST['depdrop_parents'];
            if ($parents != null && is_array($parents)) {
                $model = new ClientsOnline();
                $out = $model->optsPartByAge($parents[0]);

                if (!empty($out)) {
                    return Json::encode($out);
                }
            }
        }
        return Json::encode(['output'=>'', 'selected'=>'']);
    }

}
