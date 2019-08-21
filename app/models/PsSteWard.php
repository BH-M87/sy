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
            [['community_id','name' ,'mobile','sex',],'required','message' => '{attribute}不能为空!','on'=>['add','edit']],
            [['community_id', 'sex', 'evaluate', 'praise', 'create_at'], 'integer'],
            ['name', 'match', 'pattern' => Regular::string(1, 10),'message' => '{attribute}最长不超过10个汉字，且不能含字符', 'on' =>['add', 'edit']],
            [['mobile'], 'match', 'pattern'=>Regular::telOrPhone(), 'message'=>'联系电话必须是区号-电话格式或者手机号码格式'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => '管家ID',
            'community_id' => '小区ID',
            'name' => '管家名称',
            'mobile' => '手机号',
            'sex' => '性别',
            'evaluate' => 'Evaluate',
            'praise' => 'Praise',
            'create_at' => 'Create At',
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
