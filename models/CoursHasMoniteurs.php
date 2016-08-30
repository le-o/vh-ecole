<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cours_has_moniteurs".
 *
 * @property integer $fk_cours_date
 * @property integer $fk_moniteur
 * @property integer $is_responsable
 *
 * @property Personnes $fkMoniteur
 * @property CoursDate $fkCoursDate
 */
class CoursHasMoniteurs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cours_has_moniteurs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fk_cours_date', 'fk_moniteur', 'is_responsable'], 'required'],
            [['fk_cours_date', 'fk_moniteur', 'is_responsable'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fk_cours_date' => Yii::t('app', 'Fk Cours Date'),
            'fk_moniteur' => Yii::t('app', 'Fk Moniteur'),
            'is_responsable' => Yii::t('app', 'Is Responsable'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkMoniteur()
    {
        return $this->hasOne(Personnes::className(), ['personne_id' => 'fk_moniteur']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkCoursDate()
    {
        return $this->hasOne(CoursDate::className(), ['cours_date_id' => 'fk_cours_date']);
    }
}
