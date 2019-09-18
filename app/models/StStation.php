<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_station".
 *
 * @property int $id 主键
 * @property string $station 先锋岗名称
 * @property string $content 描述
 * @property int $status 状态 1显示 2隐藏
 * @property int $operator_id 创建人id
 * @property string $operator_name 操作人名
 * @property int $create_at 创建时间
 */
class StStation extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_station';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'operator_id', 'create_at', 'organization_type'], 'integer'],
            [['station', 'content', 'status'], 'required', 'message' => '{attribute}不能为空','on' => ['add', 'edit']],
            [['station'], 'string', 'max' => 30],
            [['content'], 'string', 'max' => 200],
            [['operator_name'], 'string', 'max' => 20],
            [['station'], 'string', 'max' => 15, 'message' => '{attribute}最多15个字！','on' => ['add', 'edit']],
            [['content'], 'string', 'max' => 50, 'message' => '{attribute}最多50个字！','on' => ['add', 'edit']],
            [['status'],'in','range'=>[1,2], 'message' => '{attribute}只能是1或2','on' => ['add', 'edit', 'list', 'edit-status']],
            [['id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['edit','delete','view', 'edit-status']],
            [['status'], 'required', 'message' => '{attribute}不能为空','on' => ['edit-status']],
            [['organization_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '先锋岗id',
            'station' => '先锋岗名称',
            'content' => '岗位描述',
            'status' => '岗位状态',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
        ];
    }
}
