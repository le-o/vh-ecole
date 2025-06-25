<?php

namespace app\models;

use Yii;
use \DateTime;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "personnes".
 *
 * @property integer $personne_id
 * @property integer $fk_statut
 * @property integer $fk_finance
 * @property integer $fk_type
 * @property integer $fk_formation
 * @property integer $fk_langues
 * @property integer $fk_langue_mat
 * @property string $societe
 * @property string $suivi_client
 * @property string $nom
 * @property string $prenom
 * @property string $adresse1
 * @property string $numeroRue
 * @property string $adresse2
 * @property string $npa
 * @property string $localite
 * @property integer $fk_pays
 * @property integer $fk_nationalite
 * @property string $telephone
 * @property string $telephone2
 * @property string $email
 * @property string $date_naissance
 * @property string $no_avs
 * @property integer $fk_sexe
 * @property string $informations
 * @property string $complement_langue
 * @property integer $fk_salle_admin
 *
 * @property Parametres $fkStatut
 * @property Parametres $fkFinance
 * @property Parametres $fkType
 * @property Parametres $fkFormation
 * @property MoniteursHasBareme $moniteursHasBareme
 */
class Personnes extends \yii\db\ActiveRecord
{
    // Stores old attributes on afterFind() so we can compare
    // against them before/after save
    protected $oldAttributes;
    
    public $statutPart;
    public $statutPartID;

