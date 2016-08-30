<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "clients_has_cours".
 *
 * @property integer $fk_personne
 * @property integer $fk_cours
 * @property integer $is_facture
 *
 * @property Cours $fkCours
 * @property Personnes $fkPersonne
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
            [['fk_personne', 'fk_cours', 'is_facture'], 'required'],
            [['fk_personne', 'fk_cours', 'is_facture'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fk_personne' => Yii::t('app', 'Fk Personne'),
            'fk_cours' => Yii::t('app', 'Fk Cours'),
            'is_facture' => Yii::t('app', 'Is Facture'),
        ];
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
    public function getFkPersonne()
    {
        return $this->hasOne(Personnes::className(), ['personne_id' => 'fk_personne']);
    }
}
