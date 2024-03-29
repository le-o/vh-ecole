<?php

namespace app\models;

use Yii;
use \DateTime;

/**
 * This is the model class for table "personnes".
 *
 * @property integer $personne_id
 * @property integer $fk_statut
 * @property integer $fk_type
 * @property integer $fk_formation
 * @property string $noclient_cf
 * @property string $societe
 * @property string $nom
 * @property string $prenom
 * @property string $adresse1
 * @property string $adresse2
 * @property string $npa
 * @property string $localite
 * @property string $telephone
 * @property string $telephone2
 * @property string $email
 * @property string $email2
 * @property string $date_naissance
 * @property string $informations
 * @property string $carteclient_cf
 * @property string $categorie3_cf
 * @property string $soldefacture_cf
 *
 * @property Parametres $fkStatut
 * @property Parametres $fkType
 * @property Parametres $fkFormation
 */
class Personnes extends \yii\db\ActiveRecord
{
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
            [['fk_statut', 'fk_type', 'fk_formation'], 'integer'],
            [['date_naissance'], 'safe'],
            [['informations'], 'string'],
            [['noclient_cf'], 'string', 'max' => 10],
            [['societe', 'nom', 'prenom'], 'string', 'max' => 60],
            [['adresse1', 'adresse2', 'localite', 'email', 'email2'], 'string', 'max' => 100],
            [['npa'], 'string', 'max' => 5],
            [['telephone', 'telephone2'], 'string', 'max' => 20],
            [['carteclient_cf', 'categorie3_cf', 'soldefacture_cf'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'personne_id' => Yii::t('app', 'Personne ID'),
            'fk_statut' => Yii::t('app', 'Statut'),
            'fk_type' => Yii::t('app', 'Type'),
            'fk_formation' => Yii::t('app', 'Niveau formation'),
            'noclient_cf' => Yii::t('app', 'Num client CASHFLOW'),
            'societe' => Yii::t('app', 'Societe'),
            'nom' => Yii::t('app', 'Nom'),
            'prenom' => Yii::t('app', 'Prenom'),
            'adresse1' => Yii::t('app', 'Adresse 1'),
            'adresse2' => Yii::t('app', 'Adresse 2'),
            'npa' => Yii::t('app', 'Npa'),
            'localite' => Yii::t('app', 'Localite'),
            'telephone' => Yii::t('app', 'Telephone'),
            'telephone2' => Yii::t('app', 'Telephone 2'),
            'email' => Yii::t('app', 'Email'),
            'email2' => Yii::t('app', 'Email 2'),
            'date_naissance' => Yii::t('app', 'Date Naissance'),
            'informations' => Yii::t('app', 'Informations'),
            'carteclient_cf' => Yii::t('app', 'Carte client CASHFLOW'),
            'categorie3_cf' => Yii::t('app', 'Catégorie CASHFLOW'),
            'soldefacture_cf' => Yii::t('app', 'Solde facture CASHFLOW'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->date_naissance = ($this->date_naissance == '0000-00-00') ? '' : date('d.m.Y', strtotime($this->date_naissance));
        parent::afterFind();
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->date_naissance = ($this->date_naissance == '') ? 'null' : date('Y-m-d', strtotime($this->date_naissance));
            return true;
        } else {
            return false;
        }
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
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_statut']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkType()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_type']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkFormation()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_formation']);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCours()
    {
        return $this->hasMany(ClientsHasCours::className(), ['fk_personne' => 'personne_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientsHasCoursDate()
    {
        return $this->hasMany(ClientsHasCoursDate::className(), ['fk_personne' => 'personne_id']);
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
    public function getMoniteurHasCoursDate()
    {
        return $this->hasMany(CoursHasMoniteurs::className(), ['fk_moniteur' => 'personne_id']);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonneHasInterlocuteurs()
    {
        return $this->hasMany(PersonnesHasInterlocuteurs::className(), ['fk_personne' => 'personne_id']);
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
}
