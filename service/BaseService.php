<?php
namespace service;
use app\models\IotSupplierCommunity;
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
}