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

    /**
     * @return string
     */
    public function getMoniteursRole() {
        // Case animateur.rice ASSE ou encadrant.e ASSE ou instructeur/rice ASSE (choisir le meilleur niveau)
        // ET ajouter à la ligne responsable de formation ASSE
        // ET ajouter à la ligne expert.e ASSE - si les dates sont remplies évidemment
        $role = '';
        if (!empty($this->instructeur_asse)) {
            $role = 'Instructeur';
        } elseif (!empty($this->encadrant_asse)) {
            $role = 'Encadrant';
        } elseif (!empty($this->animateur_asse)) {
            $role = 'Animateur';
        }
        $sep = (!empty($role) ? PHP_EOL : '');
        if (!empty($this->referent_asse)) {
            $role .= $sep . 'Responsable';
        }
        if (!empty($this->expert_asse)) {
            $role .= $sep . 'Expert';
        }
        return $role;
    }

    /**
     * @return string
     */
    public function getMoniteursExamDate() {
        // Date de passage de l'examen de animateur/encadrant/instructeur
        // (prendre la date du meilleur niveau choisi avant dans la colonne "rolle"
        if (!empty($this->expert_asse)) {
            $date = $this->expert_asse;
        } elseif (!empty($this->referent_asse)) {
            $date = $this->referent_asse;
        } elseif (!empty($this->instructeur_asse)) {
            $date = $this->instructeur_asse;
        } elseif (!empty($this->encadrant_asse)) {
            $date = $this->encadrant_asse;
        } elseif (!empty($this->animateur_asse)) {
            $date = $this->animateur_asse;
        } else {
            return '';
        }

        return date('d.m.Y', strtotime($date));
    }

    /**
     * @return string
     */
    public function getBaremeSuggereComplete(): string {
        return $this->getBaremeSuggere(true);
    }

    /**
     * @return string
     */
    public function getBaremeSuggereSimple(): string {
        return $this->getBaremeSuggere(false);
    }

    /**
     * @param bool $withLabel
     * @return string
     */
    private function getBaremeSuggere(bool $withLabel): string {
        $bareme = 'auxiliaire';
        $dates = [];
        if (!empty($this->animateur_asse) && !empty($this->parcours)) {
            $dates = [$this->animateur_asse, $this->parcours];
            $bareme = 'animateur';

            if (!empty($this->js1_escalade)) {
                $dates[] = $this->js1_escalade;
                $bareme = 'moniteur 1';

                if (!empty($this->methode_VCS) && !empty($this->js_allround)) {
                    $dates[] = $this->methode_VCS;
                    $dates[] = $this->js_allround;
                    $bareme = 'moniteur 2';

                    if (!empty($this->experience_cours)) {
                        $dates[] = $this->experience_cours;
                        $bareme = 'moniteur 3';
                    }

                    if (!empty($this->instructeur_asse)) {
                        $dates[] = $this->instructeur_asse;
                        $bareme = 'moniteur 4';

                        if (!empty($this->js2_escalade)) {
                            $dates[] = $this->js2_escalade;
                            $bareme = 'moniteur 5';

                            if (!empty($this->js3_escalade) || !empty($this->prof_escalade)) {
                                $dates[] = $this->js3_escalade;
                                $dates[] = $this->prof_escalade;
                                $bareme = 'moniteur 6';
                            }
                        }
                    } elseif (!empty($this->prof_escalade)) {
                        $dates[] = $this->prof_escalade;
                        $bareme = 'moniteur 4';
                    }
                } elseif (!empty($this->instructeur_asse) || !empty($this->js2_escalade) || !empty($this->js3_escalade)) {
                    $dates = [$this->instructeur_asse, $this->js2_escalade, $this->js3_escalade];
                    $bareme = 'moniteur 2';
                }
            }
        }

        $baremeSuggere = ($withLabel ? 'Barème suggéré : Barème ' . $bareme : ucfirst($bareme));
        if (!empty($dates)) {
            $date = date("d.m.Y", max(array_map('strtotime', array_filter($dates))));
            $baremeSuggere .= ' - ' . $date;
        }
        return $baremeSuggere;
    }
}
