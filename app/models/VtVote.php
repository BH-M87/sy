<?php
namespace app\models;

use Yii;
use common\core\Regular;

class VtVote extends BaseModel
{

    public $typeMsg = ['1'=>'内部系统','2'=>'独立H5'];

    public static function tableName()
    {
        return 'vt_vote';
    }

    public function rules()
    {
        return [
            [['activity_id', 'mobile', 'type', 'player_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add']],
            [['activity_id','player_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['record']],
            [['player_id'], 'playerVerification', 'on' => ['record']],
            ['mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式出错', 'on' => ['add']],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'activity_id' => '活动ID',
            'player_id' => '选手ID',
            'mobile' => '手机号',
            'type' => '来源',
            'create_at' => '新增时间',
        ];
    }

    //选手验证
    public function playerVerification($attribute){
        if(!empty($this->player_id)){
            $res = VtPlayer::find()->select(['id'])->where(['=','id',$this->player_id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该选手不存在");
            }
        }
    }

    //选手被投票记录列表
    public function getRecord($params){
        $fields = ['m.member_id','v.mobile','v.type'];
        $model = self::find()->alias('v')
                        ->leftJoin(['m'=>VtMember::tableName()],'m.mobile=v.mobile')
                        ->select($fields)
                        ->where(['=','v.player_id',$params['player_id']]);
        $model->andWhere(['=','v.activity_id',$params['activity_id']]);
        $count = $model->count();
        if(!empty($params['page'])||!empty($params['pageSize'])){
            $page = !empty($params['page'])?intval($params['page']):1;
            $pageSize = !empty($params['pageSize'])?intval($params['pageSize']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["v.id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return [
            'list'=>$result,
            'totals'=>$count
        ];
    }
}
