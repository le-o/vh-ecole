<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Cours;

/**
 * CoursSearch represents the model behind the search form about `app\models\Cours`.
 */
class CoursSearch extends Cours
{
    public $fkNiveau;
    public $fkType;
    public $fkNom;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cours_id', 'participant_min', 'participant_max', 'session', 'is_actif'], 'integer'],
            [['duree', 'prix'], 'double'],
            [['description', 'annee', 'fkNiveau', 'fkType', 'fkNom'], 'safe'],
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
        $query = Cours::find();
//        $query->joinWith(['fkNiveau', 'fkType', 'fkNom']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['is_actif'=>SORT_DESC, 'fkNom'=>SORT_ASC]],
            'pagination' => [
                'pagesize' => 80,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'cours_id' => $this->cours_id,
            'duree' => $this->duree,
            'session' => $this->session,
            'annee' => $this->annee,
            'prix' => $this->prix,
            'participant_min' => $this->participant_min,
            'participant_max' => $this->participant_max,
            'is_actif' => $this->is_actif,
        ]);

        $query->andFilterWhere(['like', 'description', $this->description]);
        
        $query->joinWith(['fkNiveau' => function ($niveau) {
            $niveau->alias('niveau');
            $niveau->where('niveau.nom LIKE "%'.$this->fkNiveau.'%"');
        }]);
        $dataProvider->sort->attributes['fkNiveau'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['niveau.nom' => SORT_ASC],
            'desc' => ['niveau.nom' => SORT_DESC],
        ];
        $query->joinWith(['fkType' => function ($type) {
            $type->alias('type');
            $type->where('type.nom LIKE "%'.$this->fkType.'%"');
        }]);
        $dataProvider->sort->attributes['fkType'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['type.nom' => SORT_ASC],
            'desc' => ['type.nom' => SORT_DESC],
        ];
        $query->joinWith(['fkNom' => function ($nom) {
            $nom->alias('nom');
            $nom->where('nom.nom LIKE "%'.$this->fkNom.'%"');
        }]);
        $dataProvider->sort->attributes['fkNom'] = [
            // The tables are the ones our relation are configured to
            // in my case they are prefixed with "tbl_"
            'asc' => ['nom.nom' => SORT_ASC],
            'desc' => ['nom.nom' => SORT_DESC],
        ];

        return $dataProvider;
    }
}
