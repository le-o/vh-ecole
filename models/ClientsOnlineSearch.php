<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\ClientsOnline;

/**
 * ClientsOnlineSearch represents the model behind the search form about `app\models\ClientsOnline`.
 */
class ClientsOnlineSearch extends ClientsOnline
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['client_online_id', 'fk_parent', 'fk_cours', 'is_actif'], 'integer'],
            [['nom', 'prenom', 'adresse', 'npa', 'localite', 'telephone', 'email', 'date_naissance', 'informations', 'date_inscription'], 'safe'],
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
        $query = ClientsOnline::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['date_inscription'=>SORT_DESC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'client_online_id' => $this->client_online_id,
            'fk_parent' => $this->fk_parent,
            'fk_cours' => $this->fk_cours,
            'date_naissance' => $this->date_naissance,
            'date_inscription' => $this->date_inscription,
            'is_actif' => $this->is_actif,
        ]);

        $query->andFilterWhere(['like', 'nom', $this->nom])
            ->andFilterWhere(['like', 'prenom', $this->prenom])
            ->andFilterWhere(['like', 'adresse', $this->adresse])
            ->andFilterWhere(['like', 'npa', $this->npa])
            ->andFilterWhere(['like', 'localite', $this->localite])
            ->andFilterWhere(['like', 'telephone', $this->telephone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'informations', $this->informations]);

        return $dataProvider;
    }
}
