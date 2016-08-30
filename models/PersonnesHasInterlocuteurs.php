<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "personnes_has_interlocuteurs".
 *
 * @property integer $fk_personne
 * @property integer $fk_interlocuteur
 *
 * @property Personnes $fkInterlocuteur
 * @property Personnes $fkPersonne
 */
class PersonnesHasInterlocuteurs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'personnes_has_interlocuteurs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_personne', 'fk_interlocuteur'], 'required'],
            [['fk_personne', 'fk_interlocuteur'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fk_personne' => 'Fk Personne',
            'fk_interlocuteur' => 'Fk Interlocuteur',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkInterlocuteur()
    {
        return $this->hasOne(Personnes::className(), ['personne_id' => 'fk_interlocuteur']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkPersonne()
    {
        return $this->hasOne(Personnes::className(), ['personne_id' => 'fk_personne']);
    }
}
