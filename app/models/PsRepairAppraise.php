<?php

namespace app\models;

use Faker\Provider\Base;
use Yii;

/**
 * This is the model class for table "ps_repair_appraise".
 *
 * @property int $id
 * @property int $repair_id 报事报修id
 * @property int $start_num
 * @property string $appraise_labels 评价标签，多个以逗号隔开
 * @property string $content 评价内容
 * @property int $created_at 添加时间
 */
class PsRepairAppraise extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_repair_appraise';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['repair_id', 'start_num', 'created_at'], 'integer'],
            [['start_num', 'repair_id', 'appraise_labels'], 'required', 'message' => '{attribute}不能为空', 'on' => ['add']],
            [['appraise_labels', 'content'], 'string', 'max' => 255],
            ['start_num', 'in', 'range' => [1, 2, 3, 4, 5], 'message' => '{attribute}类型有误', 'on' => ['add']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'repair_id' => '工单id',
            'start_num' => '评价等级',
            'appraise_labels' => '评价标签',
            'content' => '评价内容',
            'created_at' => 'Created At',
        ];
    }
}
