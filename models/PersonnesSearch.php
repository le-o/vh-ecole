<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Personnes;

/**
 * PersonnesSearch represents the model behind the search form about `app\models\Personnes`.
 */
class PersonnesSearch extends Personnes
{
    public $fkStatut;
    public $list_langues;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['personne_id', 'fk_statut', 'fk_finance', 'fk_formation', 'fk_salle_admin'], 'integer'],
            [['suivi_client', 'societe', 'nom', 'prenom', 'adresse1', 'adresse2', 'npa', 'localite', 'telephone', 'telephone2',
                'email', 'date_naissance', 'informations', 'list_langues'], 'safe'],
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
    public function search($params, $withPagination = ['pageSize' => 20])
    {
        $query = Personnes::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => $withPagination,
            'sort'=> ['defaultOrder' => ['nom'=>SORT_ASC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if ($this->fk_type) {
            $query->andFilterWhere(['IN', 'fk_type', $this->fk_type]);
        }
        $query->andFilterWhere([
            'personne_id' => $this->personne_id,
            'fk_statut' => $this->fk_statut,
            'fk_finance' => $this->fk_finance,
            'fk_formation' => $this->fk_formation,
            'date_naissance' => $this->date_naissance,
            'fk_salle_admin' => $this->fk_salle_admin,
        ]);

        $query->andFilterWhere(['like', 'suivi_client', $this->suivi_client])
            ->andFilterWhere(['like', 'societe', $this->societe])
            ->andFilterWhere(['like', 'nom', $this->nom])
            ->andFilterWhere(['like', 'prenom', $this->prenom])
            ->andFilterWhere(['like', 'adresse1', $this->adresse1])
            ->andFilterWhere(['like', 'adresse2', $this->adresse2])
            ->andFilterWhere(['like', 'npa', $this->npa])
            ->andFilterWhere(['like', 'localite', $this->localite])
            ->andFilterWhere(['like', 'telephone', $this->telephone])
            ->andFilterWhere(['like', 'telephone2', $this->telephone2])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'informations', $this->informations]);

        return $dataProvider;
    }
    
    public function searchMoniteurs($params, $withPagination = ['pageSize' => 20])
    {
        $query = Personnes::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => $withPagination,
            'sort'=> ['defaultOrder' => ['nom'=>SORT_ASC]]
        ]);
        
        $dataProvider->sort->attributes['fkStatut'] = [
            'asc' => ['parametres.nom' => SORT_ASC, 'cours.session' => SORT_ASC],
            'desc' => ['parametres.nom' => SORT_DESC, 'cours.session' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
        $query->where(['IN', 'fk_type', Yii::$app->params['typeEncadrantActif']]);
        if (isset($params['fk_langues']) && $params['fk_langues'] != '') {
            $this->fk_langues = $params['fk_langues'];
            $query->andWhere(['LIKE', 'fk_langues', $this->fk_langues]);
        }

        $query->andFilterWhere([
            'personne_id' => $this->personne_id,
            'fk_statut' => $this->fk_statut,
            'fk_type' => $this->fk_type,
            'fk_formation' => $this->fk_formation,
            'date_naissance' => $this->date_naissance,
        ]);

        $query->andFilterWhere(['like', 'nom', $this->nom])
            ->andFilterWhere(['like', 'prenom', $this->prenom]);

        return $dataProvider;
    }
}
