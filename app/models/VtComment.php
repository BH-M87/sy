<?php
namespace app\models;

use Yii;
use common\core\Regular;

class VtComment extends BaseModel
{
    public static function tableName()
    {
        return 'vt_comment';
    }

    public function rules()
    {
        return [
            [['activity_id', 'mobile', 'content', 'type', 'player_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add']],
            [['activity_id','player_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['record']],
            [['content'], 'string', 'max' => 140],
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
            'content' => '评论内容',
            'type' => '来源',
            'create_at' => '新增时间',
        ];
    }

    //选手被评论记录列表
    public function getRecord($params){
        $fields = ['m.member_id','c.mobile','c.type','c.content','c.create_at'];
        $model = self::find()->alias('c')
            ->leftJoin(['m'=>VtMember::tableName()],'m.mobile=c.mobile')
            ->select($fields)
            ->where(['=','c.player_id',$params['player_id']]);
        $model->andWhere(['=','c.activity_id',$params['activity_id']]);
        $count = $model->count();
        if(!empty($params['page'])||!empty($params['pageSize'])){
            $page = !empty($params['page'])?intval($params['page']):1;
            $pageSize = !empty($params['pageSize'])?intval($params['pageSize']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["c.id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return [
            'list'=>$result,
            'totals'=>$count
        ];
    }
}
