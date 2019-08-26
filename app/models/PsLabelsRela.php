<?php
namespace app\models;

use Yii;

class PsLabelsRela extends BaseModel
{     
    public static function tableName()
    {
        return 'ps_labels_rela';
    }

    public function rules()
    {
        return [
            [['community_id', 'labels_id', 'data_id', 'data_type'], 'required', 'on' => ['add', 'edit']],
            [['community_id', 'labels_id', 'data_id', 'data_type'], 'integer'],
            ['data_type', 'in', 'range' => [1,2,3]],
            ['created_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'labels_id' => '标签id',
            'data_id' => '数据id',
            'data_type' => '数据类型',
            'created_at' => '新曾时间',
        ];
    }

    // 新增 编辑
    public function saveData()
    {
        return $this->save();
    }
}
