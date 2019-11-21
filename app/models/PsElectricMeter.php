<?php

namespace app\models;

use service\rbac\OperateService;
use Yii;

/**
 * This is the model class for table "ps_electric_meter".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $meter_no
 * @property integer $meter_type
 * @property integer $meter_status
 * @property integer $room_id
 * @property string $group
 * @property string $building
 * @property string $unit
 * @property string $room
 * @property string $address
 * @property integer $start_ton
 * @property integer $start_time
 * @property integer $cycle_time
 * @property integer $payment_time
 * @property integer $has_reading
 * @property string $remark
 * @property integer $create_at
 * @property integer $latest_record_time
 */
class PsElectricMeter extends BaseModel
{

    public static $meter_status   =['1'=>"启用",'2'=>"禁用"];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_electric_meter';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id',"meter_no",'meter_status','room_id','group','building','unit','address','start_ton','latest_record_time','create_at',],'required','on'=>['add']],
            [['community_id', 'meter_type', 'meter_status', 'room_id', 'start_time', 'cycle_time', 'payment_time', 'has_reading', 'create_at','latest_record_time'], 'integer'],
            [['meter_no'], 'string', 'max' => 20],
            [['group', 'building', 'unit', 'room'], 'string', 'max' => 64],
            [['address'], 'string', 'max' => 255],
            [['remark'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'meter_no' => 'Meter No',
            'meter_type' => 'Meter Type',
            'meter_status' => 'Meter Status',
            'room_id' => 'Room ID',
            'group' => 'Group',
            'building' => 'Building',
            'unit' => 'Unit',
            'room' => 'Room',
            'address' => 'Address',
            'start_ton' => 'Start Ton',
            'start_time' => 'Start Time',
            'cycle_time' => 'Cycle Time',
            'payment_time' => 'Payment Time',
            'has_reading' => 'Has Reading',
            'remark' => 'Remark',
            'create_at' => 'Create At',
            'latest_record_time' => 'Latest Record Time',
        ];
    }

    /**
     * 查询电表数据
     * @author Yjh
     * @param $data
     * @param string $field
     * @param bool $page
     * @return array
     */
    public static function getData($data,$field = '*',$page=true)
    {
        $return = [];
        $electric_meter = PsElectricMeter::find()->select($field)->where($data['where'])->andWhere($data['like'])->orderBy([ 'id' => SORT_DESC]);
        if ($page) {
            $page = !empty($data['page']) ? $data['page'] : 1;
            $row = !empty($data['row']) ? $data['row'] : 10;
            $page = ($page-1)*$row;
            $countQuery = clone $electric_meter;
            $count = $countQuery->count();
            $return['totals'] = $count;
            $electric_meter->offset($page)->limit($row);
        }
        $models = $electric_meter->asArray()->all();
        if ($models) {
            $result = self::afterSelect($models);
        }
        $return['list'] = $result ?? null;
        return $return;
    }

    /**
     * 数据格式化
     * @author Yjh
     * @param $model
     * @return mixed
     */
    public static function afterSelect($model)
    {
        if (count($model) == count($model, 1)) {
            $model['latest_record_time'] = date('Y-m-d', $model['latest_record_time']);
            $model['meter_status_desc'] = self::$meter_status[$model['meter_status']];
            $model['type'] = '电表';
        } else {
            foreach ($model as $k => &$v) {
                $v['meter_status_desc'] = self::$meter_status[$v['meter_status']];
                $v['latest_record_time'] = date('Y-m-d', $v['latest_record_time']);
                $v['type'] = '电表';
            }
        }
        return $model;
    }

    /**
     * 修改电表数据
     * @author Yjh
     * @param $data
     * @param $where
     * @return array
     */
    public static function editData($data,$where)
    {
        $model = self::findOne($where['id']);
        $model->meter_no = $data['meter_no'];
        $model->meter_status = $data['meter_status'];
        $model->start_ton = $data['start_ton'];
        $model->latest_record_time = $data['latest_record_time'];
        $model->remark = $data['remark'];
        $model->save();
        return $model->attributes;
    }

    /**
     * 删除数据
     * @author yjh
     * @param $id
     * @return bool
     */
    public static function deleteData($id,$userinfo='')
    {
        $model = self::findOne($id);
        if (!empty($model)) {
            //保存日志
            $log = [
                "community_id" => $model->community_id,
                "operate_menu" => "仪表信息",
                "operate_type" => "删除电表",
                "operate_content" => $model->meter_no
            ];
            OperateService::addComm($userinfo, $log);
            return $model->delete();
        }
        return false;
    }
}