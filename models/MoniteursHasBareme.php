<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "moniteurs_has_bareme".
 *
 * @property int $fk_personne
 * @property int $fk_bareme
 * @property string $date_debut
 * @property string $date_fin
 *
 * @property Parametres $fkBareme
 * @property Personnes $fkPersonne
 */
class MoniteursHasBareme extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'moniteurs_has_bareme';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fk_personne', 'fk_bareme', 'date_debut'], 'required'],
            [['fk_personne', 'fk_bareme'], 'integer'],
            [['date_debut', 'date_fin'], 'safe'],
            [['fk_personne', 'fk_bareme', 'date_debut'], 'unique', 'targetAttribute' => ['fk_personne', 'fk_bareme', 'date_debut']],
            [['fk_personne'], 'exist', 'skipOnError' => true, 'targetClass' => Personnes::class, 'targetAttribute' => ['fk_personne' => 'personne_id']],
            [['fk_bareme'], 'exist', 'skipOnError' => true, 'targetClass' => Parametres::class, 'targetAttribute' => ['fk_bareme' => 'parametre_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'fk_personne' => Yii::t('app', 'Moniteur'),
            'fk_bareme' => Yii::t('app', 'Bareme'),
            'date_debut' => Yii::t('app', 'Date Debut'),
            'date_fin' => Yii::t('app', 'Date Fin'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->date_debut = date('d.m.Y', strtotime($this->date_debut));
        $this->date_fin = date('d.m.Y', strtotime($this->date_fin));
        parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->date_debut = date('Y-m-d', strtotime($this->date_debut));
            $this->date_fin =  ($this->date_fin == '' ? '9999-12-31' : date('Y-m-d', strtotime($this->date_fin)));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets query for [[FkBareme]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFkBareme()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_bareme']);
    }

    /**
     * Gets query for [[FkPersonne]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFkPersonne()
    {
        return $this->hasOne(Personnes::class, ['personne_id' => 'fk_personne']);
    }

    /**
     * Gets query for [[FkBareme]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPreviousBareme()
    {
        return self::find()
            ->where(['fk_personne' => $this->fk_personne])
            ->andWhere(['<', 'date_debut', date('Y-m-d', strtotime($this->date_debut))])
            ->andWhere(['!=', 'fk_bareme', $this->fk_bareme])
            ->orderBy('date_fin DESC')
            ->one();
    }

    /**
     * Gets query for [[FkBareme]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNextBareme()
    {
        return self::find()
            ->where(['fk_personne' => $this->fk_personne])
            ->andWhere(['>', 'date_debut', date('Y-m-d', strtotime($this->date_debut))])
            ->andWhere(['!=', 'fk_bareme', $this->fk_bareme])
            ->orderBy('date_fin')
            ->one();
    }
}