    public $nopersonnel;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'personnes';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_statut', 'fk_type', 'nom', 'prenom', 'telephone', 'email'], 'required'],
            [['fk_statut', 'fk_finance', 'fk_type', 'fk_formation', 'fk_langue_mat', 'fk_pays', 'fk_nationalite', 'fk_sexe', 'fk_salle_admin'], 'integer'],
            [['date_naissance'], 'safe'],
            [['informations'], 'string'],
            [['no_avs'], 'string', 'max' => 16],
            [['no_avs'], 'match', 'pattern' => '/[7][5][6]\\.[\d]{4}[.][\d]{4}[.][\d]{2}$/'],
            [['societe', 'nom', 'prenom'], 'string', 'max' => 60],
            [['suivi_client', 'complement_langue'], 'string', 'max' => 250],
            [['adresse1', 'adresse2', 'localite', 'email'], 'string', 'max' => 100],
            [['numeroRue'], 'string', 'max' => 10],
            [['npa'], 'string', 'max' => 5],
            [['telephone', 'telephone2'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'personne_id' => Yii::t('app', 'Personne ID'),
            'nopersonnel' => Yii::t('app', 'No personnel'),
            'fk_statut' => Yii::t('app', 'Statut'),
            'fk_finance' => Yii::t('app', 'Finances'),
            'fk_type' => Yii::t('app', 'Type'),
            'fk_formation' => Yii::t('app', 'Barème moniteur'),
            'fk_langues' => Yii::t('app', 'Langues parlées'),
            'fk_langue_mat' => Yii::t('app', 'Langue maternelle'),
            'fkLangueMat.nom' => Yii::t('app', 'Langue maternelle'),
            'societe' => Yii::t('app', 'Societe'),
            'suivi_client' => Yii::t('app', 'Suivi client'),
            'nom' => Yii::t('app', 'Nom'),
            'prenom' => Yii::t('app', 'Prenom'),
            'adresse1' => Yii::t('app', 'Adresse'),
            'numeroRue' => Yii::t('app', 'Numéro'),
            'adresse2' => Yii::t('app', 'Adresse (complément)'),
            'npa' => Yii::t('app', 'Npa'),
            'localite' => Yii::t('app', 'Localite'),
            'fk_pays' => Yii::t('app', 'Pays'),
            'fkPays.nom' => Yii::t('app', 'Pays'),
            'fk_nationalite' => Yii::t('app', 'Nationalité'),
            'fkNationalite.nom' => Yii::t('app', 'Nationalité'),
            'telephone' => Yii::t('app', 'Telephone'),
            'telephone2' => Yii::t('app', 'Telephone pro'),
            'email' => Yii::t('app', 'Email'),
            'date_naissance' => Yii::t('app', 'Date Naissance'),
            'no_avs' => Yii::t('app', 'No AVS'),
            'fk_sexe' => Yii::t('app', 'Sexe'),
            'fkSexe.nom' => Yii::t('app', 'Sexe'),
            'informations' => Yii::t('app', 'Informations'),
            'fk_salle_admin' => Yii::t('app', 'Fk Salle Admin'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->date_naissance = (is_null($this->date_naissance) || $this->date_naissance == '0000-00-00') ? '' : date('d.m.Y', strtotime($this->date_naissance));
        $this->fk_langues = json_decode($this->fk_langues ?? '');

        $this->setNopersonnel();

        $this->oldAttributes = $this->attributes;
        parent::afterFind();
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->fk_langues = json_encode($this->fk_langues);
            $this->date_naissance = ($this->date_naissance == '') ? null : date('Y-m-d', strtotime($this->date_naissance));
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->fk_langues = json_decode($this->fk_langues);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Gets query for [[Moniteur]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMoniteurInfo()
    {
        return $this->hasOne(Moniteurs::class, ['fk_personne' => 'personne_id']);
    }
    
    /**
     * @return Personne nom prénom
     */
    public function getNomPrenom()
    {
        return $this->nom.' '.$this->prenom;
    }
    
    /**
     * @return Personne âge calculé
     */
    public function getAge()
    {
        $date = new DateTime($this->date_naissance);
        $now = new DateTime();
        $interval = $now->diff($date);
        return $interval->y;
    }
    
    /**
     * @return array options for etat_commande drop-down
     */
    public function optsType()
    {
	    $myParametres = new Parametres();
	    return $myParametres->optsType();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkStatut()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_statut']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkFinance()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_finance']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkType()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_type']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkFormation()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_formation']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkLangueMat()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_langue_mat']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkPays()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_pays']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkNationalite()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_nationalite']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkSexe()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_sexe']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkSalleadmin()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_salle_admin']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkLangues()
    {
        return $this->hasMany(Parametres::class, ['parametre_id' => 'fk_langues']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkLanguesNoms()
    {
        $langueNom = [];
        $langues = $this->hasMany(Parametres::class, ['parametre_id' => 'fk_langues']);
        foreach ($langues->all() as $l) {
            $langueNom[] = $l->nom;
        }
        return implode(', ', $langueNom);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMoniteursHasBareme()
    {
        return $this->hasMany(MoniteursHasBareme::class, ['fk_personne' => 'personne_id'])
            ->orderBy('date_debut DESC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCours()
    {
        return $this->hasMany(ClientsHasCours::class, ['fk_personne' => 'personne_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientsHasCours()
    {
        return $this->hasMany(ClientsHasCours::class, ['fk_personne' => 'personne_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientsHasCoursDate()
    {
        return $this->hasMany(ClientsHasCoursDate::class, ['fk_personne' => 'personne_id']);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientsHasCoursDateActif()
    {
        
        $query = $this->getClientsHasCoursDate();
        return $query->all();
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientsHasOneCoursDate($fk_cours_date)
    {
        return ClientsHasCoursDate::findOne(['fk_personne' => $this->personne_id, 'fk_cours_date' => $fk_cours_date]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMoniteursHasBaremeFromDate($date)
    {
        return MoniteursHasBareme::find()
            ->where(['fk_personne' => $this->personne_id])
            ->andWhere(':mydate BETWEEN date_debut AND date_fin',
                [':mydate' => date('Y-m-d', strtotime($date))]
            )
            ->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurrentBareme()
    {
        return $this->getMoniteursHasBaremeFromDate(date('Y-m-d'));
    }

    public function getLetterBaremeFromDate($date)
    {
        $moniteurHasBareme = $this->getMoniteursHasBaremeFromDate($date);
        return (!is_null($moniteurHasBareme)
            ? '<sup>' . $moniteurHasBareme->fkBareme->info_special . '</sup>'
            : ''
        );
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMoniteurHasCoursDate()
    {
        return $this->hasMany(CoursHasMoniteurs::class, ['fk_moniteur' => 'personne_id']);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonneHasInterlocuteurs()
    {
        return $this->hasMany(PersonnesHasInterlocuteurs::class, ['fk_personne' => 'personne_id']);
    }
    
    /**
     * @return string
     */
    public function getInterlocuteurs()
    {
        $interlocuteurs = [];
        $myInterlocuteurs = PersonnesHasInterlocuteurs::findAll(['fk_personne' => $this->personne_id]);
        foreach ($myInterlocuteurs as $interlocuteur) {
            $interlocuteurs[] = \yii\helpers\Html::a($interlocuteur->fkInterlocuteur->NomPrenom, \yii\helpers\Url::to(['/personnes/view', 'id' => $interlocuteur->fk_interlocuteur]));
        }
        return implode(', ', $interlocuteurs);
    }
    
    /**
     * @return string
     */
    public function getIsInterlocuteursFrom()
    {
        $interlocuteurs = [];
        $myInterlocuteurs = PersonnesHasInterlocuteurs::findAll(['fk_interlocuteur' => $this->personne_id]);
        foreach ($myInterlocuteurs as $interlocuteur) {
            $interlocuteurs[] = \yii\helpers\Html::a($interlocuteur->fkPersonne->NomPrenom, \yii\helpers\Url::to(['/personnes/view', 'id' => $interlocuteur->fk_personne]));
        }
        return implode('<br />', $interlocuteurs);
    }
    
    /**
     * 
     * @param array $excludePart
     * @return array
     */
    public static function getClientsNotInCours($excludePart)
    {
        $clients = self::find()->where(['not in', 'personne_id', $excludePart])->orderBy('nom, prenom')->all();
        foreach ($clients as $c) {
            $dataClients[$c->fkType->nom][$c->personne_id] = ($c->societe != '') ? $c->societe.' '.$c->NomPrenom : $c->NomPrenom;
        }
        return $dataClients;
    }

    /**
     *
     * @return array
     */
    public static function getMoniteurs()
    {
        $moniteurs = self::find()
            ->where(['fk_type' => Yii::$app->params['typeEncadrantActif']])
            ->orderBy('nom, prenom')
            ->all();
        return ArrayHelper::map($moniteurs, 'personne_id', 'NomPrenom');
    }

    /**
     * @return void
     */
    private function setNopersonnel(): void
    {
        $length = 9 - strlen($this->fk_salle_admin . $this->personne_id);
        $this->nopersonnel = $this->fk_salle_admin . str_repeat('0', $length) . $this->personne_id;
    }
}
