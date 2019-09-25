<?php

namespace app\models;

use common\core\PsCommon;
use common\core\Regular;
use Yii;

/**
 * This is the model class for table "ps_resident_audit".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $member_id
 * @property integer $room_id
 * @property string $name
 * @property string $mobile
 * @property string $card_no
 * @property string $images
 * @property integer $identity_type
 * @property integer $time_end
 * @property integer $status
 * @property string $reason 审核不通过原因
 * @property integer $operator
 * @property string $operator_name
 * @property integer $create_at
 * @property integer $update_at
 * @property integer $accept_at
 */
class PsResidentAudit extends BaseModel
{
    const AUDIT_WAIT = 0;
    const AUDIT_PASS = 1;
    const AUDIT_NO_PASS = 2;

    public static $status = [
        0 => '审核',
        1 => '通过',
        2 => '失败'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_resident_audit';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'member_id', 'room_id', 'name', 'mobile', 'identity_type'], 'required'],
            [['community_id', 'member_id', 'room_id', 'identity_type', 'time_end', 'status', 'create_at', 'accept_at',
                'update_at', 'sex', 'unaccept_at'], 'integer'],
            [['images'], 'string'],
            [['operator'], 'safe'],
            [['name', 'operator_name'], 'string', 'max' => 50],
            [['mobile'], 'match', 'pattern' => Regular::phone(), 'message' => '手机号格式出错'],
            [['reason'], 'string', 'max' => 50],
            [['card_no'], 'string', 'max' => 20],
            [['card_no'], 'cardNoCheck'],
            [['time_end'], 'timeCheck'],
            [['time_end', 'status'], 'default', 'value' => 0],
        ];
    }

    public function timeCheck()
    {
        if ($this->status == self::AUDIT_NO_PASS) {
            return true;
        }
        if ($this->time_end === null) {
            $this->addError('time_end', "有效期不能为空");
        }
        if ($this->identity_type == 3 && $this->time_end && ($this->time_end <= strtotime(date('Y-m-d 23:59:59')))) {
            $this->addError('time_end', '有效期必须大于当天');
        }
    }

    public function cardNoCheck()
    {
        $card_no = $this->card_no;
        //如果身份证号不为空且不符合身份证验证规则
        if(!empty($card_no) && !preg_match(Regular::idCard(),$card_no)){
            $this->addError('card_no', "不是合法的身份证号");
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'member_id' => '用户ID',
            'room_id' => '房屋ID',
            'name' => '姓名',
            'mobile' => '手机号',
            'sex' => '性别',
            'card_no' => '身份证号',
            'images' => '图片',
            'identity_type' => '身份',
            'time_end' => '有效期',
            'status' => '审核状态',
            'reason' => '未通过原因',
            'operator' => '操作人',
            'operator_name' => '操作人姓名',
            'create_at' => '创建时间',
            'update_at' => '重新提交时间',
            'accept_at' => '处理时间',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->create_at = $this->create_at ? $this->create_at : time();
            $this->update_at = time();
        } else {
            $this->update_at = time();
        }
        return true;
    }


    /**
     * 列表查询
     * @param $params
     * @return \yii\db\ActiveQuery
     */
    public function get($params)
    {
        $model = self::find()
            ->andFilterWhere([
                'ps_resident_audit.community_id' => PsCommon::get($params, 'community_id'),
                'ps_resident_audit.status' => PsCommon::get($params, 'status') ?: self::AUDIT_WAIT,
                'identity_type' => PsCommon::get($params, 'identity_type')
            ]);
        $model->joinWith('room')->andFilterWhere([
            'group' => PsCommon::get($params, 'group'),
            'building' => PsCommon::get($params, 'building'),
            'unit' => PsCommon::get($params, 'unit'),
            'room' => PsCommon::get($params, 'room'),
        ]);
        if (!empty($params['name'])) {
            $model->andFilterWhere(['or', ['like', 'name', $params['name']], ['like', 'mobile', $params['name']]]);
        }
        return $model;
    }

    public function getRoom()
    {
        return $this->hasOne(PsCommunityRoominfo::className(), ['id' => 'room_id'])
            ->select('id, group, building, unit, room');
    }

    public function getMember()
    {
        return $this->hasOne(PsMember::className(), ['id' => 'member_id']);
    }

    /**
     * 获取单条数据
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
}
