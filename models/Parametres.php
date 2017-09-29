<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parametres".
 *
 * @property integer $parametre_id
 * @property integer $class_key
 * @property string $nom
 * @property string $valeur
 * @property string $info_special
 * @property string $info_couleur
 * @property integer $tri
 *
 * @property Personnes[] $personnes
 * @property Personnes[] $personnes0
 */
class Parametres extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'parametres';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['class_key', 'nom'], 'required'],
            [['class_key', 'tri'], 'integer'],
            [['valeur'], 'string'],
            [['nom'], 'string', 'max' => 50],
            [['info_special'], 'string', 'max' => 150],
            [['info_couleur'], 'string', 'max' => 7]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'parametre_id' => Yii::t('app', 'Parametre ID'),
            'class_key' => Yii::t('app', 'Class Key'),
            'nom' => Yii::t('app', 'Nom'),
            'valeur' => Yii::t('app', 'Valeur'),
            'info_special' => Yii::t('app', 'Info Special'),
            'info_couleur' => Yii::t('app', 'Info Couleur'),
            'tri' => Yii::t('app', 'Tri'),
        ];
    }
    
    /**
     * @return array options email for drop-down
     */
    public function optsEmail()
    {   
        return $this->optsDropDown(1);
    }

    /**
     * @return array options personne statut for drop-down
     */
    public function optsStatut()
    {
        return $this->optsDropDown(3);
    }

    /**
     * @return array options personne type for drop-down
     */
    public function optsType()
    {   
        return $this->optsDropDown(2);
    }
    
    /**
     * @return array options personne type for drop-down
     */
    public function optsNiveau()
    {   
        return $this->optsDropDown(4);
    }

    /**
     * @return array options cours type for drop-down
     */
    public function optsTypeCours()
    {
        return $this->optsDropDown(6);
    }

    /**
     * @return array options nom cours for drop-down
     */
    public function optsNomCours()
    {
        return $this->optsDropDown(7);
    }

    /**
     * @return array options niveau formation for drop-down
     */
    public function optsNiveauFormation()
    {
        return $this->optsDropDown(8);
    }

    /**
     * @return array options statut participant for drop-down
     */
    public function optsStatutPart()
    {
        return $this->optsDropDown(9);
    }
    
    /**
     * @return array options tranche âge for drop-down
     */
    public function optsTrancheAge()
    {
        return $this->optsDropDown(10);
    }
    
    /**
     * @return array options saison for drop-down
     */
    public function optsSaison()
    {
        return $this->optsDropDown(11);
    }
    
    /**
     * @return array options jours semaine for drop-down
     */
    public function optsJourSemaine()
    {
        return $this->optsDropDown(12);
    }
    
    /**
     * @return array options semestre for drop-down
     */
    public function optsSemestre()
    {
        return $this->optsDropDown(13);
    }
    
    /**
     * @return array options categorie for drop-down
     */
    public function optsCategorie()
    {
        return $this->optsDropDown(14);
    }

    /**
     * @return array options from classkey for drop-down
     */
    public function optsDropDown($classKey)
    {   
        $codes = self::find()->where(['class_key' => $classKey])->orderBy('tri')->all();
        $temp = array();
        foreach($codes as $code) {
            $temp[$code['parametre_id']]= $code->nom;
        }
        return $temp;
    }
    
    /**
     * @return array options for class_key drop-down
     */
    public function optsRegroupement()
    {
        return array(
            '1' => 'Texte et email',
            '2' => 'Type de personnes',
            '3' => 'Statut de personnes',
            '4' => 'Niveau de cours',
            '5' => 'Paramètres généraux',
            '6' => 'Type de cours',
            '7' => 'Nom de cours',
            '8' => 'Niveau de formation',
            '9' => 'Statut de participation',
            '10' => 'Tranche d\'âge',
            '11' => 'Année de cours',
            '12' => 'Jour de la semaine',
            '13' => 'Nom session internet',
            '14' => 'Catégorie internet',
        );
    }
}
