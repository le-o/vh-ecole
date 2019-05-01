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
 * @property integer $fk_lieu
 * @property float $duree
 * @property float $prix
 * @property string $remarque
 * @property integer $nb_client_non_inscrit
 * @property boolean $calendar_sync
 * @property datetime $update_date
 *
 * @property Cours $fkCours
 * @property Parametres $fkLieu
 */
class CoursDate extends \yii\db\ActiveRecord
{
    
    const CALENDAR_NEW = 0;
    const CALENDAR_SYNC = 1;
    const CALENDAR_EDIT = 2;
    
    public $updateSync = true;

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
            [['fk_cours', 'date', 'heure_debut', 'duree', 'prix', 'fk_lieu'], 'required'],
            [['fk_cours', 'nb_client_non_inscrit', 'fk_lieu'], 'integer'],
            [['date', 'heure_debut', 'remarque'], 'safe']
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
            'fkLieu' => Yii::t('app', 'Lieu'),
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
            if (strlen($this->heure_debut < 8)) {
                $this->heure_debut = $this->heure_debut.':00';
            }
            
            if (true == $this->updateSync) {
                $this->calendar_sync = ($this->isNewRecord) ? self::CALENDAR_NEW : self::CALENDAR_EDIT;
            } else {
                $this->update_date = date('Y-m-d H:i:s');
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return Date Heure de fin
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
    public function getFkLieu()
    {
        return $this->hasOne(Parametres::className(), ['parametre_id' => 'fk_lieu']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientsHasCoursDate()
    {
        return $this->hasMany(ClientsHasCoursDate::className(), ['fk_cours_date' => 'cours_date_id'])->orderBy(['fk_statut' => SORT_ASC]);
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
    public function getCoursHasMoniteursListe($arrayMoniteurs = [], $sep = ', ')
    {
        $moniteurs = (!empty($arrayMoniteurs)) ? $arrayMoniteurs : $this->getCoursHasMoniteurs();
        $arrayMoniteurs = [];
        foreach ($moniteurs as $m) {
            if (isset($m->fk_moniteur)) {
                $arrayMoniteurs[] = $m->fkMoniteur->nomPrenom;
            }
        }
        return implode($sep, $arrayMoniteurs);
    }
    
    /**
     * @return int Number of clients
     */
    public function getNombreClientsInscrits()
    {
        return Personnes::find()->distinct()->joinWith('clientsHasCoursDate', false)->where(['IN', 'clients_has_cours_date.fk_cours_date', $this->cours_date_id])->andWhere(['clients_has_cours_date.fk_statut' => Yii::$app->params['partInscrit']])->count();
    }
    
    /**
     * @return string Nombre clients inscrits (Nombre clients 2 cours à l'essai)
     */
    public function getNombreClientsInscritsForDataGrid()
    {
        $partEssai = Personnes::find()->distinct()->joinWith('clientsHasCoursDate', false)->where(['IN', 'clients_has_cours_date.fk_cours_date', $this->cours_date_id])->andWhere(['clients_has_cours_date.fk_statut' => Yii::$app->params['part2Essai']])->count();
        
        return ($partEssai != 0) ? $this->getNombreClientsInscrits().' ('.$partEssai.')' : $this->getNombreClientsInscrits();
    }
    
    public function getDateToSync($count = false, $limit = 0) {
        $query = self::find()
                ->where(['>=', 'date', date('Y.m.d')])
                ->andWhere(['IN', 'calendar_sync', [self::CALENDAR_NEW, self::CALENDAR_EDIT]])
                ->orderBy('date ASC');
        if ($limit > 0) {
            $query->limit($limit);
        }
        $modelCoursDate = $query->all();
        if (true == $count) {
            return count($modelCoursDate);
        } else {
            return $modelCoursDate;
        }
    }
    
    /**
     * 
     * @return string
     */
    public function getVCalendarString()
    {
        if ($this->fkCours->fk_type == Yii::$app->params['coursPonctuel']) {
            $title = (isset($this->clientsHasCoursDate[0]) ? $this->clientsHasCoursDate[0]->fkPersonne->suivi_client.' '.$this->clientsHasCoursDate[0]->fkPersonne->societe.' '.$this->clientsHasCoursDate[0]->fkPersonne->nomPrenom : Yii::t('app', 'Client non défini'));
            $title .= ' '.$this->fkCours->fkNom->nom.' '.$this->fkCours->session;
        } else {
            $title = $this->fkCours->fkNom->nom.' '.$this->fkCours->session.'.'.$this->fkCours->annee;
        }

        $arrayMoniteurs = [];
        $moniteurs = $this->coursHasMoniteurs;
        foreach ($moniteurs as $m) {
            $arrayMoniteurs[] = $m->fkMoniteur->nomPrenom;
        }
        $description = implode(', ', $arrayMoniteurs);
        
        $stringEvent = 'BEGIN:VCALENDAR
PRODID:-//vertic-halle/gestion des cours//NONSGML v1.0//EN
VERSION:2.0
BEGIN:VTIMEZONE
TZID:Europe/Zurich
X-LIC-LOCATION:Europe/Zurich
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:' . $this->dateToCal(strtotime($this->update_date)) . '
LAST-MODIFIED:' . $this->dateToCal(strtotime($this->update_date)) . '
DTSTAMP:' . $this->dateToCal(time()) . '
UID:VH-cours-' . $this->cours_date_id . '
SUMMARY:' . $title . '
DTSTART;TZID=Europe/Zurich:' . $this->dateToCal(strtotime($this->date . ' ' . $this->heure_debut)) . '
DTEND;TZID=Europe/Zurich:' . $this->dateToCal(strtotime($this->date . ' ' . $this->getHeureFin())) . '
LOCATION:' . $this->fkLieu->nom . '
DESCRIPTION:' . $description . '
END:VEVENT
END:VCALENDAR';
        return $stringEvent;
    }
    
    /**
     * 
     * @param time $timestamp
     * @return date
     */
    private function dateToCal($timestamp) {
        return date('Ymd\THis', $timestamp);
    }
}
