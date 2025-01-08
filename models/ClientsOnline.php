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
 * @property string $numeroRue
 * @property string $npa
 * @property string $localite
 * @property integer $fk_pays
 * @property integer $fk_nationalite
 * @property string $telephone
 * @property string $email
 * @property string $date_naissance
 * @property string $no_avs
 * @property integer $fk_sexe
 * @property integer $fk_langue_mat
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

    // tableau de validation des inscriptions automatiques
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
            7 => true,
            8 => true,
            9 => false,
            10 => false,
            11 => false,
            12 => false,
            '12+' => false,
        ],
        // anniversaire aventure
        '5-6-aventure' => [
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
        '7-11' => [
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
        '7-11-aventure' => [
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
        '12+' => [
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
        '12+-aventure' => [
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
        '12+-aventure-240' => [
            1 => false,
            2 => false,
            3 => false,
            4 => false,
            5 => false,
            6 => false,
            7 => false,
            8 => false,
            9 => false,
            10 => false,
            11 => false,
            12 => false,
            '12+' => false,
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
            [['fk_parent', 'fk_cours_nom', 'fk_cours', 'fk_pays', 'fk_nationalite', 'fk_sexe', 'is_actif'], 'integer'],
            [['fk_cours_nom', 'adresse', 'npa', 'localite', 'telephone', 'email'], 'required'],
            [['numeroRue', 'fk_pays'], 'required', 'except' => 'anniversaire'],
            [['date_naissance', 'date_naissance_enfant', 'date_inscription'], 'safe'],
            [['informations', 'agemoyen', 'nbparticipant'], 'string'],
            [['nom', 'prenom', 'prenom_enfant'], 'string', 'max' => 60],
            [['adresse', 'localite', 'email'], 'string', 'max' => 100],
            [['npa'], 'string', 'max' => 5],
            [['telephone'], 'string', 'max' => 20],
            [['no_avs'], 'string', 'max' => 16],
            [['no_avs'], 'match', 'pattern' => '/[7][5][6]\\.[\d]{4}[.][\d]{4}[.][\d]{2}$/'],
            ['no_avs', 'makeAVSMandatory', 'skipOnEmpty'=>false, 'params' => 'date_naissance'],
            ['iagree', 'compare', 'operator' => '==', 'compareValue' => true, 'message' => Yii::t('app', 'Vous devez accepter les conditions générales')],

            [['nom', 'prenom', 'prenom_enfant', 'agemoyen', 'nbparticipant'], 'required', 'on' => ['anniversaire']],

            ['fk_sexe', 'required', 'when' => function ($model) {
                    return $model->nom != '';
                }, 'whenClient' => "function (attribute, value) {
                    if (attribute.name.indexOf('[') == -1) {
                       index = '';
                    } else {
                        if (attribute.name.charAt(0) === '[') { //when dynamic form is not activated and name of inputs is like [0]fk_sexe
                            index = '-' + attribute.name.charAt(1); //I get the array index
                        } else {
                            index = '-' + attribute.name.charAt(14); //dynamic form activated the name of inputs changed like this ClientsOnline[0][fk_sexe].
                        }
                    }
                    return '' != $('[id$=' + index + '-nom]').val();
            }", 'except' => 'anniversaire'],
            ['fk_nationalite', 'required', 'when' => function ($model) {
                return $model->nom != '';
            }, 'whenClient' => "function (attribute, value) {
                    if (attribute.name.indexOf('[') == -1) {
                       index = '';
                    } else {
                        if (attribute.name.charAt(0) === '[') { //when dynamic form is not activated and name of inputs is like [0]fk_sexe
                            index = '-' + attribute.name.charAt(1); //I get the array index
                        } else {
                            index = '-' + attribute.name.charAt(14); //dynamic form activated the name of inputs changed like this ClientsOnline[0][fk_sexe].
                        }
                    }
                    console.log($('[id$=' + index + '-nom]').val());
                    return '' != $('[id$=' + index + '-nom]').val();
            }", 'except' => 'anniversaire'],
            ['fk_langue_mat', 'required', 'when' => function ($model) {
                return $model->nom != '';
            }, 'whenClient' => "function (attribute, value) {
                    if (attribute.name.indexOf('[') == -1) {
                       index = '';
                    } else {
                        if (attribute.name.charAt(0) === '[') { //when dynamic form is not activated and name of inputs is like [0]fk_sexe
                            index = '-' + attribute.name.charAt(1); //I get the array index
                        } else {
                            index = '-' + attribute.name.charAt(14); //dynamic form activated the name of inputs changed like this ClientsOnline[0][fk_sexe].
                        }
                    }
                    return '' != $('[id$=' + index + '-nom]').val();
            }", 'except' => 'anniversaire']
        ];
    }

    public function makeAVSMandatory($attribute_name, $params)
    {
        if (empty($this->$attribute_name) &&  !empty($this->$params)) {
            $from = new \DateTime($this->$params);
            $to   = new \DateTime('today');
            if (20 > $from->diff($to)->y) {
                $this->addError($attribute_name, Yii::t('app', "Le no AVS est obligatoire."));
            }
        }
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
            'numeroRue' => Yii::t('app', 'Numéro'),
            'npa' => Yii::t('app', 'Npa'),
            'localite' => Yii::t('app', 'Localite'),
            'fk_pays' => Yii::t('app', 'Pays'),
            'fkPays.nom' => Yii::t('app', 'Pays'),
            'fk_nationalite' => Yii::t('app', 'Nationalité'),
            'fkNationalite.nom' => Yii::t('app', 'Nationalité'),
            'telephone' => Yii::t('app', 'Telephone'),
            'email' => Yii::t('app', 'Email'),
            'date_naissance' => Yii::t('app', 'Date Naissance'),
            'no_avs' => Yii::t('app', 'No AVS'),
            'fk_sexe' => Yii::t('app', 'Sexe'),
            'fkSexe.nom' => Yii::t('app', 'Sexe'),
            'fk_langue_mat' => Yii::t('app', 'Langue maternelle'),
            'fkLangueMat.nom' => Yii::t('app', 'Langue maternelle'),
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
        $this->date_naissance = ($this->date_naissance == '0000-00-00' || empty($this->date_naissance)) ? '' : date('d.m.Y', strtotime($this->date_naissance));
        $this->date_inscription = date('d.m.Y H:i:s', strtotime($this->date_inscription));
        parent::afterFind();
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (!empty($this->date_naissance)) {
                $this->date_naissance = date('Y-m-d', strtotime($this->date_naissance));
            }
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
    public function getFkPays()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_pays']);
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
    public function getFkNationalite()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_nationalite']);
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
        $arrayAge = ['2-12', '5-6', '7-11', '12+'];
        if (!in_array($agemoyen, $arrayAge)) {
            return $out;
        }

        $selected = '';
        $min = 1;
        $max = 12;
        for ($i = $min; $i <= $max; $i++) {
            $out[] = ['id' => $i, 'name' => $i];
        }
        $out[] = ['id' => $max . '+', 'name' => Yii::t('app', 'plus de {nombre} personnes', ['nombre' => $max])];

        return ['output'=>$out, 'selected'=>$selected];
    }
}
