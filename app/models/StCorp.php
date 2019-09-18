<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_corp".
 *
 * @property int $id
 * @property string $corp_name 企业名称
 * @property string $corp_id 企业id
 * @property int $company_id 物业公司id
 * @property int $created_at 添加时间
 */
class StCorp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_corp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id', 'created_at'], 'integer'],
            [['corp_name'], 'string', 'max' => 50],
            [['corp_id'], 'string', 'max' => 40],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'corp_name' => 'Corp Name',
            'corp_id' => 'Corp ID',
            'company_id' => 'Company ID',
            'created_at' => 'Created At',
        ];
    }
}
