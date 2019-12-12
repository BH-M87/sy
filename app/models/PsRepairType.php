<?php
namespace app\models;
use Yii;

/**
 * This is the model class for table "ps_repair_type".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 * @property integer $level
 * @property integer $parent_id
 * @property integer $is_relate_room
 * @property integer $status
 * @property integer $created_at
 */
class PsRepairType extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_repair_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'level', 'parent_id', 'is_relate_room', 'icon_url', 'status', 'created_at'], 'integer'],
            [['name'], 'string', 'max' => 20],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['status','edit']],
            [['status'], 'required','message' => '{attribute}不能为空!', 'on' => ['status']],
            ['status', 'in', 'range' => [1, 2, 3]],
            [['community_id','name','level'], 'required','message' => '{attribute}不能为空!', 'on' => ['add','edit']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '类别Id',
            'community_id' => '小区Id',
            'name' => '类别名称',
            'level' => '类别属性',
            'parent_id' => '父级类别',
            'icon_url' => '类目图片',
            'is_relate_room' => '是否关联房屋信息',
            'status' => '显示/隐藏',
            'created_at' => 'Created At',
        ];
    }
}
