<?php
/**
 * 支付宝物业缴费 房屋，账单相关接口
 * 支付宝接口地址 https://docs.open.alipay.com/347/106488/
 * User: wenchao.feng
 * Date: 2017/3/7
 * Time: 13:42
 */
namespace service\alipay;

use service\BaseService;
use service\manage\CommunityService;
use app\models\PsPropertyCompany;
use service\alipay\AliTokenService;
use yii\helpers\FileHelper;
use common\core\F;
use service\qiniu\UploadService;
use service\common\QrcodeService;
use common\ali\AopRedirect;
use Yii;

class AlipayBillService extends BaseService
{
    private $_alipay;// AopRedirect实例

    function __construct($communityNo = null)
    {
        $this->_alipay = new AopRedirect();
        if (is_null($communityNo)) {
            $this->_alipay->alipayPublicKey = Yii::$app->params['alipay_public_key_file'];
            $this->_alipay->rsaPrivateKeyFilePath = Yii::$app->params['merchant_private_key_file'];
            $this->_alipay->gatewayUrl = Yii::$app->params['gate_way_url'];
            $this->_alipay->appId = Yii::$app->params['property_app_id'];
            $this->_alipay->signType = 'RSA';
        } else {
            $companyModel = PsPropertyCompany::find()
                ->select(['ps_property_company.has_sign_qrcode', 'ps_property_company.id as pro_company_id'])
                ->leftJoin('ps_community comm', 'ps_property_company.id = comm.pro_company_id')
                ->where(['comm.community_no' => $communityNo])
                ->asArray()
                ->one();
            $hasQrcodeAuth = $companyModel['has_sign_qrcode'] == 1 ? true : false;
            if (YII_ENV == 'master' || YII_ENV == 'release') {
                if ($hasQrcodeAuth) {
                    $this->_alipay->gatewayUrl         = Yii::$app->params['gate_way_url'];
                    $this->_alipay->appId              = Yii::$app->params['property_isv_app_id'];
                    $this->_alipay->alipayrsaPublicKey = file_get_contents(Yii::$app->params['property_isv_alipay_public_key_file']);
                    $this->_alipay->rsaPrivateKey      = file_get_contents(Yii::$app->params['property_isv_merchant_private_key_file']);
                    $this->_alipay->signType = 'RSA2';
                } else {
                    $this->_alipay->alipayPublicKey = Yii::$app->params['alipay_public_key_file'];
                    $this->_alipay->rsaPrivateKeyFilePath = Yii::$app->params['merchant_private_key_file'];
                    $this->_alipay->gatewayUrl = Yii::$app->params['gate_way_url'];
                    $this->_alipay->appId = Yii::$app->params['property_app_id'];
                    $this->_alipay->signType = 'RSA';
                }
            } elseif (YII_ENV == 'shaxiang'){
                $this->_alipay->alipayPublicKey = Yii::$app->params['alipay_public_key_file'];
                $this->_alipay->rsaPrivateKeyFilePath = Yii::$app->params['merchant_private_key_file'];
                $this->_alipay->gatewayUrl = Yii::$app->params['gate_way_url'];
                $this->_alipay->appId = Yii::$app->params['property_app_id'];
                $this->_alipay->signType = 'RSA';
            } else {
                $this->_alipay->gatewayUrl         = Yii::$app->params['gate_way_url'];
                $this->_alipay->appId              = Yii::$app->params['property_isv_app_id'];
                $this->_alipay->alipayrsaPublicKey = file_get_contents(Yii::$app->params['property_isv_alipay_public_key_file']);
                $this->_alipay->rsaPrivateKey      = file_get_contents(Yii::$app->params['property_isv_merchant_private_key_file']);
                $this->_alipay->signType = 'RSA2';
            }
        }
    }

