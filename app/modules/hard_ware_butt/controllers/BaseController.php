<?php
namespace app\modules\hard_ware_butt\controllers;
use app\models\IotSupplierCommunity;
use app\models\ParkingLot;
use common\core\F;
use common\core\PsCommon;
use yii\web\Controller;
use Yii;

/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/9/23
 * Time: 17:00
 */

class BaseController extends Controller
{
    public $authCode;
    public $communityId = '';
    public $supplierId = '';
    public $enableCsrfValidation = false;
    public $params = [];//传参
    public $requestType;//请求类型
    public $page;
    public $rows;
    public $openAlipayParking = 0;
    public $interfaceType;
    private $_rand;
    private $_timeStamp;
    private $_openKey;
    private $_sign;
    private $_openSecret = 'zjy123#@!';
    private $_allowOpenKey = ['test1', 'test2', 'hemu', 'fushi', 'deliyun', 'dahua', 'dinaike'];
    public $enableAction;


    public function beforeAction($action)
    {
        return true;
        if (!parent::beforeAction($action)) return false;
        $this->requestType = Yii::$app->request->getMethod();
        if ($this->requestType == 'POST') {
            $this->params = Yii::$app->request->getBodyParams();
        } else {
            $this->params = Yii::$app->request->queryParams;
        }

        $this->page = !empty($this->params['page']) ? $this->params['page'] : 1;
        $this->rows = !empty($this->params['rows']) ? $this->params['rows'] : 10;
        //不走token验证的接口，及download不走其他权限,小区ID 验证
        if (!empty($this->enableAction) && in_array($action->id, $this->enableAction)) {
            return true;
        }
        //签名校验
        $this->_rand = Yii::$app->request->getHeaders()->get('rand');
        $this->_timeStamp = Yii::$app->request->getHeaders()->get('timeStamp');
        $this->_openKey = Yii::$app->request->getHeaders()->get('openKey');
        $this->_sign = Yii::$app->request->getHeaders()->get('sign');
        $this->authCode = Yii::$app->request->getHeaders()->get('authCode');

        if (!$this->authCode) {
            die(PsCommon::responseFailed('authCode 不能为空!'));
        }
        //对接iot系统的时候,authCode传固定的
        if($this->authCode == 'iot123456'){
            $lotCode = !empty($this->params['lotCode']) ? $this->params['lotCode'] : (!empty($this->params['parkNumber']) ? $this->params['parkNumber'] : '');
            if(empty($lotCode)){
                die(PsCommon::responseFailed('车场lotCode不能为空！'));
            }
            $lotInfo = ParkingLot::find()
                ->where(['park_code'=>$lotCode])
                ->andWhere(['status'=>1])
                ->asArray()->one();
            if (!$lotInfo) {
                die(PsCommon::responseFailed('该车场还没跟小区绑定!'));
            }
            $model = IotSupplierCommunity::find()
                ->select(['community_id', 'supplier_id', 'open_alipay_parking', 'interface_type'])
                ->where(['community_id' => $lotInfo['community_id'],'supplier_id' => $lotInfo['supplier_id']])
                ->asArray()
                ->one();
        }else{
            //根据authCode 查看小区
            $model = IotSupplierCommunity::find()
                ->select(['community_id', 'supplier_id', 'open_alipay_parking', 'interface_type'])
                ->where(['auth_code' => $this->authCode])
                ->asArray()
                ->one();
            if (!$model) {
                die(PsCommon::responseFailed('authCode 不存在!'));
            }
        }

        $this->communityId = $model['community_id'];
        $this->supplierId = $model['supplier_id'];
        $this->openAlipayParking = $model['open_alipay_parking'];
        $this->interfaceType = $model['interface_type'];

        $this->voidSign();

        return true;
    }

    private function voidSign()
    {
        //跳过验签验证
        return true;
        if ($this->_openKey == 'test') {
            return true;
        }

        //必传参数是否完成
        if (!$this->_sign || !$this->_rand || !$this->_timeStamp || !$this->_openKey) {
            die(PsCommon::responseFailed('请求公共参数不完整!'));
        }

        if (!in_array($this->_openKey, $this->_allowOpenKey)) {
            die(PsCommon::responseFailed('openKey 不合法!'));
        }

        //验证签名
        $sign = md5(md5($this->_openKey.$this->_rand.$this->_timeStamp).$this->_openSecret);
        if ($this->_sign == $sign) {
            return true;
        } else {
            die(PsCommon::responseFailed('签名验证失败!'));
        }
    }
}