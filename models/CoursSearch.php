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
    public $fkSaison;
    public $fkSemestre;
    public $fkStatut;
    public $fkJours;
    public $isPriorise;
    
    public $bySalle;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cours_id', 'participant_min', 'participant_max'], 'integer'],
            [['duree', 'prix'], 'double'],
            [['description', 'annee', 'session', 'fkNiveau', 'fkType', 'fkNom', 'fkSaison', 'fkStatut'], 'safe'],
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
    public function search($params, $pageSize = 60)
    {
        $query = Cours::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pagesize' => $pageSize,
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
            'annee' => $this->annee,
            'prix' => $this->prix,
            'participant_min' => $this->participant_min,
            'participant_max' => $this->participant_max,
            'fk_statut' => $this->fkStatut,
            'is_publie' => $this->is_publie,
            'fk_saison' => $this->fkSaison,
        ])
        ->andFilterWhere(['IN', 'fk_salle', $this->bySalle])
        ->andFilterWhere(['like', 'session', $this->session]);
        
//        $query->andWhere(['IN', 'fk_salle', $this->bySalle]);
        
        if ($this->isPriorise == true) {
            $query->andWhere(['NOT', ['tri_internet' => null]]);
            $query->orderBy('tri_internet');
        }
        
        $query->joinWith(['fkLangue' => function($langue) {
            $langue->alias('langue');
        }]);
        $query->joinWith(['fkAge' => function($age) {
            $age->alias('age');
        }]);

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
            'asc' => ['nom.nom' => SORT_ASC],
            'desc' => ['nom.nom' => SORT_DESC],
        ];
        $query->joinWith(['fkSaison' => function ($nom) {
            $nom->alias('saison');
//            $nom->where('saison.nom LIKE "%'.$this->fkSaison.'%"');
        }]);
        $dataProvider->sort->attributes['fkSaison'] = [
            'asc' => ['saison.nom' => SORT_ASC],
            'desc' => ['saison.nom' => SORT_DESC],
        ];
        $query->joinWith(['fkStatut' => function ($nom) {
            $nom->alias('statut');
        }]);
        $dataProvider->sort->attributes['fkStatut'] = [
            'asc' => ['statut.nom' => SORT_ASC],
            'desc' => ['statut.nom' => SORT_DESC],
        ];
        $query->joinWith(['fkSemestre' => function ($nom) {
            $nom->alias('semestre');
//            $nom->where('semestre.nom LIKE "%'.$this->fkSemestre.'%"');
        }]);
        $dataProvider->sort->attributes['fkSemestre'] = [
            'asc' => ['semestre.nom' => SORT_ASC],
            'desc' => ['semestre.nom' => SORT_DESC],
        ];
        $dataProvider->sort->defaultOrder = ['fk_statut'=>SORT_ASC, 'fkNom'=>SORT_ASC];

        return $dataProvider;
    }
}
