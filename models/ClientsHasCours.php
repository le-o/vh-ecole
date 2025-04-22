<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "clients_has_cours".
 *
 * @property integer $fk_personne
 * @property integer $fk_cours
 * @property integer $fk_statut
 *
 * @property Personnes $fkPersonne
 * @property Cours $fkCours
 * @property Parametres $fkStatut
 */
class ClientsHasCours extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'clients_has_cours';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_personne', 'fk_cours', 'fk_statut'], 'required'],
            [['fk_personne', 'fk_cours', 'fk_statut'], 'integer'],
            [['fk_personne'], 'exist', 'skipOnError' => true, 'targetClass' => Personnes::className(), 'targetAttribute' => ['fk_personne' => 'personne_id']],
            [['fk_cours'], 'exist', 'skipOnError' => true, 'targetClass' => Cours::className(), 'targetAttribute' => ['fk_cours' => 'cours_id']],
            [['fk_statut'], 'exist', 'skipOnError' => true, 'targetClass' => Parametres::className(), 'targetAttribute' => ['fk_statut' => 'parametre_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fk_personne' => Yii::t('app', 'Personne'),
            'fk_cours' => Yii::t('app', 'Cours'),
            'fk_statut' => Yii::t('app', 'Statut'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkPersonne()
    {
        return $this->hasOne(Personnes::class, ['personne_id' => 'fk_personne']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkCours()
    {
        return $this->hasOne(Cours::class, ['cours_id' => 'fk_cours']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkStatut()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_statut']);
    }
}
