<?php
namespace app\models;

use yii\behaviors\TimestampBehavior;

use common\core\Regular;
use common\MyException;

class PsActivity extends BaseModel
{
    public static $type = [1 => '小区活动', 2 => '邻里活动', 3 => '官方活动', 4 => '社区活动'];
    public static $status = [1 => '进行中', 2 => '已结束', 3 => '已取消'];
    public static $activity_type = [1 => '群团活动', 2 => '志愿活动', 3 => '党建活动', 4 => '其他活动'];

    public static function tableName()
    {
        return 'ps_activity';
    }

    public function rules()
    {
        return [
            [['title', 'picture', 'link_name', 'link_mobile', 'join_end', 'start_time', 'end_time', 'description', 'address'], 'required', 'message' => '{attribute}必填'],
            [['link_name'],  'match', 'pattern' => Regular::string(1, 10), 'message' => '{attribute}最长不超过10个汉字，且不能含字符'],
            ['link_mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}必须是手机号'],
            [['end_time', 'start_time', 'join_end'], 'timeVerify'],
            [['community_id', 'room_id', 'join_end', 'start_time', 'end_time', 'join_number', 'activity_number', 'status', 'is_top', 'type', 'is_del', 'operator_id', 'updated_at', 'organization_type', 'organization_id'], 'integer', 'message'=> '{attribute}不是数字'],
            [['title', 'address'], 'string', 'max' => 30],
            [['activity_number'], 'integer', 'max' => 300],

            [['created_at'], 'default', 'on' => ['add', 'streetAdd'], 'value' => time()],

            [['id'], 'required', 'on' => ['edit', 'streetEdit'], 'message' => '{attribute}必填'],        

            [['activity_type'], 'required', 'on' => ['streetAdd', 'streetEdit'], 'message' => '{attribute}必填'],
        ];
    }

    // 时间验证
    public function timeVerify()
    {   
        if ($this->end_time < $this->start_time) {
            $this->addError('', '活动结束时间必须大于活动开始时间');
        }

        if ($this->end_time < $this->join_end) {
            $this->addError('', '活动结束必须大于报名截止时间');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => '活动ID',
            'organization_type' => '所属组织类型',
            'organization_id' => '所属组织ID',
            'community_id' => '小区ID',
            'room_id' => '房屋ID',
            'title' => '活动主题',
            'picture' => '活动封面图',
            'link_name' => '联系人',
            'link_mobile' => '联系电话',
            'address' => '活动地点',
            'join_end' => '报名截止时间',
            'start_time' => '活动开始时间',
            'end_time' => '活动结束时间',
            'join_number' => '报名人数', 
            'activity_number' => '活动人数',
            'type' => '发起方类型',
            'status' => '状态',
            'is_top' => '置顶状态',
            'top_time' => '置顶时间',
            'is_del' => '是否删除',
            'description' => '活动描述',
            'operator_id' => '操作人ID',
            'created_at' => '新增时间',
            'updated_at' => '修改时间',
            'activity_type' => '活动类型'
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
    public static function getOne($p)
    {
        $m = self::find()->where(['is_del' => 1, 'id' => $p['id']])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andFilterWhere(['=', 'type', $p['type']])->one();
        if (empty($m)) {
            throw new MyException('数据不存在');
        }
        return $m;
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
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}
