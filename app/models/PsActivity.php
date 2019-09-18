<?php
namespace app\models;

use common\core\F;
use common\core\PsCommon;
use yii\behaviors\TimestampBehavior;

use common\core\Regular;
use common\MyException;

use app\models\PsActivityEnroll;

class PsActivity extends BaseModel
{
    public static $type = [1 => '小区活动', 2 => '邻里活动', 3 => '官方活动', 4 => '社区活动'];
    public static $status = [1 => '进行中', 2 => '已结束', 3 => '已取消', 4 => '未开始'];
    public static $activity_type = [1 => '群团活动', 2 => '志愿活动', 3 => '党建活动', 4 => '其他活动'];

    public static function tableName()
    {
        return 'ps_activity';
    }

    public function rules()
    {
        return [
            [['title', 'picture', 'link_name', 'link_mobile', 'join_end', 'start_time', 'end_time', 'description', 'address'], 'required', 'message' => '{attribute}必填'],
            [['link_name'],  'match', 'pattern' => Regular::string(1, 10), 'message' => '{attribute}最长不能超过10个字符'],
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

    // 获取单条数据
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

    // 获取列表
    public static function getList($p)
    {
        $page = $p['page'] ?? 1;
        $rows = $p['rows'] ?? 5;

        $p['join_start'] = !empty($p['join_start']) ? strtotime($p['join_start']) : null;
        $p['join_end'] = !empty($p['join_end']) ? strtotime($p['join_end'].' 23:59:59') : null;
        $p['activity_start'] = !empty($p['activity_start']) ? strtotime($p['activity_start']) : null;
        $p['activity_end'] = !empty($p['activity_end']) ? strtotime($p['activity_end'].' 23:59:59') : null;

        $m = self::find()->select(['id', 'title', 'start_time', 'end_time', 'join_end', 'status', 'address', 
            'link_name', 'link_mobile', 'join_number', 'is_top', 'activity_number', 'activity_type', 'picture', 'type'])
            ->filterWhere(['=', 'community_id', PsCommon::get($p,'community_id')])
            ->orFilterWhere(['in', 'organization_id', PsCommon::get($p,'organization_id')])
            ->andFilterWhere(['is_del' => 1])
            ->andFilterWhere(['=', 'type', PsCommon::get($p,'type')])
            ->andFilterWhere(['in', 'status', PsCommon::get($p,'status')])
            ->andFilterWhere(['=', 'activity_type', PsCommon::get($p,'activity_type')])
            ->andFilterWhere(['like', 'title', PsCommon::get($p,'title')])
            ->andFilterWhere(['or', ['like', 'link_name', PsCommon::get($p,'name') ?? null], ['like', 'link_mobile', PsCommon::get($p,'name') ?? null]])
            ->andFilterWhere(['>=', 'join_end', PsCommon::get($p,'join_start')])
            ->andFilterWhere(['<=', 'join_end', PsCommon::get($p,'join_end')])
            ->andFilterWhere(['>=', 'start_time', PsCommon::get($p,'activity_start')])
            ->andFilterWhere(['<=', 'end_time', PsCommon::get($p,'activity_end')]);

        $totals = $m->count();

        if ($totals > 0) {
            if (!empty($p['small'])) { // 小程序的列表
                $list = $m->orderBy('is_top desc, top_time desc, created_at desc')->offset(($page - 1) * $rows)->limit($rows)->asArray()->all();
            } else {
                $list = $m->orderBy('id desc')->offset(($page - 1) * $rows)->limit($rows)->asArray()->all();
            }
            self::afterList($list);
        }

        return ['list' => $list ?? [], 'totals' => $totals];
    }

    // 列表结果格式化
    public static function afterList(&$list)
    {
        foreach ($list as &$v) {
            $v['status'] = self::status($v);
            $v['picture'] = F::ossImagePath($v['picture']);
            $v['start_time'] = date('Y-m-d H:i', $v['start_time']);
            $v['end_time'] = date('Y-m-d H:i', $v['end_time']);
            $v['join_end'] = date('Y-m-d H:i', $v['join_end']);
            $v['status_desc'] = self::$status[$v['status']];
            $v['type_desc'] = self::$type[$v['type']];
            $v['activity_type_desc'] = !empty($v['activity_type']) ? self::$activity_type[$v['activity_type']] : '';
            $enroll = PsActivityEnroll::find()->select('user_id, name as user_name, avatar')
                ->where(['a_id' => $v['id']])->asArray()->all();
            $v['people_list'] = $enroll;
            $avatar_arr = [];
            if (!empty($enroll)) {
                foreach ($enroll as $val) {
                    $avatar_arr[] = !empty($val['avatar']) ? $val['avatar'] : 'http://static.zje.com/2019041819483665978.png';
                }
            }
            $v['join_info'] = $avatar_arr;
        }
    }
    
    // 活动状态
    public static function status($p)
    {
        if ($p['status'] == 3) {
            return 3;
        } else if ($p['end_time'] < time()) {
            return 2;
        } else if ($p['start_time'] > time()) {
            return 4;
        } else {
            return 1;
        }
    }

}
