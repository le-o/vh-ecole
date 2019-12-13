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
 * @property string $date_fin_validite
 * @property integer $fk_langue
 *
 * @property Personnes[] $personnes
 * @property Personnes[] $personnes0
 */
class Parametres extends \yii\db\ActiveRecord
{
    
    public $keyForMail;
    public $listeEmails;
    public $listePersonneId;
    
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
            [['class_key', 'tri', 'fk_langue'], 'integer'],
            [['valeur'], 'string'],
            [['date_fin_validite'], 'safe'],
            [['nom'], 'string', 'max' => 50],
            [['info_special'], 'string', 'max' => 150],
            [['info_couleur'], 'string', 'max' => 7],
            
            [['date_fin_validite'], 'default', 'value' => NULL],
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
            'date_fin_validite' => Yii::t('app', 'Invalide depuis le'),
            'fk_langue' => Yii::t('app', 'Langue interface'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->date_fin_validite = $this->date_fin_validite != '' && $this->date_fin_validite != '0000-00-00' ? date('d.m.Y', strtotime($this->date_fin_validite)) : '';
        parent::afterFind();
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->date_fin_validite = $this->date_fin_validite != '' && $this->date_fin_validite != '0000-00-00' ? date('Y-m-d', strtotime($this->date_fin_validite)) : null;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkLangue()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_langue']);
    }
    
    /**
     * @return array options email for drop-down
     */
    public function optsEmail($selectedParam = null)
    {   
        return $this->optsDropDown(1, $selectedParam);
    }

    /**
     * @return array options personne statut for drop-down
     */
    public function optsStatut($selectedParam = null)
    {
        return $this->optsDropDown(3, $selectedParam);
    }

    /**
     * @return array options personne type for drop-down
     */
    public function optsType($selectedParam = null)
    {   
        return $this->optsDropDown(2, $selectedParam);
    }
    
    /**
     * @return array options personne type for drop-down
     */
    public function optsNiveau($selectedParam = null)
    {   
        return $this->optsDropDown(4, $selectedParam);
    }

    /**
     * @return array options cours type for drop-down
     */
    public function optsTypeCours($selectedParam = null)
    {
        return $this->optsDropDown(6, $selectedParam);
    }

    /**
     * @return array options nom cours for drop-down
     */
    public function optsNomCours($selectedParam = null)
    {
        return $this->optsDropDown(7, $selectedParam);
    }

    /**
     * !! Only for javascript !!
     * @param int $type parametres_id from info_special
     * @return string options id separate by ,
     */
    public function optsNomCoursByType($type)
    {
        $query = self::find()->where(['class_key' => 7, 'info_special' => $type])->orderBy('tri');
        $query->andWhere(['OR', 'date_fin_validite IS NULL', ['>=', 'date_fin_validite', 'today()']]);
        $codes = $query->all();
        $temp = array();
        foreach($codes as $code) {
            $temp[]= $code['parametre_id'];
        }
        return implode(', ', $temp);
    }

    /**
     * @return array options niveau formation for drop-down
     */
    public function optsNiveauFormation($selectedParam = null)
    {
        return $this->optsDropDown(8, $selectedParam);
    }

    /**
     * @return array options statut participant for drop-down
     */
    public function optsStatutPart($selectedParam = null)
    {
        return $this->optsDropDown(9, $selectedParam);
    }
    
    /**
     * @return array options tranche âge for drop-down
     */
    public function optsTrancheAge($selectedParam = null)
    {
        return $this->optsDropDown(10, $selectedParam);
    }
    
    /**
     * @return array options saison for drop-down
     */
    public function optsSaison($selectedParam = null)
    {
        return $this->optsDropDown(11, $selectedParam);
    }
    
    /**
     * @return array options jours semaine for drop-down
     */
    public function optsJourSemaine($selectedParam = null)
    {
        return $this->optsDropDown(12, $selectedParam);
    }
    
    /**
     * @return array options semestre for drop-down
     */
    public function optsSemestre($selectedParam = null)
    {
        return $this->optsDropDown(13, $selectedParam);
    }
    
    /**
     * @return array options categorie for drop-down
     */
    public function optsCategorie($selectedParam = null)
    {
        return $this->optsDropDown(14, $selectedParam);
    }
    
    /**
     * @return array options langue for drop-down
     */
    public function optsLangue($selectedParam = null)
    {
        return $this->optsDropDown(15, $selectedParam);
    }
    
    /**
     * @return array options langue for drop-down
     */
    public function optsLangueInterface($selectedParam = null)
    {
        $queryWhere = ['IN', 'parametre_id', Yii::$app->params['interface_language']];
        return $this->optsDropDown(15, $selectedParam, $queryWhere);
    }
    
    /**
     * @return array options salle for drop-down
     */
    public function optsSalle($selectedParam = null)
    {
        return $this->optsDropDown(16, $selectedParam);
    }
    /**
     * @return array options lieu for drop-down
     */
    public function optsLieu($selectedParam = null)
    {
        return $this->optsDropDown(17, $selectedParam);
    }

    /**
     * @return array options from classkey for drop-down
     */
    public function optsDropDown($classKey, $selectedParam, $queryWhere = null, $restrictLangue = true)
    {   
        $query = self::find()->where(['class_key' => $classKey])->orderBy('tri');
        
        $withId = (null !== $selectedParam && '' !== $selectedParam) ? 'parametre_id = '.$selectedParam : '';
        $query->andWhere(['OR', $withId, 'date_fin_validite IS NULL', ['>=', 'date_fin_validite', 'today()']]);
        
        // retrouver le code langue depuis la langue de l'interface
        if ($restrictLangue) {
            $langue = Yii::$app->language;
            $codeLangue = array_search($langue, Yii::$app->params['interface_language_label']);
            $query->andWhere(['OR', $withId, ['IN', 'fk_langue', [$codeLangue, Yii::$app->params['language_independant']]]]);
        }
        
        if ($queryWhere !== null) {
            $query->andWhere($queryWhere);
        }
        $codes = $query->all();
        $temp = array();
        foreach($codes as $code) {
            $temp[$code['parametre_id']] = Yii::t('app', $code->nom);
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
            '15' => 'Langue',
            '16' => 'Salle',
            '17' => 'Lieu',
        );
    }
    
    public function languesInterface()
    {
        return $this->optsDropDown(15, null, ['info_special' => 'langue interface'], false);
    }
    
    /**
     * Permet de foncer ou d'éclaircir une couleur hexadecimal
     * @param string $couleur
     * @param int $changementTon
     * @return string
     */
    public static function changerTonCouleur($couleur,$changementTon){
        $couleur=substr($couleur,1,6);
        $cl=explode('x',wordwrap($couleur,2,'x',3));
        $couleur='';
        for($i=0;$i<=2;$i++){
            $cl[$i]=hexdec($cl[$i]);
            $cl[$i]=$cl[$i]+$changementTon;
            if($cl[$i]<0) $cl[$i]=0;
            if($cl[$i]>255) $cl[$i]=255;
            $couleur.=StrToUpper(substr('0'.dechex($cl[$i]),-2));
        }
        return '#'.$couleur; 
    }
}
