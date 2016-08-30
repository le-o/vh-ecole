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
 * @property string $description
 * @property float $duree
 * @property string $session
 * @property string $annee
 * @property float $prix
 * @property integer $participant_min
 * @property integer $participant_max
 * @property integer $is_actif
 *
 * @property Parametres $fkNiveau
 * @property CoursDate[] $coursDates
 */
class Cours extends \yii\db\ActiveRecord
{
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
            [['fk_niveau', 'fk_type', 'fk_nom', 'description', 'duree', 'is_actif'], 'required'],
            [['fk_niveau', 'fk_type', 'fk_nom', 'participant_min', 'participant_max', 'is_actif'], 'integer'],
            [['duree', 'prix'], 'double'],
            [['description', 'session'], 'string'],
            [['annee'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cours_id' => Yii::t('app', 'Cours ID'),
            'fk_niveau' => Yii::t('app', 'Fk Niveau'),
            'fk_type' => Yii::t('app', 'Fk Type'),
            'fk_nom' => Yii::t('app', 'Fk Nom'),
            'description' => Yii::t('app', 'Description'),
            'duree' => Yii::t('app', 'Durée'),
            'session' => Yii::t('app', 'Session'),
            'annee' => Yii::t('app', 'Annee'),
            'prix' => Yii::t('app', 'Prix'),
            'participant_min' => Yii::t('app', 'Participant Min'),
            'participant_max' => Yii::t('app', 'Participant Max'),
            'is_actif' => Yii::t('app', 'Is Actif'),
        ];
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
    public function getCoursDates()
    {
        return $this->hasMany(CoursDate::className(), ['fk_cours' => 'cours_id']);
    }

    /**
     * @return int Number of clients
     */
    public function getNombreClientsInscrits()
    {
        // liste des dates de cours
        $listeCoursDate = [];
        $coursDate = CoursDate::find()->where(['fk_cours' => $this->cours_id])->orderBy('date');
        foreach ($coursDate->all() as $date) {
            $listeCoursDate[] = $date->cours_date_id;
        }
	    return Personnes::find()->distinct()->joinWith('clientsHasCoursDate', false)->where(['IN', 'clients_has_cours_date.fk_cours_date', $listeCoursDate])->count();
    }
}
