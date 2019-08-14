<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_water_record".
 *
 * @property integer $id
 * @property integer $room_id
 * @property integer $bill_id
 * @property integer $latest_ton
 * @property integer $use_ton
 * @property integer $current_ton
 * @property integer $use_days
 * @property string $last_pay_day
 * @property integer $period_start
 * @property string $note
 * @property integer $period_end
 * @property integer $create_time
 * @property integer $operator_id
 * @property string $operator_name
 * @property string $bill_error
 * @property string $latest_create_time
 * @property integer $bill_type
 */
class PsWaterRecord extends BaseModel
{
    public static $type_msg = ['1'=>'水表','2'=>'电表'];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_water_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['current_ton',"latest_ton",'id',],'required','on'=>['edit_meter_num']],
//            [['room_id', 'latest_ton', 'use_ton', 'current_ton', 'use_days', 'last_pay_day', 'period_start', 'period_end', 'create_time', 'operator_id', 'operator_name', 'status'], 'required'],
            [['room_id', 'bill_id', 'period_start', 'period_end',
                'create_time', 'operator_id', 'status','bill_type'], 'integer'],
            [['note'], 'string', 'max' => 200],
            [['operator_name'], 'string', 'max' => 20],
            [['bill_error'], 'string', 'max'=>'100'],
            ['bill_error', 'default', 'value'=>''],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'room_id' => 'Room ID',
            'bill_id' => 'Bill ID',
            'latest_ton' => '上期读数',
            'use_ton' => 'Use Ton',
            'current_ton' => '本期读数',
            'period_start' => 'Period Start',
            'note' => 'Note',
            'period_end' => 'Period End',
            'create_time' => 'Create Time',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'status' => 'Status',
            'bill_error'=>'Bill Error',
            'bill_type'=>'Bill Type'
        ];
    }

    public function getBill()
    {
        return $this->hasOne(PsBill::className(), ['id'=>'bill_id'])
            ->select('id, bill_entry_amount, acct_period_start, acct_period_end');
    }

    public function getRoom()
    {
        return $this->hasOne(PsCommunityRoominfo::className(), ['id'=>'room_id'])
            ->select('id, building, unit, room');
    }

    /**
     * 批量插入
     * @author Yjh
     * @param $data
     */
    public static function batchInserts($data)
    {
        self::model()->yiiBatchInsert([
            'cycle_id', 'room_id', 'status', 'latest_ton', 'use_ton', 'current_ton', 'period_start',
            'period_end', 'price', 'meter_no', 'create_time', 'operator_id', 'operator_name', 'bill_type',
            'formula','formula_price', 'has_reading','created_at'
        ],$data);
    }

    /**
     * 修改读数
     * @author Yjh
     * @param $param
     * @return array
     */
    public static function editMeterNum($param)
    {
        $model = self::findOne($param['id']);
        $model->current_ton = $param['current_ton'];
        $model->latest_ton = $param['latest_ton'];
        $model->use_ton = $param['use_ton'];
        $model->price = $param['price'];
        if (!empty($param['current_ton']) &&  $param['current_ton'] > 0) {
            $model->has_reading = 1;
        } else {
            $model->has_reading = 2;
        }
        $model->save();
        return $model->attributes;
    }
    /**
     * 获取数据
     * @author Yjh
     * @param $data
     * @param string $field
     * @param bool $page
     * @return array
     */
    public static function getData($data,$field = '*',$page=true)
    {
        $return = [];
        $meter_record = self::find()->select($field)->joinWith('room as a')->where($data['where'])->andWhere($data['like'])->orderBy([ 'a.id' => SORT_DESC]);
        if ($page) {
            $page = !empty($data['page']) ? $data['page'] : 1;
            $row = !empty($data['row']) ? $data['row'] : 10;
            $page = ($page-1)*$row;
            $count = $meter_record->count();
            $return['totals'] = $count;
            $meter_record->offset($page)->limit($row);
        }
        $models = $meter_record->asArray()->all();
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
            $model['period_start'] = date('Y-m-d', $model['period_start']);
            $model['period_end'] = date('Y-m-d', $model['period_end']);
            $model['bill_type'] = self::$type_msg[$model['bill_type']];
            $model['unit'] = $model['room']['unit'];
            $model['building'] = $model['room']['building'];
            $model['room'] = $model['room']['room'];
        } else {
            foreach ($model as $k => &$v) {
                $v['period_start'] = date('Y-m-d', $v['period_start']);
                $v['period_end'] = date('Y-m-d', $v['period_end']);
                $v['bill_type'] = self::$type_msg[$v['bill_type']];
                $v['unit'] = $v['room']['unit'];
                $v['building'] = $v['room']['building'];
                $v['room'] = $v['room']['room'];
            }
        }
        return $model;
    }
}
