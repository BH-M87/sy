<?php
namespace service;
use app\models\IotSupplierCommunity;
use app\models\IotSuppliers;
use app\models\PsAppMember;
use app\models\PsAppUser;
use app\models\PsMember;
use Yii;
use yii\base\Model;
use common\core\Api;

class BaseService {

    private static $_services = [];

    /**
     * 单例容器
     */
    public static function service($params = null) {
        $name = get_called_class();
        if(!isset(self::$_services[$name]) || !is_object(self::$_services[$name])) {
            $instance = self::$_services[$name] = new static($params);
            return $instance;
        }
        return self::$_services[$name];
    }

    /**
     * 防止克隆
     */
    private function __clone() {}

    /**
     * 操作失败
     * @param string $msg
     * @return array
     */
    public function failed($msg = '系统错误', $code=0) {
        return ['code'=>$code, 'msg'=>$msg];
    }

    /**
     * 操作成功
     * @param array $data
     * @return array
     */
    public function success($data=[]) {
        return ['code'=>1, 'data'=>$data];
    }

    /**
     * 缓存
     * @param $cacheKey
     * @param $expire
     * @param $closure
     * @return mixed
     */
    public function cache($cacheKey, $expire, $closure) {
        if(!$expire) {
            return $closure();
        }
        if(!$data = Yii::$app->cache->get($cacheKey)) {
            $data = $closure();
            Yii::$app->cache->set($cacheKey, $data, $expire);
        }
        return $data;
    }

    /**
     * 返回model validate错误
     * @param ActiveRecord $model
     * @return string
     */
    public function getError(Model $model)
    {
        $errors = array_values($model->getErrors());
        if(!empty($errors[0][0])) {
            return $errors[0][0];
        }
        return '系统异常';
    }


    //get请求接口
    public function apiGet($url, $data = [], $log = false, $paramFormat = true, $hasSign = false)
    {
        return Api::getInstance()->get($url, $data);
    }

    //post请求接口
    public function apiPost($url, $data = [], $log = false, $paramFormat = true, $hasSign = false)
    {
        return Api::getInstance()->post($url, $data);
    }

    /**
     * 比较两个二维数组，获取差集
     * @param $arr1
     * @param $arr2
     * @param string $pk
     * @return array
     */
    function get_diff_array_by_key($arr1,$arr2,$pk='key'){
        try{
            $res=[];
            foreach($arr2 as $item) $tmpArr[$item[$pk]] = $item;
            foreach($arr1 as $v) if(! isset($tmpArr[$v[$pk]])) $res[] = $v;
            return $res;
        }catch (\Exception $exception){
            return $arr1;
        }
    }

    //获取业主id
    public function getMemberByUser($user_id)
    {
        return PsAppMember::find()->select('member_id')->where(['app_user_id' => $user_id])->scalar();
    }

    //获取业主名称
    public function getMemberNameByUser($user_id)
    {
        return PsMember::find()->select('name')->where(['id' => $user_id])->scalar();
    }

    //获取支付宝id
    public function getBuyerIdr($user_id)
    {
        return PsAppUser::find()->select('channel_user_id')->where(['id' => $user_id])->scalar();
    }

    /**
     * 根据小区id和接入类型获取供应商id
     * @param $communityId
     * @param $type  1 道闸 2门禁
     * @return false|null|string
     */
    public function getSupplierId($communityId, $type)
    {
        return IotSupplierCommunity::find()
            ->select(['supplier_id'])
            ->where(['community_id'=>$communityId, 'supplier_type' => $type])
            ->scalar();
    }

    /**
     * 查询供应商sn
     * @param $supplier_id
     * @return false|null|string
     */
    public function getSupplierProductSn($supplier_id)
    {
        return IotSuppliers::find()->select(['productSn'])->where(['id'=>$supplier_id])->asArray()->scalar();
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