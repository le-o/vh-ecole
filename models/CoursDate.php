<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cours_date".
 *
 * @property integer $cours_date_id
 * @property integer $fk_cours
 * @property string $date
 * @property string $heure_debut
 * @property string $lieu
 * @property float $duree
 * @property float $prix
 * @property string $remarque
 * @property integer $nb_client_non_inscrit
 *
 * @property Cours $fkCours
 */
class CoursDate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cours_date';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_cours', 'date', 'heure_debut', 'lieu', 'duree', 'prix'], 'required'],
            [['fk_cours', 'nb_client_non_inscrit'], 'integer'],
            [['date', 'heure_debut', 'remarque'], 'safe'],
            [['lieu'], 'string', 'max' => 150]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cours_date_id' => Yii::t('app', 'Cours Date ID'),
            'fk_cours' => Yii::t('app', 'Fk Cours'),
            'fkCours' => Yii::t('app', 'Nom du cours'),
            'date' => Yii::t('app', 'Date'),
            'heure_debut' => Yii::t('app', 'Heure début'),
            'lieu' => Yii::t('app', 'Lieu'),
            'duree' => Yii::t('app', 'Durée'),
            'prix' => Yii::t('app', 'Prix'),
            'remarque' => Yii::t('app', 'Remarque'),
            'nb_client_non_inscrit' => Yii::t('app', 'Nombre de client sans inscription'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->date = date('d.m.Y', strtotime($this->date));
        $this->heure_debut = substr($this->heure_debut, 0, 5);
        parent::afterFind();
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->date = date('Y-m-d', strtotime($this->date));
            $this->heure_debut = $this->heure_debut.':00';
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return Personne nom prénom
     */
    public function getHeureFin()
    {
        $tominutes = $this->duree * 60;
        return date('H:i', strtotime($this->heure_debut.'+'.$tominutes.' minutes'));
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkCours()
    {
        return $this->hasOne(Cours::className(), ['cours_id' => 'fk_cours']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientsHasCoursDate()
    {
        return $this->hasMany(ClientsHasCoursDate::className(), ['fk_cours_date' => 'cours_date_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForPresence($fk_personne)
    {
        return ClientsHasCoursDate::findOne(['fk_cours_date' => $this->cours_date_id, 'fk_personne' => $fk_personne]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoursHasMoniteurs()
    {
        return $this->hasMany(CoursHasMoniteurs::className(), ['fk_cours_date' => 'cours_date_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoursHasMoniteursListe()
    {
        $moniteurs = $this->getCoursHasMoniteurs();
        $arrayMoniteurs = [];
        foreach ($moniteurs as $m) {
            $arrayMoniteurs[] = $m->fkMoniteur->nomPrenom;
        }
        return implode(', ', $arrayMoniteurs);
    }
    
    /**
     * @return int Number of clients
     */
    public function getNombreClientsInscrits()
    {
	    return Personnes::find()->distinct()->joinWith('clientsHasCoursDate', false)->where(['IN', 'clients_has_cours_date.fk_cours_date', $this->cours_date_id])->count();
    }
}