    /**
     * 账单批量同步到支付宝
     * @param $data
     * @return mixed
     */
    public function batchBill($token, $data)
    {
        //获取token
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        $params['biz_content'] = json_encode($data);
        $result = $this->_alipay->execute('alipay.eco.cplife.bill.batch.upload', $params, null, $token);
        if ($result['code'] == 10000) {
            $data['batch_id'] = $result['batch_id'];
            return $this->success($data);
        } else {
            return $this->failed('支付宝错误：' . $result['sub_msg']);
        }
    }
    /**
     * 修改支付宝账单
     * @param $data
     * @return mixed
     */
    public function batchUpdateBill($data)
    {

        $params['biz_content'] = json_encode($data);
        //获取token
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        $result = $this->_alipay->execute('alipay.eco.cplife.bill.modify', $params, null, $token);
        if ($result['code'] == 10000) {
            $data['alive_bill_entry_list'] = $result['alive_bill_entry_list'];
            return $this->success($data);
        } else {
            return $this->failed('支付宝错误：' . $result['sub_msg']);
        }
    }

    /**
     * 发布支付宝账单
     * @param $data
     * @return mixed
     */
    public function batchBillCkl($data)
    {

        $params['biz_content'] = json_encode($data);
        //获取token
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        $result = $this->_alipay->execute('alipay.eco.cplife.bill.batch.upload', $params, null, $token);
        if ($result['code'] == 10000) {
            return $this->success($data);
        } else {
            return $this->failed('支付宝错误：' . $result['sub_msg']);
        }
    }

    /**
     * 查询所有物业缴费账单
     * @param $communityNo
     * @param $batchId
     * @param $roomId
     */
    public function queryAll($communityNo, $batchId, $roomId)
    {
        $r = $this->queryBill($communityNo, $batchId, $roomId, 1);
        if ($r['code'] != 10000) {
            return $this->failed($r['msg']);
        }
        $total = $r['total_bill_count'];
        $data = empty($r['bill_result_set']) ? [] : $r['bill_result_set'];
        if ($total >= 500) {//超过1000条，需要请求下一页, TODO 递归
            //batch最多1000条数据
            $r2 = $this->queryBill($communityNo, $batchId, $roomId, 2);
            if ($r2['code'] != 10000) {
                return $this->failed($r['msg']);
            }
            if (!empty($r2['bill_result_set'])) {
                $data = array_merge($data, $r2['bill_result_set']);
            }
        }

        return $this->success(['total' => $total, 'data' => $data]);
    }

    /**
     * 批量查询物业缴费账单
     * @param $communityNo
     * @param $params
     */
    public function queryBill($communityNo, $batchId, $roomId = '', $page)
    {
        $biz = ['community_id' => $communityNo, 'page_num' => $page, 'page_size' => 500];
        if ($batchId) {
            $biz['batch_id'] = (string)$batchId;
        }
        if ($roomId) {
            $biz['out_room_id'] = $roomId;
        }
        $token = AliTokenService::service()->getTokenByCommunityNo($communityNo);
        $params['biz_content'] = json_encode($biz);
        return $this->_alipay->execute('alipay.eco.cplife.bill.batchquery', $params, null, $token);
    }

    public function testQueryBills($data,$communityNo)
    {
        $token = AliTokenService::service()->getTokenByCommunityNo($communityNo);
        $params['biz_content'] = json_encode($data);
        //print_r($params);exit;
        return $this->_alipay->execute('alipay.eco.cplife.bill.batchquery', $params, null, $token);
    }


    /**
     * 支付宝物业账单删除
     * @param $communityNo
     * @param $billList
     */
    public function deleteBill($communityNo, $billIds)
    {
        $biz = [
            'community_id' => $communityNo,
            'bill_entry_id_list' => $billIds,
        ];
        $params['biz_content'] = json_encode($biz);
        $token = AliTokenService::service()->getTokenByCommunityNo($communityNo);
        return $this->_alipay->execute('alipay.eco.cplife.bill.delete', $params, null, $token);
    }

    /**
     * 物业账单支付宝回调，返回输出结果
     */
    public function responseToAli($status)
    {
        $biz = json_encode(['econotify' => $status]);
        return $this->_alipay->encryptAndSign($biz, $this->_alipay->alipayPublicKey, $this->_alipay->rsaPrivateKey, 'UTF-8', false, true);
    }


