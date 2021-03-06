<?php
namespace app\models;

use Yii;

class PsParkSet extends BaseModel
{
    public static function tableName()
    {
        return 'ps_park_set';
    }

    public function rules()
    {
        return [
            [['corp_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            [['cancle_num', 'late_at', 'due_notice', 'black_num', 'appointment', 'appointment_unit', 'lock', 'lock_unit'], 'integer', 'message'=> '{attribute}格式错误!'],
            [['integral', 'min_time', 'lock', 'appointment'], 'default', 'value' => 0, 'on' => 'add'],
            [['lock_unit', 'appointment_unit'], 'default', 'value' => 1, 'on' => 'add'],
            [['cancle_num', 'black_num'], 'default', 'value' => 3, 'on' => 'add'],
            [['late_at', 'due_notice'], 'default', 'value' => 15, 'on' => 'add'],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
            [['cancle_num', 'late_at'], 'verifyData', 'on' => ['add', 'edit']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'corp_id' => '公司ID',
            'cancle_num' => '每人可同时预约车位数',
            'late_at' => '迟到取消预约时间',
            'due_notice' => '预约车位离场提前通知时间',
            'black_num' => '黑名单违约数',
            'appointment' => '预约超时',
            'appointment_unit' => '预约超时单位',
            'lock' => '锁定时间',
            'lock_unit' => '锁定时间单位',
            'min_time' => '共享最小计时单位',
            'integral' => '预约成功获得积分',
            'create_at' => '新增时间',
        ];
    }

    // 数据校验
    public function verifyData()
    {   
        if ($this->cancle_num < 1) {
            $this->addError('', '每人可同时预约车位数最小1次');
        }

        if ($this->cancle_num > 9) {
            $this->addError('', "每人可同时预约车位数最大9次");
        }

        if ($this->late_at < 0) {
            $this->addError('', '迟到取消预约时间最小0分钟');
        }

        if ($this->late_at > 60) {
            $this->addError('', "迟到取消预约时间最大60分钟");
        }

        if ($this->due_notice < 5) {
            $this->addError('', '预约车位离场提前通知时间最小5分钟');
        }

        if ($this->due_notice > 60) {
            $this->addError('', "预约车位离场提前通知时间最大60分钟");
        }

        if ($this->black_num < 1) {
            $this->addError('', '黑名单违约数最小1');
        }

        if ($this->black_num > 9) {
            $this->addError('', "黑名单违约数最大9");
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
