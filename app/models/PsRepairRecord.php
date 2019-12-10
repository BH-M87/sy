<?php

namespace app\models;

use common\core\PsCommon;
use common\core\Regular;
use service\issue\MaterialService;
use Yii;

/**
 * This is the model class for table "ps_repair_record".
 *
 * @property integer $id
 * @property integer $repair_id
 * @property string $content
 * @property integer $create_at
 * @property integer $operator_id
 * @property string $operator_name
 */
class PsRepairRecord extends BaseModel
{
    /**
     * @inheritdoc
     */
    public $community_id;
    public $num;
    public $price_unit;
    public $price;
    public $name;
    public $cate_id;
    public $material_id;
    public $group_id;

    public $expired_repair_time;
    public $repair_type;
    public $repair_content;

    public $building;
    public $group;
    public $room;
    public $unit;
    public $contact_mobile;
    public $finish_time;

    public $user_id;
    public $repair_from;

    public static function tableName()
    {
        return 'ps_repair_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['repair_id'], 'required','message' => '{attribute}不能为空!', 'on' => ['create','assign-repair']],
            ['content', 'string', 'max' => '200','message' => '{attribute}最多200个字符!', 'on' => 'create'],

            [['community_id'], 'required','message' => '小区id必填!', 'on' => ['add-material']],
            [['name'], 'required','message' => '材料名必填!', 'on' => ['add-material','edit-material']],
            [['cate_id'], 'required','message' => '材料分类必填!', 'on' => ['add-material','edit-material']],
            [['price'], 'required','message' => '材料价格必填!', 'on' => ['add-material','edit-material']],
            [['price_unit'], 'required','message' => '材料单位必填!', 'on' => ['add-material','edit-material']],
            ['name', 'match', 'pattern' => Regular::string(1, 20),
                'message' => '{attribute}最长不超过10个汉字，且不能含特殊字符', 'on' =>['add-material', 'edit-material','import-data']],
            ['price_unit', 'in', 'range' =>array_keys(MaterialService::$_material_type), 'message' => '材料单位错误', 'on' =>['add-material','edit-material']],
            ['price', 'double','message' => '材料价格错误!', 'on' =>['add-material','edit-material']],
            ['cate_id', 'in', 'range' => [1, 2],'message' => '材料分类不正确', 'on' =>['add-material','edit-material']],
            ['num', 'integer','message' => '材料数量错误!', 'on' =>['add-material','edit-material']],
            ['material_id', 'required','message' => '材料id不能为空!', 'on' =>['show-material','edit-material',"delete-material"]],

            [['expired_repair_time'], 'required','message' => '上门时间必填!', 'on' => ['add-repair1','add-repair2','add-repair3']],
            [['contact_name'], 'required','message' => '报修人必填!', 'on' => ['add-repair1','add-repair2','add-repair3']],
            [['repair_type'], 'required','message' => '报修类型必填!', 'on' => ['add-repair1','add-repair2','add-repair3']],
            [['repair_content'], 'required','message' => '报修内容必填!', 'on' => ['add-repair1','add-repair2','add-repair3']],
            [['repair_from'], 'required','message' => '报修来源不能为空!', 'on' => ['add-repair1','add-repair2','add-repair3']],
            [['content','repair_id','status'], 'required','message' => '{attribute}不能为空!', 'on' => ['review']],

            [['contact_mobile'], 'required','message' => '{attribute}必填!', 'on' => ['add-repair2']],
            [['group','unit',"building","room"], 'required','message' => '{attribute}必填!', 'on' => ['add-repair2','add-repair3']],

            [['group_id'], 'required','message' => '用户组必填!', 'on' => ['get-group-users']],
            [['user_id'], 'required','message' => '用户必填!', 'on' => ['assign-repair']],
            [['finish_time'], 'required','message' => '期望完成時間不能为空!', 'on' => ['assign-repair']],
            ['finish_time', 'compare', 'compareValue' => 0, 'message'=>'期望完成時間只能是正数','operator' => '>','on'=>'assign-repair'],
            ['finish_time', 'integer', 'message' => '期望完成時間只能是正数!', 'on' => 'assign-repair'],
            ['contact_mobile', 'match', 'pattern' => Regular::phone(),
                'message' => '{attribute}格式出错，必须是手机号码', 'on' => ['add-repair2']],
            ['repair_content', 'string', 'length' => [1, 200],'message' => '报修内容最长不超过200个汉字，且不能含特殊字符','on' =>['add-repair1','add-repair2','add-repair3']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'repair_id' => '报事报修id',
            'content' => '跟进内容',
            'create_at' => 'Create At',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'group' => '期/苑/区',
            'unit' => '单元',
            'room' => '室号',
            'building' => '幢',
            'contact_mobile' => '联系电话',
            'contact_name' => '报修人',
            'status'=>'操作类型',
        ];
    }
}
