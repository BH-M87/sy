<?php
/**
 * 门禁 基类服务
 * @author wenchao.feng
 * @date 2018/05/15
 */

namespace service\basic_data;

use app\models\DoorDevices;
use app\models\IotSupplierCommunity;
use app\models\IotSuppliers;

Class BaseService extends \service\BaseService
{
    //根据name，搜索ID，用于批量导入
    public function searchIdByName($data, $name)
    {
        foreach($data as $k=>$t) {
            if($t['name'] == $name) {
                return $k;
            }
        }
        return false;
    }

    //获取供应商
    public function getSupplier($communityId)
    {
        $res = '';
        if($communityId){
            $res = IotSupplierCommunity::find()->select(['supplier_id'])->where(['community_id'=>$communityId, 'supplier_type' => 2])->scalar();
        }
        return $res;
    }

    //获取这个小区下面所有在用的门禁设备id，找到对应的supplier_name
    public function getSuppliers($communityId)
    {
        $res = '';
        if($communityId){
            $res = DoorDevices::find()->alias('d')
                ->leftJoin(['s'=>IotSuppliers::tableName()],'d.supplier_id = s.id')
                ->where(['d.community_id'=>$communityId, 'd.status' => 1])
                ->select(['s.supplier_name'])
                ->column();
            //如果还没添加设备，就去判断小区跟设备厂商的关联表
            if(empty($res)){
                $res = IotSupplierCommunity::find()->alias('sc')
                    ->leftJoin(['s'=>IotSuppliers::tableName()],'sc.supplier_id = s.id')
                    ->where(['sc.community_id'=>$communityId,'supplier_type'=>2])
                    ->select(['s.supplier_name'])
                    ->column();
            }
        }
        return $res;
    }

    //获取供应商id跟name
    public function getSupplierList($communityId,$type)
    {

        if($communityId != 'test'){
            $res = IotSupplierCommunity::find()->alias('sc')
                ->leftJoin('parking_suppliers s','sc.supplier_id = s.id')
                ->select(['s.id','s.name'])
                ->where(['sc.community_id'=>$communityId, 'sc.supplier_type' => $type])->asArray()->all();
        }else{
            $res = IotSupplierCommunity::find()->alias('sc')
                ->leftJoin('parking_suppliers s','sc.supplier_id = s.id')
                ->select(['s.id','s.name'])
                ->where(['sc.supplier_type' => $type])->asArray()->all();
        }
        return $res;
    }

    //根据id获取供应商名称
    public function getSupplierNameById($id)
    {
        return IotSuppliers::find()->select(['name'])->where(['id'=>$id])->scalar();
    }

    //根据id获取供应商标识
    public function getSupplierSignById($id)
    {
        return IotSuppliers::find()->select(['supplier_name'])->where(['id'=>$id])->scalar();
    }

    public function getSupplierProductSn($supplier_id)
    {
        return IotSuppliers::find()->select(['productSn'])->where(['id'=>$supplier_id])->asArray()->scalar();
    }

    /**
     * 根据不同的开门方式来获取产品的productSn
     * @param $communityId
     * @param string $functionFace          人脸
     * @param string $functionBlueTooth     蓝牙
     * @param string $functionCode          二维码
     * @param string $functionPassword      密码
     * @param string $functionCard          门卡
     * @return array|string
     */
    public function getSupplierProductSnByCommunityId($communityId,$functionFace = '',$functionBlueTooth = '',$functionCode = '',$functionPassword = '',$functionCard ='')
    {
        //因为二维码的时候一个设备厂商只能生成一个二维码，所以只拿一个productSn,edit by zq 2019-7-12
        if($functionCode){
            $res = IotSupplierCommunity::find()->alias('sc')
                ->leftJoin(['s'=>IotSuppliers::tableName()],'s.id = sc.supplier_id')
                ->where(['sc.community_id'=>$communityId])
                ->andWhere(['<>','s.productSn',''])
                ->andFilterWhere(['s.functionCode'=>1])
                ->select(['s.productSn'])->orderBy('sc.created_at asc')->asArray()->column();
            $result = !empty($res) ? $res[0] : '';//todo 获取最早接入的设备厂商productSn,后续根据iot需求修改
        }else{
            $res = IotSupplierCommunity::find()->alias('sc')
                ->leftJoin(['s'=>IotSuppliers::tableName()],'s.id = sc.supplier_id')
                ->where(['sc.community_id'=>$communityId])
                ->andWhere(['<>','s.productSn','']);
            if($functionFace){
                $res->andFilterWhere(['s.functionFace'=>1]);
            }
            if($functionBlueTooth){
                $res->andFilterWhere(['s.functionBlueTooth'=>1]);
            }
            if($functionCode){
                $res->andFilterWhere(['s.functionCode'=>1]);
            }
            if($functionPassword){
                $res->andFilterWhere(['s.functionPassword'=>1]);
            }
            if($functionCard){
                $res->andFilterWhere(['s.functionCard'=>1]);
            }
            $result = $res->select(['s.productSn'])->orderBy('sc.created_at asc')->asArray()->column();
        }
        return $result;

    }

    public function getAuthCodeNew($community_id,$supplier_id)
    {
        $res = '';
        if($community_id && $supplier_id){
            $res = IotSupplierCommunity::find()->select(['auth_code'])->where(['community_id'=>$community_id,'supplier_id'=>$supplier_id])->scalar();
        }
        return $res;
    }


    //获取授权码
    public function getAuthCode($community_id,$supplier_id)
    {
        $res = '';
        if($community_id && $supplier_id){
            $res = IotSupplierCommunity::find()->select(['auth_code'])->where(['community_id'=>$community_id,'supplier_id'=>$supplier_id, 'supplier_type' => 2])->scalar();
        }
        return $res;
    }

    //判断是否有当前小区的操作权限
    public function checkCarportId($community_id,$communitys){
        $community = explode(',',$communitys);
        if(!in_array($community_id,$community)){
            return false;
        }else{
            return true;
        }
    }

    //如果传入是时间不是时间戳，那么将之转化
    public function dealTime($time)
    {
        if(!is_numeric($time)){
            //将传入的时间转成当天的23时59分39秒
            $time1 = strtotime($time);
            $time2 = date('Y-m-d',$time1)." 23:59:59";
            $time = strtotime($time2);
        }
        return $time;
    }

    //将时间戳转成指定日期格式的字符串
    public function dealDateTime($time,$type = 'Y-m-d H:i:s')
    {
        if(is_numeric($time)){
            return date($type,$time);
        }else{
            return $time;
        }
    }

    //验证是不是调用新版的iot接口
    public function checkIsNewIot($community_id)
    {
        return true;
        //$newIotCommunityListMaster = ['588','570','565','585'];
        $newIotCommunityListMaster = ['593','587','584','583','588','580','585','565','570','493'];
        $newIotCommunityListDev = ['220','134','147'];
        if(YII_ENV == 'prod' && !in_array($community_id,$newIotCommunityListMaster)){
            return true;
        }else if(YII_ENV != 'prod' && !in_array($community_id,$newIotCommunityListDev)){
            return true;
        }else{
            return false;
        }
    }

    //验证是不是测试环境
    public function checkIsMaster($community_id =''){
        $testCommunity = ['220','222'];
        //测试环境且指定的小区
        if(YII_ENV != 'prod' && in_array($community_id,$testCommunity)){
            return true;
        }else{
            return false;
        }
    }

    //验证数据是走老的iot接口还是新的iot接口
    public function checkIoNewIotOld($community_id)
    {
        //$newIotCommunityListMaster = ['588','570','565','585'];
        $newIotCommunityListMaster = ['593','587','584','583','588','580','585','565','570','493'];
        $newIotCommunityListDev = ['220','134','147'];
        if(YII_ENV == 'prod' && !in_array($community_id,$newIotCommunityListMaster)){
            return true;
        }else if(YII_ENV != 'prod' && !in_array($community_id,$newIotCommunityListDev)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取同步标识，是否把数据同步到数据平台跟公安厅
     * @param $community_id
     * @param $supplier_id
     * @param string $supplier_type
     * $supplier_type 1道闸，2门禁，空全部
     * @return false|null|string
     */
    public function getSyncDatacenter($community_id,$supplier_id,$supplier_type = '')
    {
        //根据供应商类型来区分是查找门禁还是道闸的同步类型 add by zq 2019-5-9
        if(empty($supplier_type)){
            $supplier_type = [1,2];
        }
        $return = IotSupplierCommunity::find()
            ->select(['sync_datacenter'])
            ->where(['community_id'=> $community_id, 'supplier_id' => $supplier_id,'supplier_type'=>$supplier_type])
            ->scalar();
        //如果门禁或者道闸没有单独的配置，就默认查找全部的
        if(empty($return)){
            $return = IotSupplierCommunity::find()
                ->select(['sync_datacenter'])
                ->where(['community_id'=> $community_id, 'supplier_id' => $supplier_id])
                ->scalar();
        }
        return $return;
    }

}
