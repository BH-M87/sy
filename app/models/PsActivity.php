<?php
namespace app\models;

use yii\behaviors\TimestampBehavior;

use common\core\Regular;

class PsActivity extends BaseModel
{
    public static $status_desc = [1 => '进行中', 2 => '已结束', 3 => '取消'];

    public static function tableName()
    {
        return 'ps_activity';
    }

    public function rules()
    {
        return [
            [['id','community_id'],'required','message'=>'{attribute}不能为空!','on' => 'backend_edit'],
            [['activity_number','is_top','address'],'required','message'=>'{attribute}不能为空!','on' => ['backend_add','backend_edit', 'add']],
            [['community_id', 'title', 'picture', 'link_name', 'link_mobile', 'join_end', 'start_time', 'end_time', 'description'], 'required','message'=>'{attribute}不能为空!','on' => ['backend_add','backend_edit', 'add']],
            [['community_id', 'room_id', 'join_end', 'start_time', 'end_time', 'join_number', 'activity_number', 'status', 'is_top', 'type','is_del', 'operator_id', 'created_at', 'updated_at'], 'integer','message'=> '{attribute}格式错误!'],
            [['title', 'address'], 'string', 'max' => 30],
            [['activity_number'], 'integer', 'max' => 300,'on' => ['backend_add','backend_edit']],
            [['link_name'],  'match', 'pattern' => Regular::string(1, 10),'message' => '{attribute}最长不超过10个汉字，且不能含字符','on' => ['backend_add','backend_edit', 'add']],
            [['link_mobile'], 'match', 'pattern'=>Regular::telOrPhone(), 'message'=>'联系电话必须是区号-电话格式或者手机号码格式','on' => ['backend_add','backend_edit', 'add']],
            [['activity_number'], 'integer', 'max' => 100,'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'room_id' => '房屋ID',
            'title' => '活动主题',
            'picture' => '活动封面图',
            'link_name' => '联系人',
            'link_mobile' => '联系人手机号',
            'address' => '活动地点',
            'join_end' => '报名截止时间',
            'start_time' => '活动开始时间',
            'end_time' => '活动结束时间',
            'join_number' => '报名人数', //0表示不限制
            'activity_number' => '活动人数',
            'type' => '1:物业端 2:业主端 3:运营端',
            'status' => '1:进行中 2:已结束 3:取消',
            'is_top' => '置顶状态',//1:不顶置 2:顶置
            'top_time' => '置顶时间',
            'is_del' => '1:未删除 2:已删除',
            'description' => '活动描述',
            'operator_id' => '操作人id',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => time()
            ],
        ];
    }

    // 获取列表
    public static function getList($params, $field, $page = true)
    {
        $activity = PsActivity::find()->select($field)
            ->where(['community_id' => $params['community_id'],'is_del' => 1,'type' => 1])
            ->andFilterWhere(['status' => $params['status']])
            ->andFilterWhere(['like', 'title', $params['title'] ?? null])
            ->andFilterWhere(['or', ['like', 'link_name', $params['name'] ?? null], ['like', 'link_mobile', $params['name'] ?? null]])
            ->andFilterWhere(['and', ['>=', 'join_end', $params['join_start'] ?? null], ['<=', 'join_end', $params['join_end'] ?? null]])
            ->andFilterWhere(['and', ['>=', 'start_time',$params['activity_start'] ?? null ], ['<=', 'end_time',$params['activity_end'] ?? null]]);
        $count = $activity->count();
        if ($count > 0) {
            $activity->orderBy('id desc');
            if ($page) {
                $activity->offset((($params['page'] ?? 1) - 1) * ($params['rows'] ?? 10))->limit($params['rows'] ?? 10);
            }
            $data = $activity->asArray()->all();
            self::afterList($data);
        }

        return ['totals' => $count, 'list' => $data ?? []];
    }

    // 获取后台数据
    public static function getBackendOne($where)
    {
        return self::find()->where($where)->andWhere(['is_del' => 1, 'type' => 1])->one();
    }

    // 列表结果格式化
    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $v['activity_title'] = $v['title'];
            $v['start_time'] = date('Y-m-d H:i',$v['start_time']);
            $v['end_time'] = date('Y-m-d H:i',$v['end_time']);
            $v['join_end'] = date('Y-m-d H:i',$v['join_end']);
            $v['status_desc'] = PsActivity::$status_desc[$v['status']];
        }
    }

    // 新增 编辑
    public function saveData($scenario, $param)
    {
        if ($scenario == 'edit') {
            self::updateAll($param, ['id' => $param['id']]);
            return true;
        }
        return $this->save();
    }
}
