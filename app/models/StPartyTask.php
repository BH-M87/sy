<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "st_party_task".
 *
 * @property string $id 任务id
 * @property string $station_id 先锋岗位id
 * @property string $task_name 任务标题
 * @property int $pioneer_value 先锋值
 * @property int $expire_time_type 领取截止时间类型 1 长期有效  2指定日期
 * @property int $expire_time 领取截止时间
 * @property string $party_address 任务地址
 * @property string $contact_name 联系人名称
 * @property string $contact_phone 联系人手机号码
 * @property int $is_location 是否需要定位 1是 2否
 * @property string $task_details 任务详情
 * @property string $create_at 创建时间
 * @property int $operator_id 创建人id
 * @property string $operator_name 操作人名
 */
class StPartyTask extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_party_task';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['station_id', 'pioneer_value', 'expire_time_type', 'expire_time', 'is_location', 'create_at', 'operator_id'], 'integer'],
            [['task_details', 'operator_id','station_id','task_name','pioneer_value','expire_time','is_location'], 'required','message' => '{attribute}必填','on' => ['add','edit']],
            [['task_details'], 'string','max' => 1000],
            [['task_name'], 'string', 'max' => 30],
            [['is_location','expire_time_type'], 'in', 'range' => [1, 2],'message' => '{attribute}非法'],
            [['party_address'], 'string', 'max' => 50],
            [['contact_name'], 'string', 'max' => 10],
            [['contact_phone'], 'string', 'max' => 12],
            [['operator_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'station_id' => '岗位ID',
            'task_name' => '任务名称',
            'pioneer_value' => '先锋值',
            'expire_time_type' => '领取截止时间类型',
            'expire_time' => '截止时间',
            'party_address' => '任务地址',
            'contact_name' => '联系人',
            'contact_phone' => '联系电话',
            'is_location' => '是否需要定位',
            'task_details' => '任务详情',
            'create_at' => '创建时间',
            'operator_id' => '操作人ID',
            'operator_name' => '操作人名称',
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['create_at'],
                ],
                'value' => time()
            ]
        ];
    }


    /**
     * 获取个人列表(只获取本年的)
     * @author yjh
     * @param $param
     * @param bool $page
     * @return mixed
     */
    public static function getUserList($param,$page=true)
    {
        $param['years'] = date('Y',time());
        $param['start'] = strtotime($param['years'].'-01-01 00:00');
        $param['end'] = strtotime($param['years'].'-12-31 24:00');

        $model = self::find()->alias('st')
            ->select('st.task_name,sts.status,sts.id,ss.station as station_name,sts.pioneer_value,sts.create_at,sts.update_at')
            ->leftJoin('st_station as ss', 'ss.id = st.station_id')
            ->leftJoin('st_party_task_station as sts', 'sts.task_id = st.id')
            ->where(['sts.communist_id' => $param['communist_id']])
            ->andFilterWhere(['sts.status' => $param['status'] ?? null])
            ->andFilterWhere(['>','sts.create_at',$param['start']])
            ->andFilterWhere(['<','sts.create_at',$param['end']]);
        $model->orderBy([ 'st.create_at' => SORT_DESC]);
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
            self::afterUserList($data['list']);
        }
        return $data;
    }

    public static function afterUserList(&$data)
    {
        foreach ($data as &$v) {
            //领取时间取新增时间
            if ($v['status'] == 1) {
                $v['created_at'] = date('Y-m-d H:i:s',$v['create_at']);
            } else {
                $v['created_at'] = date('Y-m-d H:i:s',$v['update_at']);
            }
            if ($v['status'] == 3 || $v['status'] == 4) {
                $record = StPartyTaskOperateRecord::find()->where(['party_task_station_id' => $v['id']])->one();
                $v['content'] = $record['content'];
                unset($record);
            }
            $v['status_info'] = [
                'id' => $v['status'],
                'name' => StPartyTaskStation::$audit_status_msg[$v['status']],
            ];
            unset($v['station']);
        }
    }


    /**
     * 获取列表
     * @author yjh
     * @param $param
     * @param bool $page
     * @return mixed
     */
    public static function getList($param,$page=true)
    {
        $model = self::find()->alias('st')->select('st.*')
            ->leftJoin('st_station as ss', 'ss.id = st.station_id')
            ->filterWhere(['like', 'task_name', $param['task_name'] ?? null])
            ->andFilterWhere(['station_id' => $param['station_id']])
            ->andFilterWhere(['ss.status' => $param['station_status'] ?? null ])
            ->andFilterWhere(['or',['>','expire_time',$param['expire_time'] ?? null],['expire_time_type' => $param['expire_time_type'] ?? null]]);
        $model->orderBy([ 'st.create_at' => SORT_DESC]);
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
            $station = StStation::find()->where(['id' => $v['station_id']])->one();
            $v['station_name'] = $station->station;
            $v['station_status'] = $station->status == 1 ? '显示中' : '已隐藏';
            $v['claim_count'] = StPartyTaskStation::find()->where(['task_id' => $v['id']])->count();
            if ($v['expire_time_type'] == 2) {
                if ($v['expire_time'] < time()) {
                    $v['expire_time'] = '已过期';
                } else {
                    $v['expire_time'] = date('Y-m-d H:i:s',$v['expire_time']);
                }
            } else {
                $v['expire_time'] = '长期有效';
            }
            unset($v['expire_time_type']);
            unset($station);
        }
    }
}
