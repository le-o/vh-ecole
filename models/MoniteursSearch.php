<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Moniteurs;

/**
 * MoniteursSearch represents the model behind the search form of `app\models\Moniteurs`.
 */
class MoniteursSearch extends Moniteurs
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['moniteur_id', 'fk_personne', 'no_cresus'], 'integer'],
            [['diplome', 'remarque', 'animateur_asse', 'instructeur_asse', 'encadrant_asse', 'referent_asse', 'expert_asse', 'parcours', 'methode_VCS', 'experience_cours', 'prof_escalade', 'js1_escalade', 'js2_escalade', 'js3_escalade', 'js_allround', 'js_expert'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
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
        $query = Moniteurs::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'moniteur_id' => $this->moniteur_id,
            'fk_personne' => $this->fk_personne,
            'no_cresus' => $this->no_cresus,
            'animateur_asse' => $this->animateur_asse,
            'instructeur_asse' => $this->instructeur_asse,
            'encadrant_asse' => $this->encadrant_asse,
            'referent_asse' => $this->referent_asse,
            'expert_asse' => $this->expert_asse,
            'parcours' => $this->parcours,
            'methode_VCS' => $this->methode_VCS,
            'experience_cours' => $this->experience_cours,
            'prof_escalade' => $this->prof_escalade,
            'js1_escalade' => $this->js1_escalade,
            'js2_escalade' => $this->js2_escalade,
            'js3_escalade' => $this->js3_escalade,
            'js_allround' => $this->js_allround,
            'js_expert' => $this->js_expert,
        ]);

        $query->andFilterWhere(['like', 'diplome', $this->diplome])
            ->andFilterWhere(['like', 'remarque', $this->remarque]);

        return $dataProvider;
    }
}
