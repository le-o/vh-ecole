<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CoursDate;

/**
 * CoursDateSearch represents the model behind the search form about `app\models\CoursDate`.
 */
class CoursDateSearch extends CoursDate
{
	public $fkCours;
	public $participantMin;
	public $participantMax;
    public $session;
    public $depuis;
    public $dateA;
    public $homepage = false;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cours_date_id', 'fk_cours'], 'integer'],
            [['date', 'heure_debut', 'lieu', 'duree', 'prix', 'remarque', 'nb_client_non_inscrit', 'fkCours', 'participantMin', 'participantMax', 'session', 'depuis', 'dateA'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = CoursDate::find();
        $query->joinWith(['fkCours.fkNom']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['date' => SORT_ASC]],
            'pagination' => [
                'pagesize' => 80,
            ],
        ]);
        
        $dataProvider->sort->attributes['fkCours'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['cours.nom' => SORT_ASC],
            'desc' => ['cours.nom' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['participantMin'] = [
            'asc' => ['cours.participant_min' => SORT_ASC],
            'desc' => ['cours.participant_min' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['participantMax'] = [
            'asc' => ['cours.participant_max' => SORT_ASC],
            'desc' => ['cours.participant_max' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'cours_date_id' => $this->cours_date_id,
            'fk_cours' => $this->fk_cours,
            'date' => $this->date,
            'heure_debut' => $this->heure_debut,
            'duree' => $this->duree,
            'prix' => $this->prix,
            'cours.participant_min' => $this->participantMin,
            'cours.participant_max' => $this->participantMax,
        ]);

        $query->andFilterWhere(['like', 'lieu', $this->lieu]);
        $query->andFilterWhere(['like', 'remarque', $this->remarque]);
        $query->andFilterWhere(['like', 'nb_client_non_inscrit', $this->nb_client_non_inscrit]);
        $query->andFilterWhere(['like', 'parametres.nom', $this->fkCours]);
        $query->andFilterWhere(['like', 'cours.session', $this->session]);
        
        if ($this->depuis != '') {
            $query->andWhere("date >= '".date('Y-m-d', strtotime($this->depuis))."'");
            if ($this->homepage == true) {
                $query->distinct = true;
                $query->select = ['fk_cours'];
            }
        }
        if ($this->dateA != '') {
            $query->andWhere("date <= '".date('Y-m-d', strtotime($this->dateA))."'");
        }

        return $dataProvider;
    }
}
