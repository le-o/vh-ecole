<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Parametres;

/**
 * ParametresSearch represents the model behind the search form about `app\models\Parametres`.
 */
class ParametresSearch extends Parametres
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parametre_id', 'class_key', 'tri'], 'integer'],
            [['nom', 'valeur', 'info_special', 'date_fin_validite'], 'safe'],
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
        $query = Parametres::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'parametre_id' => $this->parametre_id,
            'class_key' => $this->class_key,
            'tri' => $this->tri,
            'date_fin_validite' => $this->date_fin_validite,
        ]);

        $query->andFilterWhere(['like', 'nom', $this->nom])
            ->andFilterWhere(['like', 'valeur', $this->valeur])
            ->andFilterWhere(['like', 'info_special', $this->info_special]);

        return $dataProvider;
    }
}
