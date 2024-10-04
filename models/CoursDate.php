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
    public $baremeMoniteur = null;

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
            [['date', 'heure_debut', 'remarque', 'baremeMoniteur'], 'safe']
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
            'fk_lieu' => Yii::t('app', 'Lieu'),
            'duree' => Yii::t('app', 'Durée'),
            'prix' => Yii::t('app', 'Prix'),
            'remarque' => Yii::t('app', 'Remarque'),
            'nb_client_non_inscrit' => Yii::t('app', 'Nombre de client sans inscription'),
            'baremeMoniteur' => Yii::t('app', 'Barème prestation'),
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
        return $this->hasOne(Cours::class, ['cours_id' => 'fk_cours']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFkLieu()
    {
        return $this->hasOne(Parametres::class, ['parametre_id' => 'fk_lieu']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientHasCours($personneID)
    {
        return ClientsHasCours::findOne(['fk_cours' => $this->fk_cours, 'fk_personne' => $personneID]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientsHasCoursDate()
    {
        return $this->hasMany(ClientsHasCoursDate::class, ['fk_cours_date' => 'cours_date_id']);
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
        return $this->hasMany(CoursHasMoniteurs::class, ['fk_cours_date' => 'cours_date_id']);
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
        return ClientsHasCours::find()->where(['fk_cours' => $this->fk_cours])->andWhere(['fk_statut' => Yii::$app->params['partInscrit']])->count();
    }

    /**
     * @return int Number of clients 2 cours essai
     */
    public function getNombreClients()
    {
        return ClientsHasCours::find()->where(['fk_cours' => $this->fk_cours])->andWhere(['IN', 'fk_statut', [Yii::$app->params['partInscrit'], Yii::$app->params['part2Essai']]])->count();
    }
    
    /**
     * @return string Nombre clients inscrits (Nombre clients 2 cours à l'essai)
     */
    public function getNombreClientsInscritsForDataGrid()
    {
        $partEssai = ClientsHasCours::find()->where(['fk_cours' => $this->fk_cours])->andWhere(['fk_statut' => Yii::$app->params['part2Essai']])->count();
        
        return ($partEssai != 0) ? $this->getNombreClientsInscrits().' ('.$partEssai.')' : $this->getNombreClientsInscrits();
    }
    
    public function getDateToSync($count = false, $limit = 0) {
        $query = self::find()
                ->where(['>=', 'date', date('Y.m.d')])
                ->andWhere(['IN', 'calendar_sync', [self::CALENDAR_NEW, self::CALENDAR_EDIT]])
                ->orderBy('fk_lieu, date ASC');
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

//    public function getDateToSyncManuel($count = false, $limit = 0) {
//        $coursDateID = [
//            7447,7197,6613,7439,7582,7601,7619,7565,7628,7650,7509,7574,7602,7629,7568,7688,
//            7630,7651,7519,7631,7632,7510,7645,7646,7652,7520,7647,7648,7653,7511,7512,7649,
//            7521,7633,7522,7634,7513,7635,7523,7636,7637,7524,7638,7639,7518,7640,7641,7642,
//            7525,7643,7644,7526,7527,7463,7468,7570,7542,7563,6411,7654,6412,7444,7569,7627,
//            6413,6414,6415,6416,6417,6418,6419,6420,6421,6422,6423,6424,6425,6426,6427,7202,
//            7219,7372,7236,7253,7270,7287,7603,7304,7321,7338,7389,7406,7203,7220,7373,7237,
//            7254,7271,7288,7604,7305,7322,7339,7390,7404,7204,7221,7374,7238,7255,7272,7289,
//            7605,7306,7323,7340,7599,7405,7205,7222,7375,7239,7256,7273,7290,7606,7307,7324,
//            7341,7395,7407,7206,7223,7376,7240,7257,7274,7291,7607,7308,7325,7342,7396,7685,
//            7207,7224,7377,7241,7258,7275,7292,7608,7309,7326,7343,7397,7686,7408,7209,7226,
//            7379,7243,7260,7277,7294,7609,7311,7328,7345,7392,7210,7227,7380,7244,7261,7278,
//            7295,7610,7312,7329,7346,7393,7211,7228,7381,7245,7262,7279,7296,7611,7313,7330,
//            7347,7394,7216,7587,7378,7575,7242,7259,7293,7581,7612,7319,7576,7336,7417,7212,
//            7229,7382,7246,7263,7280,7297,7613,7314,7331,7348,7401,7213,7230,7383,7247,7264,
//            7281,7298,7614,7315,7332,7349,7402,7214,7231,7384,7248,7265,7282,7299,7615,7316,
//            7333,7350,7413,7215,7232,7385,7249,7266,7283,7300,7616,7317,7334,7351,7414,7233,
//            7592,7386,7250,7267,7284,7301,7617,7318,7335,7352,7415,7217,7234,7387,7251,7268,
//            7285,7302,7618,7310,7588,7353,7409,7410,7411,7412
//        ];
//        $query = self::find()
//            ->where(['>=', 'date', '2019-12-18'])
//            ->andWhere(['IN', 'cours_date_id', $coursDateID])
//            ->orderBy('fk_lieu, date ASC');
//        if ($limit > 0) {
//            $query->limit($limit);
//        }
//        $modelCoursDate = $query->all();
//        if (true == $count) {
//            return count($modelCoursDate);
//        } else {
//            return $modelCoursDate;
//        }
//    }
    
    /**
     * 
     * @return string
     */
    public function getVCalendarString()
    {
        if (in_array($this->fkCours->fk_type, Yii::$app->params['coursPonctuelUnique'])) {
            $title = (isset($this->clientsHasCoursDate[0]) ? $this->clientsHasCoursDate[0]->fkPersonne->suivi_client.' '.$this->clientsHasCoursDate[0]->fkPersonne->societe.' '.$this->clientsHasCoursDate[0]->fkPersonne->nomPrenom : Yii::t('app', 'Client non défini'));
            $title .= ' ' . $this->fkCours->fkNom->nom . ' ' . $this->fkCours->session;
        } else {
            $title = $this->fkCours->fkNom->nom . ' ' . $this->fkCours->session;
            $title .= ('' != $this->fkCours->annee) ? '.'.$this->fkCours->annee : '';
        }
        // suppression des retours à la ligne "maudit" qui empêche l'insertion dans le calendrier
        $title = trim(preg_replace('/\s\s+/', ' ', $title));

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
