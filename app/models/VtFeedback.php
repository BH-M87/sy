<?php
namespace app\models;

use Yii;
use common\core\Regular;

class VtFeedback extends BaseModel
{
    public static function tableName()
    {
        return 'vt_feedback';
    }

    public function rules()
    {
        return [
            [['activity_id', 'mobile', 'content', 'type'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add']],
            [['activity_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['record']],
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
            'mobile' => '手机号',
            'content' => '反馈内容',
            'type' => '来源',
            'create_at' => '新增时间',
        ];
    }

    //活动反馈记录列表
    public function getRecord($params){
        $fields = ['m.member_id','f.mobile','f.type','f.content','f.create_at'];
        $model = self::find()->alias('f')
            ->leftJoin(['m'=>VtMember::tableName()],'m.mobile=f.mobile')
            ->select($fields)
            ->where(['=','f.activity_id',$params['activity_id']]);
        $count = $model->count();
        if(!empty($params['page'])||!empty($params['pageSize'])){
            $page = !empty($params['page'])?intval($params['page']):1;
            $pageSize = !empty($params['pageSize'])?intval($params['pageSize']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["f.id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return [
            'list'=>$result,
            'totals'=>$count
        ];
    }
}
