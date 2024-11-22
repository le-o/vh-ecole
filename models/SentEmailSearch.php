<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\SentEmail;

/**
 * SentEmailSearch represents the model behind the search form of `app\models\SentEmail`.
 */
class SentEmailSearch extends SentEmail
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sent_email_id'], 'integer'],
            [['from', 'to', 'bcc', 'sent_date', 'subject', 'body', 'email_params'], 'safe'],
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
        $query = SentEmail::find();

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
            'sent_email_id' => $this->sent_email_id,
        ]);

        $query->andFilterWhere(['like', 'from', $this->from])
            ->andFilterWhere(['like', 'to', $this->to])
            ->andFilterWhere(['like', 'bcc', $this->bcc])
            ->andFilterWhere(['like', 'sent_date', $this->sent_date])
            ->andFilterWhere(['like', 'subject', $this->subject])
            ->andFilterWhere(['like', 'body', $this->body])
            ->andFilterWhere(['like', 'email_params', $this->email_params]);

        $query->orderBy('sent_email_id DESC');

        return $dataProvider;
    }
}
