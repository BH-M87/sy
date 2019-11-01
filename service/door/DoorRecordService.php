<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/21
 * Time: 15:07
 */

namespace service\door;


use app\models\DoorRecord;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use service\common\CsvService;
use service\rbac\OperateService;

class DoorRecordService extends BaseService
{
    //开门方式
    public $_open_door_type = [
        '1' => '人脸开门',
        '2' => '蓝牙开门',
        '3' => '密码开门',
        '4' => '钥匙开门',
        '5' => '扫码开门',
        '6' => '临时密码',
        '7' => '二维码开门'
    ];

    //用户身份
    public $_user_type = [
        '1' => '业主',
        '2' => '家人',
        '3' => '租客',
        '4' => '访客'
    ];

    public function getCommon()
    {
        $comm = [
            'open_door_type' => PsCommon::returnKeyValue($this->_open_door_type),
            'user_type' => PsCommon::returnKeyValue($this->_user_type)
        ];
        return $comm;
    }

    //列表
    public function getList($params)
    {
        $model = DoorRecord::find()->alias('dr')
            ->select(['dr.*','c.name as community_name'])
            ->leftJoin('ps_community as c','c.id = dr.community_id')
            ->where(['dr.community_id'=>$params['community_id']]);
        if (!empty($params['open_type'])) {
            $model->andFilterWhere(['dr.open_type'=>$params['open_type']]);
        }
        if (!empty($params['user_type'])) {
            $model->andFilterWhere(['dr.user_type'=>$params['user_type']]);
        }
        if (!empty($params['device_name'])) {
            $model->andFilterWhere(['like','dr.device_name',$params['device_name']]);
        }
        if (!empty($params['user_phone'])) {
            $model->andFilterWhere(["or",["like","dr.user_name",$params['user_phone']],["like","dr.user_phone",$params['user_phone']]]);
        }
        if (!empty($params['card_no'])) {
            $model->andFilterWhere(['like','dr.card_no',$params['card_no']]);
        }
        if(!empty($params['start_time'])){
            $start_time = strtotime($params['start_time']);
            $model->andFilterWhere(['>=','dr.open_time',$start_time]);
        }
        if (!empty($params['end_time'])) {
            $end_time = strtotime($params['end_time'].' 23:59:59');
            $model->andFilterWhere(['<=','dr.open_time',$end_time]);
        }
        //苑期区的筛选
        if(!empty($params['group'])){
            $model = $model->andFilterWhere(['dr.group'=>$params['group']]);
        }
        if (!empty($params['building'])){
            $model = $model->andFilterWhere(['dr.building'=>$params['building']]);
        }
        if (!empty($params['unit'])){
            $model = $model->andFilterWhere(['dr.unit' => $params['unit']]);
        }
        if (!empty($params['room'])){
            $model = $model->andFilterWhere(['dr.room'=>$params['room']]);
        }
        $re['totals'] = $model->count();
        if (empty($params['use_as'])) {
            $model->offset((($params['page'] - 1) * $params['rows']))
                ->limit($params['rows']);
        }
        $list = $model
            ->orderBy('dr.id desc')
            ->asArray()
            ->all();
        $deviceTypeDescArr = DeviceService::service()->_pass_type;
        foreach ($list as $k => $value) {
            $list[$k]['capture_photo'] = $value['capture_photo'] ? F::getOssImagePath($value['capture_photo'], 'zjy') : '';
            $list[$k]['room_address'] = $value['group'].$value['building'].$value['unit'].$value['room'];
            $list[$k]['open_type'] = PsCommon::getKeyValue($value['open_type'],$this->_open_door_type);
            $list[$k]['open_type_desc'] = $list[$k]['open_type']['value'];
            $list[$k]['user_type'] = $value['user_phone'] ? PsCommon::getKeyValue($value['user_type'],$this->_user_type) : [];
            $list[$k]['user_type_desc'] = $list[$k]['user_type']['value'];
            $list[$k]['open_times'] = $value['open_time'] ? date("Y-m-d H:i:s",$value['open_time']) : '';
            $list[$k]['deviceType'] = $value['device_type'];
            $list[$k]['deviceTypeDesc'] = !empty($deviceTypeDescArr[$value['device_type']]) ? $deviceTypeDescArr[$value['device_type']] : '';
            $list[$k]['user_phone'] = $value['user_phone'];
        }
        $re['list'] = $list;
        return $re;
    }

    //导出
    public function export($params, $userInfo = [])
    {
        $params['use_as'] = 'export';
        $result = $this->getList($params);
        if (count($result['list']) < 1) {
            throw new MyException('数据为空');
        }
        $config = [
            ['title' => '开门时间', 'field' => 'open_times'],
            ['title' => '姓名', 'field' => 'user_name'],
            ['title' => '手机号码', 'field' => 'user_phone'],
            ['title' => '用户类型', 'field' => 'user_type_desc'],
            ['title' => '关联房屋', 'field' => 'room_address'],
            ['title' => '开门方式', 'field' => 'open_type_desc'],
            ['title' => '设备名称', 'field' => 'device_name'],
            ['title' => '门卡卡号', 'field' => 'card_no'],
        ];
        $filename = CsvService::service()->saveTempFile(1, $config, $result['list'], 'openRecord');
        $filePath = F::originalFile().'temp/'.$filename;
        $fileRe = F::uploadFileToOss($filePath);
        $downUrl = $fileRe['filepath'];
        $operate = [
            "community_id" => $params["community_id"],
            "operate_menu" => "门禁管理",
            "operate_type" => "导出开门记录",
            "operate_content" => "导出",
        ];
        OperateService::addComm($userInfo, $operate);
        return $downUrl;
    }

    /**
     * 社区微恼关联访客
     * @author yjh
     * @param $param
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getVisitorRecord($param)
    {
        $data = DoorRecord::find()->select('open_type,device_name,capture_photo,open_time')->where(['visitor_id' => $param['visitor_id']])->asArray()->all();
        foreach ($data as &$v) {
            $v['open_type'] = [
                'id' => $v['open_type'],
                'name' => $this->_open_door_type[$v['open_type']]
            ];
            $v['enter_time'] = !empty($v['open_time']) ? date('Y-m-d',$v['open_time']) : '';
            $v['leave_time'] =  '';
            unset($v['open_time']);
        }
        return $data;
    }
}