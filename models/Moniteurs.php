<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "moniteurs".
 *
 * @property int $moniteur_id
 * @property int $fk_personne
 * @property int $no_cresus
 * @property string $diplome
 * @property string $remarque
 * @property string $animateur_asse
 * @property string $instructeur_asse
 * @property string $encadrant_asse
 * @property string $referent_asse
 * @property string $expert_asse
 * @property string $parcours
 * @property string $methode_VCS
 * @property string $experience_cours
 * @property string $prof_escalade
 * @property string $js1_escalade
 * @property string $js2_escalade
 * @property string $js3_escalade
 * @property string $js_allround
 * @property string $js_expert
 *
 * @property Personnes $fkPersonne
 */
class Moniteurs extends \yii\db\ActiveRecord
{

    public $formationsStored = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'moniteurs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fk_personne'], 'required'],
            [['fk_personne', 'no_cresus'], 'integer'],
            [['remarque', 'diplome'], 'string'],
            [['animateur_asse', 'instructeur_asse', 'encadrant_asse', 'referent_asse', 'expert_asse', 'parcours', 'methode_VCS', 'experience_cours', 'prof_escalade', 'js1_escalade', 'js2_escalade', 'js3_escalade', 'js_allround', 'js_expert'], 'safe'],
            [['fk_personne'], 'exist', 'skipOnError' => true, 'targetClass' => Personnes::class, 'targetAttribute' => ['fk_personne' => 'personne_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'moniteur_id' => Yii::t('app', 'Moniteur ID'),
            'fk_personne' => Yii::t('app', 'Personne'),
            'no_cresus' => Yii::t('app', 'No Cresus'),
            'remarque' => Yii::t('app', 'Remarques'),
            'diplome' => Yii::t('app', 'Diplôme'),
            'animateur_asse' => Yii::t('app', 'Animateur.rice ASSE'),
            'instructeur_asse' => Yii::t('app', 'Instructeur.rice ASSE'),
            'encadrant_asse' => Yii::t('app', 'Encadrant.e ASSE'),
            'referent_asse' => Yii::t('app', 'Responsable de formation ASSE'),
            'expert_asse' => Yii::t('app', 'Expert.e ASSE'),
            'parcours' => Yii::t('app', 'Parcours/pendule'),
            'methode_VCS' => Yii::t('app', 'Méthodologie VCS'),
            'experience_cours' => Yii::t('app', 'Expérience de cours'),
            'prof_escalade' => Yii::t('app', 'Professeur.e d\'escalade'),
            'js1_escalade' => Yii::t('app', 'JS1 Escalade'),
            'js2_escalade' => Yii::t('app', 'JS2 Escalade'),
            'js3_escalade' => Yii::t('app', 'JS3 Escalade'),
            'js_allround' => Yii::t('app', 'JS Allround'),
            'js_expert' => Yii::t('app', 'Expert.e JS escalade'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        foreach ($this->moniteursHasFormations as $f) {
            $this->formationsStored[] = $f->fk_formation;
        }

        parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->animateur_asse = ($this->animateur_asse == '') ? null : date('Y-m-d', strtotime($this->animateur_asse));
            $this->parcours = ($this->parcours == '') ? null : date('Y-m-d', strtotime($this->parcours));
            $this->methode_VCS = ($this->methode_VCS == '') ? null : date('Y-m-d', strtotime($this->methode_VCS));
            $this->js_allround = ($this->js_allround == '') ? null : date('Y-m-d', strtotime($this->js_allround));
            $this->js1_escalade = ($this->js1_escalade == '') ? null : date('Y-m-d', strtotime($this->js1_escalade));
            $this->encadrant_asse = ($this->encadrant_asse == '') ? null : date('Y-m-d', strtotime($this->encadrant_asse));
            $this->experience_cours = ($this->experience_cours == '') ? null : date('Y-m-d', strtotime($this->experience_cours));
            $this->instructeur_asse = ($this->instructeur_asse == '') ? null : date('Y-m-d', strtotime($this->instructeur_asse));
            $this->referent_asse = ($this->referent_asse == '') ? null : date('Y-m-d', strtotime($this->referent_asse));
            $this->expert_asse = ($this->expert_asse == '') ? null : date('Y-m-d', strtotime($this->expert_asse));
            $this->js2_escalade = ($this->js2_escalade == '') ? null : date('Y-m-d', strtotime($this->js2_escalade));
            $this->js3_escalade = ($this->js3_escalade == '') ? null : date('Y-m-d', strtotime($this->js3_escalade));
            $this->prof_escalade = ($this->prof_escalade == '') ? null : date('Y-m-d', strtotime($this->prof_escalade));
            $this->js_expert = ($this->js_expert == '') ? null : date('Y-m-d', strtotime($this->js_expert));
            return true;
        } else {
            return false;
        }
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
     * @return \yii\db\ActiveQuery
     */
    public function getMoniteursHasFormations()
    {
        return $this->hasMany(MoniteursHasFormations::class, ['fk_moniteur' => 'moniteur_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function checkMoniteursHasOneFormation($moniteur_id, $formation_id)
    {
        return MoniteursHasFormations::findOne(['fk_moniteur' => $moniteur_id, 'fk_formation' => $formation_id]);
    }
}
