<?php

namespace app\models;

use Yii;
use yii\helpers\Json;

/**
 * This is the model class for table "clients_online".
 *
 * @property integer $client_online_id
 * @property integer $fk_parent
 * @property integer $fk_cours_nom
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

    public $prenom_enfant;
    public $date_naissance_enfant;
    public $agemoyen;
    public $nbparticipant;

    // tableau de validation des inscriptions automatique
    public $inscriptionRules = [
        // anniversaire light
        '2-12' => [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
            5 => true,
            6 => true,
            7 => true,
            8 => true,
            9 => true,
            10 => true,
            11 => true,
            12 => true,
            '12+' => false,
        ],
        // anniversaire avec moniteur
        '5-6' => [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
            5 => true,
            6 => true,
            7 => false,
            8 => false,
            9 => false,
            10 => false,
            11 => false,
            12 => false,
            '12+' => false,
        ],
        '7-12' => [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
            5 => true,
            6 => true,
            7 => true,
            8 => true,
            9 => true,
            10 => true,
            11 => false,
            12 => false,
            '12+' => false,
        ],
        '12+' => [
            'contact' => false,
        ]
    ];
    
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
            [['fk_parent', 'fk_cours_nom', 'fk_cours', 'is_actif'], 'integer'],
            [['fk_cours_nom', 'adresse', 'npa', 'localite', 'telephone', 'email'], 'required'],
            [['date_naissance', 'date_naissance_enfant', 'date_inscription'], 'safe'],
            [['informations', 'agemoyen', 'nbparticipant'], 'string'],
            [['nom', 'prenom', 'prenom_enfant'], 'string', 'max' => 60],
            [['adresse', 'localite', 'email'], 'string', 'max' => 100],
            [['npa'], 'string', 'max' => 5],
            [['telephone'], 'string', 'max' => 20],
            ['iagree', 'compare', 'operator' => '==', 'compareValue' => true, 'message' => Yii::t('app', 'Vous devez accepter les conditions générales')],

            [['nom', 'prenom', 'prenom_enfant', 'agemoyen', 'nbparticipant'], 'required', 'on' => ['anniversaire']],
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
            'fk_cours_nom' => Yii::t('app', 'Fk Cours Nom'),
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
            'agemoyen' => Yii::t('app', 'Age moyen des enfants'),
            'nbparticipant' => Yii::t('app', 'Choisir un nombre de participant (enfants et adultes)'),
            'prenom_enfant' => Yii::t('app', 'Prénom de l\'enfant'),
            'date_naissance_enfant' => Yii::t('app', 'Date de naissance de l\'enfant'),
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
    public function getFkCoursNom()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_cours_nom']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkCours()
    {
        return $this->hasOne(Cours::className(), ['cours_id' => 'fk_cours']);
    }

    /**
     * @return array options for drop-down dependant
     */
    public function optsPartByAge($agemoyen)
    {
        $out = [];
        $arrayAge = ['2-12', '5-6', '7-12', '12+'];
        if (!in_array($agemoyen, $arrayAge)) {
            return $out;
        }

        $selected = '';
        if ('12+' == $agemoyen) {
            $out = [
                ['id' => 'contact', 'name' => Yii::t('app', 'Choix du nombre sans importance')],
            ];
            $selected = 'contact';
        } else {
            $min = 1;
            $max = 12;
            for ($i = $min; $i <= $max; $i++) {
                $out[] = ['id' => $i, 'name' => $i];
            }
            $out[] = ['id' => $max . '+', 'name' => Yii::t('app', 'plus de {nombre} personnes', ['nombre' => $max])];
        }

        return ['output'=>$out, 'selected'=>$selected];
    }
}
