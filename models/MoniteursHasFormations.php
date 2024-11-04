<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "moniteurs_has_formations".
 *
 * @property int $fk_moniteur
 * @property int $fk_formation
 *
 * @property Parametres $fkFormation
 */
class MoniteursHasFormations extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'moniteurs_has_formations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fk_moniteur', 'fk_formation'], 'required'],
            [['fk_moniteur', 'fk_formation'], 'integer'],
            [['fk_moniteur', 'fk_formation'], 'unique', 'targetAttribute' => ['fk_moniteur', 'fk_formation']],
            [['fk_formation'], 'exist', 'skipOnError' => true, 'targetClass' => Parametres::class, 'targetAttribute' => ['fk_formation' => 'parametre_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'fk_moniteur' => Yii::t('app', 'Moniteur'),
            'fk_formation' => Yii::t('app', 'Formation'),
        ];
    }

    /**
     * Gets query for [[FkFormation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFkFormation()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_formation']);
    }
}
