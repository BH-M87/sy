<?php

namespace app\models;

use service\rbac\OperateService;
use Yii;

/**
 * This is the model class for table "ps_water_meter".
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
 * @property string $start_ton
 * @property integer $start_time
 * @property integer $cycle_time
 * @property integer $payment_time
 * @property integer $has_reading
 * @property string $remark
 * @property integer $create_at
 */
class PsWaterMeter extends BaseModel
{

    //定义水费的缴费类型
    public static $meter_type = ['1'=>"固定水价",'2'=>"阶梯水价"];
    //水表的状态
    public static $meter_status   =['1'=>"启用",'2'=>"禁用"];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_water_meter';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','community_name',"meter_no",'meter_status','room_id','room_name','group_id','building_id','unit_id','address','start_ton','latest_record_time','create_at',],'required','on'=>['add']],
            [["meter_no",'meter_status','start_ton','latest_record_time'],'required','on'=>['edit']],
            [['meter_type', 'meter_status', 'start_time', 'cycle_time', 'payment_time', 'has_reading', 'create_at'], 'integer'],
            [['start_ton'], 'number'],
            [['meter_no'], 'string', 'max' => 20],
            [['community_id','community_name','room_id', 'room_name','group_id','building_id','unit_id'], 'string', 'max' => 30],
            [['address'], 'string', 'max' => 255],
            [['remark'], 'string', 'max' => 150],
            ['latest_record_time', 'default', 'value'=>0],
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
            'community_name' => 'community_name',
            'meter_no' => 'Meter No',
            'meter_type' => 'Meter Type',
            'meter_status' => 'Meter Status',
            'room_id' => 'Out Room ID',
            'room_name' => '房屋号',
            'group_id' => 'Group',
            'building_id' => 'Building',
            'unit_id' => 'Unit',
            'address' => 'Address',
            'start_ton' => 'Start Ton',
            'start_time' => 'Start Time',
            'cycle_time' => 'Cycle Time',
            'payment_time' => 'Payment Time',
            'has_reading' => 'Has Reading',
            'remark' => '备注',
            'create_at' => 'Create At',
            'latest_record_time'=>'Latest Record Time'
        ];
    }

    /**
     * 查询水表数据
     * @author Yjh
     * @param $data
     * @param string $field
     * @param bool $page
     * @return array
     */
    public static function getData($data,$field = '*',$page=true)
    {
        $return = [];
        $water_meter = self::find()->select($field)->where($data['where'])->andWhere($data['like'])->orderBy([ 'id' => SORT_DESC]);
        if ($page) {
            $countQuery = clone $water_meter;
            $count = $countQuery->count();

            $page = !empty($data['page']) ? $data['page'] : 1;
            $row = !empty($data['rows']) ? $data['rows'] : 10;
//            $page = ($page-1)*$row;

            $allPage = ceil($count/$row);
            $page1 = $allPage>$page?$page:$allPage;
//            $offset = ($page-1)*$pageSize;
            $page = ($page1-1)*$row;

            $return['totals'] = $count;
            $water_meter->offset($page)->limit($row);
        }
        $models = $water_meter->asArray()->all();
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
            $model['type'] = '水表';
        } else {
            foreach ($model as $k => &$v) {
                $v['meter_status_desc'] = self::$meter_status[$v['meter_status']];
                $v['latest_record_time'] = date('Y-m-d', $v['latest_record_time']);
                $v['type'] = '水表';
            }
        }
        return $model;
    }
    /**
     * 修改水表数据
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
    public static function deleteData($id,$userinfo)
    {
        $model = self::findOne($id);
        if (!empty($model)) {
            //保存日志
            $log = [
                "community_id" => $model->community_id,
                "operate_menu" => "仪表信息",
                "operate_type" => "删除水表",
                "operate_content" => $model->meter_no
            ];
            OperateService::addComm($userinfo, $log);
            return $model->delete();
        }
        return false;
    }
}
