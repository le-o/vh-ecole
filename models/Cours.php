<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cours".
 *
 * @property integer $cours_id
 * @property integer $fk_niveau
 * @property integer $fk_type
 * @property integer $fk_nom
 * @property integer $fk_age
 * @property string $extrait
 * @property string $description
 * @property float $duree
 * @property string $session
 * @property string $annee
 * @property integer $fk_saison
 * @property integer $fk_semestre
 * @property array $fk_jours
 * @property float $prix
 * @property integer $participant_min
 * @property integer $participant_max
 * @property string $offre_speciale
 * @property integer $is_materiel_compris
 * @property integer $is_entree_compris
 * @property integer $is_actif
 * @property integer $is_publie
 * @property array $fk_categories 
 * @property string $image_web 
 * @property integer $fk_langue
 * @property integer $fk_salle
 *
 * @property ClientsHasCours[] $clientsHasCours 
 * @property Personnes[] $fkPersonnes 
 * @property Parametres $fkNiveau
 * @property Parametres $fkType 
 * @property Parametres $fkNom 
 * @property Parametres $fkAge 
 * @property CoursDate[] $coursDates
 * @property Parametres $fkLangue
 * @property Parametres $fkSalle
 */
class Cours extends \yii\db\ActiveRecord
{
    
    public $image;
    public $image_hidden;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cours';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_niveau', 'fk_type', 'fk_nom', 'fk_age', 'description', 'duree', 'session', 'prix', 'is_materiel_compris', 'is_entree_compris', 'is_actif', 'is_publie', 'fk_langue', 'fk_salle'], 'required'],
            [['fk_niveau', 'fk_type', 'fk_nom', 'fk_age', 'fk_saison', 'fk_semestre', 'participant_min', 'participant_max', 'is_materiel_compris', 'is_entree_compris', 'is_actif', 'is_publie', 'fk_langue', 'fk_salle'], 'integer'],
            [['duree', 'prix'], 'double'],
            [['extrait', 'description', 'session', 'offre_speciale'], 'string'],
            [['annee', 'image'], 'safe'],
            [['image_web'], 'default', 'value' => null],
            [['image'], 'file', 'extensions' => 'png, jpg', 'maxSize' => 1024 * 1024 * 3, 'skipOnEmpty' => true],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cours_id' => Yii::t('app', 'Cours ID'),
            'fk_niveau' => Yii::t('app', 'Niveau'),
            'fk_type' => Yii::t('app', 'Type'),
            'fk_nom' => Yii::t('app', 'Nom'),
            'fk_age' => Yii::t('app', 'Age'),
            'description' => Yii::t('app', 'Description'),
            'duree' => Yii::t('app', 'Durée'),
            'session' => Yii::t('app', 'Session'),
            'annee' => Yii::t('app', 'Annee'),
            'fk_saison' => Yii::t('app', 'Saison'),
            'fk_semestre' => Yii::t('app', 'Semestre'),
            'fk_jours' => Yii::t('app', 'Jours de la semaine'),
            'prix' => Yii::t('app', 'Prix'),
            'participant_min' => Yii::t('app', 'Participant Min'),
            'participant_max' => Yii::t('app', 'Participant Max'),
            'offre_speciale' => Yii::t('app', 'Offre Spéciale'),
            'is_materiel_compris' => Yii::t('app', 'Is Materiel Compris'),
            'is_entree_compris' => Yii::t('app', 'Is Entree Compris'),
            'is_actif' => Yii::t('app', 'Is Actif'),
            'is_publie' => Yii::t('app', 'Is Publié'),
            'fk_langue' => Yii::t('app', 'Langue'),
            'fk_salle' => Yii::t('app', 'Salle'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->fk_jours = explode(',', $this->fk_jours);
        $this->fk_categories = explode(',', $this->fk_categories);
        parent::afterFind();
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->fk_jours = (!empty($this->fk_jours)) ? implode(',', $this->fk_jours) : '';
            $this->fk_categories = (!empty($this->fk_categories)) ? implode(',', $this->fk_categories) : '';
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
        $this->fk_jours = explode(',', $this->fk_jours);
        $this->fk_categories = explode(',', $this->fk_categories);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkNiveau()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_niveau']);
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
    public function getFkNom()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_nom']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkAge()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_age']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkSaison()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_saison']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkSemestre()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_semestre']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkLangue()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_langue']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkSalle()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_salle']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkJours()
    {
        return $this->hasMany(Parametres::className(), ['parametre_id' => 'fk_jours']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkJoursNoms()
    {
        $jourNom = [];
        $jours = $this->hasMany(Parametres::className(), ['parametre_id' => 'fk_jours']);
        foreach ($jours->all() as $j) {
            $jourNom[] = $j->nom;
        }
        return implode(', ', $jourNom);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkCategories()
    {
        return $this->hasMany(Parametres::className(), ['parametre_id' => 'fk_categories']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFirstCoursDate()
    {
        return $this->hasMany(CoursDate::className(), ['fk_cours' => 'cours_id'])->one();
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoursDates()
    {
        return $this->hasMany(CoursDate::className(), ['fk_cours' => 'cours_id'])->orderBy(['date' => SORT_ASC]);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNextCoursDate()
    {
        return $this->hasMany(CoursDate::className(), ['fk_cours' => 'cours_id'])->where(['>=', 'date', date('Y-m-d')])->one();
    }

    /**
     * @return int Number of clients
     */
    public function getNombreClientsInscrits($listeCoursDate = [])
    {
        // liste des dates de cours
        if (empty($listeCoursDate)) {
            $coursDate = CoursDate::find()->where(['fk_cours' => $this->cours_id])->orderBy('date');
            foreach ($coursDate->all() as $date) {
                $listeCoursDate[] = $date->cours_date_id;
            }
        }
        return ClientsHasCoursDate::find()->select('fk_personne')->distinct()->where(['IN', 'clients_has_cours_date.fk_cours_date', $listeCoursDate])->andWhere(['clients_has_cours_date.fk_statut' => Yii::$app->params['partInscrit']])->count();
    }
    
    /**
     * @return string Nombre clients inscrits (Nombre clients 2 cours à l'essai)
     */
    public function getNombreClientsInscritsForDataGrid()
    {
        // liste des dates de cours
        $listeCoursDate = [];
        $coursDate = CoursDate::find()->where(['fk_cours' => $this->cours_id])->orderBy('date');
        foreach ($coursDate->all() as $date) {
            $listeCoursDate[] = $date->cours_date_id;
        }
        
        $partEssai = Personnes::find()->distinct()->joinWith('clientsHasCoursDate', false)->where(['IN', 'clients_has_cours_date.fk_cours_date', $listeCoursDate])->andWhere(['clients_has_cours_date.fk_statut' => Yii::$app->params['part2Essai']])->count();
        
        return ($partEssai != 0) ? $this->getNombreClientsInscrits($listeCoursDate).' ('.$partEssai.')' : $this->getNombreClientsInscrits($listeCoursDate);
    }
    
    /**
     * @return string Nombre clients inscrits + Nombre clients 2 cours à l'essai
     */
    public function getNombreClientsInscritsForExport()
    {
        // liste des dates de cours
        $listeCoursDate = [];
        $coursDate = CoursDate::find()->where(['fk_cours' => $this->cours_id])->orderBy('date');
        foreach ($coursDate->all() as $date) {
            $listeCoursDate[] = $date->cours_date_id;
        }
        
        $partEssai = ClientsHasCoursDate::find()->select('fk_personne')->distinct()->where(['IN', 'clients_has_cours_date.fk_cours_date', $listeCoursDate])->andWhere(['clients_has_cours_date.fk_statut' => Yii::$app->params['part2Essai']])->count();

        return $this->getNombreClientsInscrits($listeCoursDate) + $partEssai;
    }
}