    /**
     * 房屋批量同步到支付宝
     * @param $data
     * @return mixed
     */
    public function batchRoomInfo($token, $data)
    {
        //获取token
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        $params['biz_content'] = json_encode($data);
        return $this->_alipay->execute('alipay.eco.cplife.roominfo.upload', $params, null, $token);
    }
    /**
     * 查询房屋
     * @param $data
     * @return array|bool
     */
    public function selectRoominfo($communityNo)
    {
        $biz = ['community_id' => $communityNo, 'page_num' => 1, 'page_size' => 200];
        $params['biz_content'] = json_encode($biz);
        $token = AliTokenService::service()->getTokenByCommunityNo($communityNo);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.eco.cplife.roominfo.query', $params, null, $token);
    }

    /**
     * 批量上传房屋
     * @param $data
     * @return array|bool
     */
    public function uploadRoominfo($data)
    {
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.eco.cplife.roominfo.upload', $params, null, $token);
    }

    /**
     * 删除房屋
     * @param $data
     * @return array|bool
     */
    public function deleteRoominfo($data)
    {
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.eco.cplife.roominfo.delete', $params, null, $token);
    }

    /**
     * 创建统一交易接口
     * @param $data
     * @return array|bool
     */
    public function tradeCreate($data,$ding_url)
    {
        $params['notify_url'] = $ding_url;
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.trade.create', $params, null, $token);
    }

    /**
     * 创建当面付
     * @param $data
     * @return array|bool
     */
    public function tradeRefund($data,$ding_url)
    {
        $params['notify_url'] = $ding_url;
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.trade.precreate', $params, null, $token);
    }
    /**
     * 查询 当面付
     * @param $data
     * @return array|bool
     */
    public function queryRefund($data)
    {
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.trade.query', $params, null, $token);
    }

    /**
     * $margin：生成当面付二维码
     */
    function create_erweima($content,$id) {
        $savePath = F::imagePath('qrcode');
        $imgUrl = '';
        //设置上传路径
        if (!file_exists($savePath)) {
            FileHelper::createDirectory($savePath, 0755, true);
        }
        $img_name = $id.'.png';
        //生成一个二维码图片
        QrcodeService::service()->png($content, $savePath.$img_name, QR_ECLEVEL_H, '200');
        if (file_exists($savePath.$img_name)) {
            chmod($savePath.$img_name, 0755);
            //图片上传到七牛
            $key_name  = md5(uniqid(microtime(true),true)).'.png';
            $new_file  = $savePath.$img_name;
            $imgUrl = UploadService::service()->saveQiniu($key_name, $new_file);
        }
        return $imgUrl;
    }
    /**
     * 查询小区
     * @param $data
     * @return array|bool
     */
    public function selCommunity($data)
    {
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.eco.cplife.community.details.query', $params, null, $token);
    }
    /**
     * 下线小区
     * @param $data
     * @return array|bool
     */
    public function OfflineCommunity($data)
    {
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.eco.cplife.basicservice.modify', $params, null, $token);
    }
    /**
     * 撤销退款接口
     * @param $data
     * @return array|bool
     */
    public function refundBill($data)
    {
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.trade.refund', $params, null, $token);
    }
    /**
     * 查询支付宝账单：交易流水
     * @param $data
     * @return array|bool
     */
    public function selTrade($data)
    {
        $params['biz_content'] = json_encode($data);
        $token = AliTokenService::service()->getTokenByCommunityNo($data['community_id']);
        if (!$token) {
            return $this->failed('物业公司未授权');
        }
        return $this->_alipay->execute('alipay.data.dataservice.bill.downloadurl.query', $params, null, $token);
    }


    /**
     * 测试创建订单
     * @param $data
     * @return array|bool
     */
    public function testTradeCreate($data, $notifyUrl)
    {
        $params['notify_url'] = $notifyUrl;
        $params['biz_content'] = json_encode($data);
        $token = '201908BBa83b751503b44f2ca88e4c165d409X44';
        return $this->_alipay->execute('alipay.trade.create', $params, null, $token);
    }
}