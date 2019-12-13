<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "clients_has_cours_date".
 *
 * @property integer $fk_personne
 * @property integer $fk_cours_date
 * @property integer $is_present
 *
 * @property CoursDate $fkCoursDate
 * @property Personnes $fkPersonne
 */
class ClientsHasCoursDate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'clients_has_cours_date';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_personne', 'fk_cours_date', 'is_present'], 'required'],
            [['fk_personne', 'fk_cours_date'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fk_personne' => Yii::t('app', 'Fk Personne'),
            'fk_cours_date' => Yii::t('app', 'Fk Cours Date'),
            'is_present' => Yii::t('app', 'present?'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkCoursDate()
    {
        return $this->hasOne(CoursDate::className(), ['cours_date_id' => 'fk_cours_date']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkPersonne()
    {
        return $this->hasOne(Personnes::className(), ['personne_id' => 'fk_personne']);
    }
}
