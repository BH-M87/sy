<?php

namespace app\models;

use common\core\F;
use common\core\PsCommon;
use common\core\Regular;
use phpDocumentor\Reflection\Types\Self_;
use Yii;
use yii\db\ActiveQuery;

class PsRoomUser extends BaseModel
{
    const UN_AUTH = 1;//未认证
    const AUTH = 2;//已认证
    const UNAUTH_OUT = 3;//未认证迁出
    const AUTH_OUT = 4;//已认证迁出

    const MOVE_IN = 1;//迁入
    const MOVE_OUT = 2;//迁出

    public static $identity_type = [
        '1' => '业主',
        '2' => '家人',
        '3' => '租客',
    ];

    public static $status_desc = [
        '1' => '迁入未认证',
        '2' => '迁入已认证',
        '3' => '未认证迁出',
        '4' => '已认证迁出',
    ];

    public static $face_desc = [
        '1' => '党员',
        '2' => '团员',
        '3' => '群众'
    ];

    public static $marry_status_desc = [
        '1' => '已婚',
        '2' => '未婚',
        '3' => '离异',
        '4' => '分居',
        '5' => '丧偶',
    ];

    public static $household_type_desc = [
        '1' => '非农业户口',
        '2' => '农业户口'
    ];

    public static function tableName()
    {
        return 'ps_room_user';
    }

