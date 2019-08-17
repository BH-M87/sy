<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "st_corp_agent".
 *
 * @property int $id
 * @property string $corp_id 应用id
 * @property string $name 应用名字
 * @property string $agent_id 应用 agentid
 * @property string $app_key 企业appKey
 * @property string $app_secret 企业appSecret
 * @property int $created_at 添加时间
 */
class StCorpAgent extends \yii\db\ActiveRecord
{
    public $corp_name;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_corp_agent';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at'], 'integer'],
            [['corp_id', 'name', 'app_key'], 'string', 'max' => 40],
            [['agent_id', 'app_secret'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'corp_id' => 'Corp ID',
            'name' => 'Name',
            'agent_id' => 'Agent ID',
            'app_key' => 'App Key',
            'app_secret' => 'App Secret',
            'created_at' => 'Created At',
        ];
    }
}
