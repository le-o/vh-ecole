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
    public $anniversairepage = false;
    public $withoutMoniteur = true;
    public $fkTypeCours;
    public $fkSalle;

    public $listCours;

    public $fkNom;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cours_date_id', 'fk_cours', 'fk_lieu', 'fkTypeCours', 'fkSalle'], 'integer'],
            [['withoutMoniteur'], 'boolean'],
            [['date', 'heure_debut', 'duree', 'prix', 'remarque', 'nb_client_non_inscrit', 'fkCours', 'participantMin', 'participantMax', 'session', 'depuis', 'dateA', 'fkNom', 'fkTypeCours', 'fkSalle'], 'safe'],
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
    public function search($params, $pagesize = 80)
    {
        $query = CoursDate::find();
        $query->joinWith(['fkCours.fkNom coursNom', 'fkCours.fkType coursType', 'fkCours.fkSalle coursSalle', 'coursHasMoniteurs']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['date' => SORT_ASC]],
            'pagination' => [
                'pagesize' => $pagesize,
            ],
        ]);
        
        $dataProvider->sort->attributes['fkNom'] = [
            'asc' => ['coursNom.nom' => SORT_ASC, 'cours.session' => SORT_ASC],
            'desc' => ['coursNom.nom' => SORT_DESC, 'cours.session' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['fkTypeCours'] = [
            'asc' => ['coursType.nom' => SORT_ASC, 'cours.session' => SORT_ASC],
            'desc' => ['coursType.nom' => SORT_DESC, 'cours.session' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['fkSalle'] = [
            'asc' => ['coursSalle.nom' => SORT_ASC, 'cours.session' => SORT_ASC],
            'desc' => ['coursSalle.nom' => SORT_DESC, 'cours.session' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['session'] = [
            'asc' => ['cours.session' => SORT_ASC, 'coursNom.nom' => SORT_ASC],
            'desc' => ['cours.session' => SORT_DESC, 'coursNom.nom' => SORT_DESC],
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
            'fk_lieu' => $this->fk_lieu,
            'date' => $this->date,
            'heure_debut' => $this->heure_debut,
            'duree' => $this->duree,
            'prix' => $this->prix,
            'cours.participant_min' => $this->participantMin,
            'cours.participant_max' => $this->participantMax,
        ]);

        $query->andFilterWhere(['like', 'remarque', $this->remarque]);
        $query->andFilterWhere(['like', 'nb_client_non_inscrit', $this->nb_client_non_inscrit]);
        $query->andFilterWhere(['like', 'coursNom.nom', $this->fkCours]);
        $query->andFilterWhere(['like', 'cours.session', $this->session]);
        $query->andFilterWhere(['like', 'cours.fk_type', $this->fkTypeCours]);
        $query->andFilterWhere(['like', 'cours.fk_salle', $this->fkSalle]);


        if (!empty($this->listCours)) {
            $query->andWhere(['IN', 'fk_cours', $this->listCours]);
        }

        if ($this->depuis != '') {
            $query->andWhere("date >= '".date('Y-m-d', strtotime($this->depuis))."'");
            if (true == $this->anniversairepage) {
                $query->andWhere("cours.is_publie = 1 AND cours.fk_statut = " . Yii::$app->params['coursActif']);
            }
            if ($this->homepage == true) {
                $query->distinct = true;
                $query->select = ['fk_cours'];
            }
        }
        if ($this->dateA != '') {
            $query->andWhere("date <= '".date('Y-m-d', strtotime($this->dateA))."'");
            if ($this->homepage == true) {
                $query->andWhere("cours.fk_statut = " . Yii::$app->params['coursActif']);
                $query->andWhere("fk_cours NOT IN (SELECT DISTINCT(d2.fk_cours) FROM cours_date d2 WHERE d2.date > '".date('Y-m-d', strtotime($this->dateA))."')");
                $query->distinct = true;
                $query->select = ['fk_cours'];
            }
        }

        if (true == $this->withoutMoniteur) {
            $query->andFilterWhere(['cours_has_moniteurs.fk_moniteur' => Yii::$app->params['sansEncadrant']]);
        }

        return $dataProvider;
    }
}