    public function rules()
    {
        return [
            [['community_id', 'room_id', 'member_id', 'name', 'mobile', 'group', 'building', 'unit', 'room', 'identity_type',
                'status'], 'required'],
            [['community_id', 'room_id', 'member_id', 'sex', 'identity_type', 'status', 'auth_time', 'time_end', 'out_time',
                'operator_id', 'enter_time', 'nation', 'face', 'household_type', 'marry_status', 'household_province',
                'household_city', 'household_area', 'live_type', 'live_detail', 'change_detail', 'create_at', 'update_at'
            ], 'integer'],
            [['name', 'card_no', 'operator_name', 'emergency_contact'], 'string', 'max' => 20],
            ['card_no', 'match', 'pattern' => Regular::idCard(), 'message' => '不是合法的身份证号'],
            [['mobile'], 'string', 'max' => 12],
            ['mobile', 'match', 'pattern' => Regular::phone(), 'message' => '手机格式不正确'],
            [['group', 'building', 'unit', 'room'], 'string', 'max' => 64],
            [['reason', 'work_address', 'household_address', 'change_before', 'change_after'], 'string', 'max' => 255],
            [['telephone', 'emergency_mobile'], 'string', 'max' => 15],
            [['qq'], 'string', 'max' => 30],
            [['wechat', 'email'], 'string', 'max' => 50],
            [['residence_number'], 'string', 'max' => 100],
            [['time_end'], 'timeCheck'],
            [['name', 'mobile', 'identity_type'], 'required', 'on' => ['family', 'renter']],
            [['time_end'], 'default', 'value' => 0],
            [['manage_house_id','face_url'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'room_id' => '房屋',
            'member_id' => '用户ID',
            'name' => '业主名称',
            'sex' => '性别',
            'mobile' => '手机号',
            'card_no' => '身份证号',
            'group' => '苑期区',
            'building' => '幢',
            'unit' => '单元',
            'room' => '室号',
            'identity_type' => '身份',
            'status' => '认证状态',
            'auth_time' => '认证时间',
            'time_end' => '有效期',
            'operator_id' => '操作人ID',
            'operator_name' => '操作人名称',
            'enter_time' => '入住时间',
            'reason' => '入住原因',
            'work_address' => '工作单位',
            'qq' => 'QQ',
            'wechat' => '微信',
            'email' => '邮箱',
            'telephone' => '家庭电话',
            'emergency_contact' => '紧急联系人',
            'emergency_mobile' => '紧急联系电话',
            'nation' => '民族',
            'face' => '政治面貌',
            'household_type' => '户口类型',
            'marry_status' => '婚姻状态',
            'household_province' => '户口省份',
            'household_city' => '户口城市',
            'household_area' => '户口区',
            'household_address' => '户口地址',
            'residence_number' => '暂住证号码',
            'live_type' => '居住类型',
            'live_detail' => '居住情况',
            'change_detail' => '变动情况',
            'change_before' => '变动前地址',
            'change_after' => '变动后地址',
            'face_url' => '人脸头像',
            'create_at' => '创建时间',
            'update_at' => '更新时间',
        ];
    }

    public function timeCheck()
    {
        if ($this->time_end === null) {
            $this->addError('time_end', "有效期不能为空");
        }
        //迁出状态的保存不判断有效期
        if ($this->status != self::UNAUTH_OUT && $this->status != self::AUTH_OUT) {
            if ($this->identity_type == 3 && $this->time_end && ($this->time_end <= strtotime(date('Y-m-d 23:59:59')))) {
                $this->addError('time_end', '有效期必须大于当天');
            }
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->update_at = $this->create_at = time();
        } else {
            $this->update_at = time();
        }
        return true;
    }

    public function getCommunity()
    {
        return $this->hasOne(PsCommunityModel::className(), ['id' => 'community_id'])->select('id, name');
    }

    public function getRoomInfo()
    {
        return $this->hasOne(PsCommunityRoominfo::className(), ['id' => 'room_id'])
            ->select(['id', 'charge_area', 'floor', 'status', 'property_type', 'out_room_id']);
    }

    /*
     * zhouph 2019.5.22
     * 查询标签人员
     */
    public function getTags(){
        return $this->hasMany(PsRoomUserLabel::className(),['room_user_id'=>'id']);

    }

    /**
     * 列表查询
     * @param $params
     * @param $page
     * @param $pageSize
     * @return ActiveQuery
     */
    public function get($params, $emptyFilter = false)
    {
        if (empty($params['status']) && !empty($params['move_status'])) {
            if ($params['move_status'] == self::MOVE_IN) {
                $params['status'] = [self::UN_AUTH, self::AUTH];
            } elseif ($params['move_status'] == self::MOVE_OUT) {
                $params['status'] = [self::UNAUTH_OUT, self::AUTH_OUT];
            }
        }
        $model = PsRoomUser::find()
            ->andFilterWhere([
                'community_id' => PsCommon::get($params, 'community_id'),
                'room_id' => PsCommon::get($params, 'room_id'),
                'member_id' => PsCommon::get($params, 'member_id'),
                'mobile' => PsCommon::get($params, 'mobile'),
                'group' => PsCommon::get($params, 'group'),
                'building' => PsCommon::get($params, 'building'),
                'unit' => PsCommon::get($params, 'unit'),
                'room' => PsCommon::get($params, 'room'),
                'identity_type' => PsCommon::get($params, 'identity_type'),
                'status' => PsCommon::get($params, 'status'),
                'id' => PsCommon::get($params, 'id')
            ]);
        if (!empty($params['name'])) {
            $model->andFilterWhere(['or', ['like', 'name', $params['name']], ['like', 'mobile', $params['name']]]);
            if (PsCommon::isVirtualPhone($params['name']) === true) {
                $model->andWhere("mobile not like '120%'");
            }
        }

        if (empty($params['status'])) {
            $model->andFilterWhere(['status' => [self::AUTH, self::UN_AUTH]]);
        }
        if ($emptyFilter) {
            $model->andWhere("mobile not like '120%'");
        }
        return $model;
    }

    /**
     * 获取单条数据
     * @author yjh
     * @edit wyf
     * @param $where
     * @param string $field
     * @param bool $type true 返回模型 false数组
     * @return array|mixed|null|string|\yii\db\ActiveRecord
     */
    public static function getOne($where, $field = '*', $type = false)
    {
        $where = self::paramFilter($where['where']);
        $model = self::find()->select($field);
        $model->andWhere($where['where']);
        if ($type) {
            $data = $model->one();
        } else {
            $data = $model->asArray()->one();
        }
        return empty($data) ? '' : $data;
    }

    /**
     * 参数过滤
     * @author yjh
     * @edit wyf
     * @param $param
     * @return mixed
     */
    public static function paramFilter($param)
    {
        $model = self::model();
        $key = array_keys($model->attributes);
        foreach ($param as $k => $v) {
            if (!in_array($k, $key)) {
                if ($k != 'rows' && $k != 'page') {
                    unset($param[$k]);
                } else {
                    $param[$k] = $v;
                }
            } else {
                $param['where'][$k] = $v;
            }
        }
        return $param;
    }



    public static function roomConcatAll($param)
    {
        $fields = [
            'id'
        ];
        $contion = "1=1";
        //小区id
        if(!empty($param['community_id'])){
            $contion.=" and ps_room_user.community_id = ".$param['community_id'];
        }
        if(!empty($param['status'])){
            $contion.=" and ps_room_user.status in(".$param['status'].")";
        }
        $result = self::find()
            ->select($fields)
            ->where($contion)
            ->asArray()
            ->all();
        return $result;
    }

    public static function getCount($p)
    {
        return self::find()->select('distinct(mobile)')
            ->andFilterWhere(['=', 'community_id', $p['community_id']])
            ->andWhere(['in', 'status', [1,2]])
            ->andFilterWhere(['identity_type' => $p['identity_type']])
            ->andFilterWhere(['time_end' => $p['time_end']])->count();
    }

    /**
     * 获取列表
     * @author yjh
     * @param $param
     * @param bool $page
     * @param string $select
     * @return mixed
     */
    public static function getList($param,$select = '*',$page=true)
    {
        $model = self::find()->select($select)->andFilterWhere(['room_id' => $param['room_id'] ?? null]);
        $model->orderBy([ 'create_at' => SORT_DESC]);
        if ($page) {
            $page = !empty($param['page']) ? $param['page'] : 1;
            $row = !empty($param['rows']) ? $param['rows'] : 10;
            $page = ($page-1)*$row;
            $count = $model->count();
            $data['totals'] = $count;
            $model->offset($page)->limit($row);
        }
        $data['list'] = $model->asArray()->all();
        if (!empty($data['list'])) {
            self::afterList($data['list']);
        }
        return $data;
    }

    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $v['mobile'] = F::processMobile($v['mobile']);
            $v['card_no'] = F::processIdCard($v['card_no']);
            $v['expired_time'] = $v['time_end'] != 0 ? date('Y-m-d',$v['time_end']) : '长期有效';
            $v['identity'] = [
                'id' => $v['identity_type'],
                'name' => self::$identity_type[$v['identity_type']]
            ];
            $v['auth_status'] = self::$status_desc[$v['status']];
            $v['auth_time'] = $v['auth_time'] != 0 ? date('Y-m-d',$v['auth_time']) : '';
            $v['member_name'] = $v['name'];
            unset($v['status']);
            unset($v['time_end']);
            unset($v['identity_type']);
        }
    }
}
