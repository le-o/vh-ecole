<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "clients_online".
 *
 * @property integer $client_online_id
 * @property integer $fk_parent
 * @property integer $fk_cours
 * @property string $nom
 * @property string $prenom
 * @property string $adresse
 * @property string $npa
 * @property string $localite
 * @property string $telephone
 * @property string $email
 * @property string $date_naissance
 * @property string $informations
 * @property string $date_inscription
 * @property integer $is_actif
 */
class ClientsOnline extends \yii\db\ActiveRecord
{
    public $iagree;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'clients_online';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_parent', 'fk_cours', 'is_actif'], 'integer'],
            [['fk_cours', 'adresse', 'npa', 'localite', 'telephone', 'email'], 'required'],
            [['date_naissance', 'date_inscription'], 'safe'],
            [['informations'], 'string'],
            [['nom', 'prenom'], 'string', 'max' => 60],
            [['adresse', 'localite', 'email'], 'string', 'max' => 100],
            [['npa'], 'string', 'max' => 5],
            [['telephone'], 'string', 'max' => 20],
            ['iagree', 'compare', 'operator' => '==', 'compareValue' => true, 'message' => Yii::t('app', 'Vous devez accepter les conditions générales')],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'client_online_id' => Yii::t('app', 'Client Online ID'),
            'fk_parent' => Yii::t('app', 'Fk Parent'),
            'fk_cours' => Yii::t('app', 'Fk Cours'),
            'nom' => Yii::t('app', 'Nom'),
            'prenom' => Yii::t('app', 'Prenom'),
            'adresse' => Yii::t('app', 'Adresse'),
            'npa' => Yii::t('app', 'Npa'),
            'localite' => Yii::t('app', 'Localite'),
            'telephone' => Yii::t('app', 'Telephone'),
            'email' => Yii::t('app', 'Email'),
            'date_naissance' => Yii::t('app', 'Date Naissance'),
            'informations' => Yii::t('app', 'Informations'),
            'date_inscription' => Yii::t('app', 'Date Inscription'),
            'is_actif' => Yii::t('app', 'Transformé en client?'),
            'iagree' => Yii::t('app', 'En cochant cette case je déclare avoir lu et accepté les conditions d\'inscription et d\'annulation indiquées au bas de cette page'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->date_naissance = ($this->date_naissance == '0000-00-00') ? '' : date('d.m.Y', strtotime($this->date_naissance));
        $this->date_inscription = date('d.m.Y H:i:s', strtotime($this->date_inscription));
        parent::afterFind();
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->date_naissance = date('Y-m-d', strtotime($this->date_naissance));
            $this->date_inscription = date('Y-m-d H:i:s');
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
     * @return \yii\db\ActiveQuery
     */
    public function getFkParametre()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_cours']);
    }
}
