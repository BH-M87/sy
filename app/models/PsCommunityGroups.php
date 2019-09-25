<?php
namespace app\models;
use Yii;

/**
 * This is the model class for table "ps_community_groups".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 */
class PsCommunityGroups extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_community_groups';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id','community_id'], 'integer'],
            [['name'], 'string', 'max' => 15],
            [['groups_code'], 'string', 'max' => 20],
            [['code'], 'string', 'max' => 2],
            [['community_id', 'name'], 'required', 'on' => ['add', 'edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['edit']],
            ['name', 'string', 'max' => '15', 'on' => ['add','edit']],
            [['groups_code'], 'checkCode', 'on' => ['add', 'edit']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '区域id',
            'community_id' => '小区id',
            'name' => '区域名称',
        ];
    }

    //获得小区幢列表
    public function getBuilding(){
        return $this->hasMany(PsCommunityBuilding::className(),['group_id'=>'id']);
    }

    //校验区域编码
    public function checkCode($attribute)
    {
        $groupCode = $this->$attribute;
        if(!empty($groupCode)){
            if(!is_numeric($groupCode)){
                $this->addError($attribute,'苑/期/区编码2位，只可为数字');
            }

        }

    }

}