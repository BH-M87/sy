<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

use app\common\core\Regular;

class PsSteWard extends BaseModel
{
    public static $sex_info = [1 => '男', 2 => '女'];

    public static function tableName()
    {
        return 'ps_steward';
    }

    public function rules()
    {
        return [
            [['community_id','id'],'required','message' => '{attribute}不能为空!','on'=>['delete','edit','detail']],
            [['community_id','community_name','name' ,'mobile','sex'],'required','message' => '{attribute}不能为空!','on'=>['add','edit']],
            [['sex', 'evaluate', 'praise', 'create_at'], 'integer'],
            [['community_id','community_name'],'string','max'=>30],
            [['name'],'string','max'=>10],
            [['mobile'], 'match', 'pattern'=>self::MOBILE_PHONE_RULE, 'message'=>'联系电话必须是区号-电话格式或者手机号码格式'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                => '管家ID',
            'community_id'      => '小区ID',
            'community_name'    => '小区名称',
            'name'              => '管家名称',
            'mobile'            => '手机号',
            'sex'               => '性别',
            'evaluate'          => '评价总数',
            'praise'            => '好评数',
            'create_at'         => '新增时间',
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['create_at'],
                ],
                'value' => time()
            ],
        ];
    }
}
