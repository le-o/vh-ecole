<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sent_email".
 *
 * @property int $sent_email_id
 * @property string $from
 * @property string $to
 * @property string|null $bcc
 * @property string|null $sent_date
 * @property string $subject
 * @property string $body
 * @property string $email_params
 */
class SentEmail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sent_email';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['from', 'to', 'subject', 'body', 'email_params'], 'required'],
            [['sent_date'], 'safe'],
            [['body', 'email_params'], 'string'],
            [['from', 'subject'], 'string', 'max' => 50],
            [['to', 'bcc'], 'string', 'max' => 150],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'sent_email_id' => Yii::t('app', 'Sent Email ID'),
            'from' => Yii::t('app', 'From'),
            'to' => Yii::t('app', 'To'),
            'bcc' => Yii::t('app', 'Bcc'),
            'sent_date' => Yii::t('app', 'Sent Date'),
            'subject' => Yii::t('app', 'Subject'),
            'body' => Yii::t('app', 'Body'),
            'email_params' => Yii::t('app', 'Email Params'),
        ];
    }
}
