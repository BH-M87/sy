<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:31
 * Desc:兑换记录service
 */
namespace service\property_basic;

use app\models\Goods;
use app\models\PsDeliveryRecords;
use app\models\PsInspectRecord;
use app\models\PsRepair;
use app\models\PsRepairAppraise;
use app\models\PsRepairRecord;
use app\models\PsCommunitySet;
use common\core\Curl;
use common\core\F;
use common\core\PsCommon;
use service\BaseService;
use service\common\QrcodeService;
use Yii;
use yii\db\Exception;

class DeliveryRecordsService extends BaseService{

    const USE_SCORE = '/internal/volunteer/use-score';

    //兑换记录新增（小程序端）
    public function addOfC($params){

        $trans = Yii::$app->db->beginTransaction();
        try{
            $recordsParams['product_id'] = !empty($params['product_id'])?$params['product_id']:'';
            $recordsParams['product_num'] = !empty($params['product_num'])?$params['product_num']:'';
            $recordsParams['community_id'] = !empty($params['community_id'])?$params['community_id']:'';
            $recordsParams['room_id'] = !empty($params['room_id'])?$params['room_id']:'';
            $recordsParams['user_id'] = !empty($params['user_id'])?$params['user_id']:'';
            $recordsParams['volunteer_id'] = !empty($params['volunteer_id'])?$params['volunteer_id']:'';
            $recordsParams['cust_name'] = !empty($params['cust_name'])?$params['cust_name']:'';
            $recordsParams['cust_mobile'] = !empty($params['cust_mobile'])?$params['cust_mobile']:'';
            $recordsParams['address'] = !empty($params['address'])?$params['address']:'';
            $model = new PsDeliveryRecords(['scenario'=>'volunteer_add']);
            if($model->load($recordsParams,'')&&$model->validate()){
                if(!$model->save()){
                    return $this->failed('新增失败！');
                }
                //调用街道志愿者接口 减积分
                $streetParams['sysUserId'] = $model->attributes['volunteer_id'];
                $streetParams['score'] = $model->attributes['integral'];
//                    $streetParams['sysUserId'] = 17;
//                    $streetParams['score'] = 0.1;
                $streetParams['content'] = $model->attributes['product_name']."兑换";
                $streetResult = self::doReduce($streetParams);
                if($streetResult['code']!=1){
                    throw new Exception($streetResult['message']);
                }
                //生成核销二维码
//                $qrParams['url'] = "pages/mine/volunteerWriteOff/volunteerWriteOff";
//                $qrParams['token'] = $params['token'];
//                $qrParams['community_id'] = $params['community_id'];
//                $qrParams['queryParam'] = 'id='.$model->attributes['id'];
//                $qrUrl = self::generateQrCode($qrParams);
//                if(!empty($qrUrl)){
//                    //保护二维码
//                    $model::updateAll(['verification_qr_code'=>$qrUrl],['id'=>$model->attributes['id']]);
//                }
                if($model->receiveType==2){
                    $qrUrl = self::createQrcode(['community_id'=>$params['community_id'],'id'=>$model->attributes['id']]);
                    if(!empty($qrUrl)){
                        //保护二维码
                        $model::updateAll(['verification_qr_code'=>$qrUrl],['id'=>$model->attributes['id']]);
                    }
                }
                $trans->commit();
                return $this->success(['id'=>$model->attributes['id'],'verification_qr_code'=>!empty($qrUrl)?$qrUrl:'']);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }


    //兑换记录新增（小程序端）
    public function add($params){

        $model = new PsDeliveryRecords(['scenario'=>'volunteer_add']);
        if($model->load($params,'')&&$model->validate()){
            if(!$model->save()){
                return $this->failed('新增失败！');
            }
            //调用街道志愿者接口 减积分
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    // 生成核销二维码图片
    private static function createQrcode($params)
    {
        $savePath = F::imagePath('visit');
//        $logo = Yii::$app->basePath . '/web/img/lyllogo.png'; // 二维码中间的logo
        $url = Yii::$app->getModule('property')->params['alipay_web_host'] . '#/pages/mine/volunteerWriteOff/volunteerWriteOff?community_id='.$params['community_id'].'&id=' . $params['id'];

//        $imgUrl = QrcodeService::service()->generateCommCodeImage($savePath, $url, $params['id'], $logo); // 生成二维码图片
        $imgUrl = QrcodeService::service()->generateCommCodeImage($savePath, $url, $params['id'], ''); // 生成二维码图片
        return $imgUrl;
    }


    /*
     *生成二维码
     * input
     *  community_id    小区id
     *  token           token
     *  url             前端url
     *  queryParams     参数
     */
    public function generateQrCode($params){
        $javaService = new JavaOfCService();
        $javaParams['data']['communityId'] = $params['community_id'];
        $javaParams['data']['describe'] = $params['community_id']."小程序二维码";
//        $javaParams['data']['communityId'] = '1254991620133425154';
//        $javaParams['data']['describe'] = "小程序二维码";
        $javaParams['data']['queryParam'] = $params['queryParam'];
        $javaParams['data']['urlParam'] = $params['url'];
        $javaParams['token'] = $params['token'];
        $javaParams['url'] = '/corpApplet/selectCommunityQrCode';
        $javaResult = $javaService->selectCommunityQrCode($javaParams);
        $qrCodeUrl = !empty($javaResult['qrCodeUrl'])?$javaResult['qrCodeUrl'].".jpg":'';
        return $qrCodeUrl;
    }

    //扣除积分
    public function doReduce($data){
        $host = Yii::$app->modules['ali_small_lyl']->params['volunteer_host'];
        $url = $host.self::USE_SCORE;
        $curl = Curl::getInstance();
        $result = $curl::post($url,$data);
        return json_decode($result,true);
    }

    //兑换记录详情
    public function detail($params){
        $model = new PsDeliveryRecords(['scenario'=>'detail']);
        if($model->load($params,'')&&$model->validate()){
            $result = $model->detail($params);
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //兑换列表
    public function getList($params){
        $model = new PsDeliveryRecords();
        $result = $model->getList($params);
        if(!empty($result['list'])){
            foreach($result['list'] as $key=>$value){
                $result['list'][$key]['create_at_msg'] = !empty($value['create_at'])?date('Y-m-d H:i',$value['create_at']):'';
                $result['list'][$key]['cust_name'] = !empty($value['cust_name'])?PsCommon::hideName($value['cust_name']):'';
                $result['list'][$key]['cust_mobile'] = !empty($value['cust_mobile'])?PsCommon::hideMobile($value['cust_mobile']):'';
                $result['list'][$key]['status_msg'] = !empty($value['status'])?$model::STATUS[$value['status']]:'';
                $result['list'][$key]['delivery_type_msg'] = !empty($value['delivery_type'])?$model::DELIVERY_TYPE[$value['delivery_type']]:'';
            }
        }
        return $this->success($result);
    }

    //兑换列表小程序端
    public function getListOfC($params){
        $model = new PsDeliveryRecords(['scenario'=>'app_list']);
        if($model->load($params,'')&&$model->validate()){
            $result = $model->getListOfC($params);
            if(!empty($result['list'])){
                foreach($result['list'] as $key=>&$value){
                    $value['create_at_msg'] = !empty($value['create_at'])?date('Y/m/d',$value['create_at']):'';
                    $receiveType = Goods::findOne($value['product_id'])->receiveType;
                    $value['verification_qr_code'] = $receiveType == 2 ? $value['verification_qr_code'] : '';
                }
            }
            return $this->success($result);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    //兑换记录发货
    public function edit($params){
        if(empty($params['delivery_type'])){
            return $this->failed("配送方式必填！");
        }
        $model = new PsDeliveryRecords();
        $scenario = $params['delivery_type'] == 1?'send_edit':'self_edit';
        $model->setScenario($scenario);
        $updateParams['id'] = !empty($params['id'])?$params['id']:'';
        $updateParams['delivery_type'] = !empty($params['delivery_type'])?$params['delivery_type']:'';
        $updateParams['courier_company'] = !empty($params['courier_company'])?$params['courier_company']:'';
        $updateParams['order_num'] = !empty($params['order_num'])?$params['order_num']:'';
        $updateParams['records_code'] = !empty($params['records_code'])?$params['records_code']:'';
        $updateParams['operator_id'] = !empty($params['create_id'])?$params['create_id']:'';
        $updateParams['operator_name'] = !empty($params['create_name'])?$params['create_name']:'';
        if($model->load($updateParams,'')&&$model->validate()){
            if(!$model->edit($updateParams)){
                return $this->failed("操作失败");
            }
            return $this->success(['id'=>$model->attributes['id']]);
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }

    /*
     * 文明码统计
     * $javaService = new JavaOfCService();
        $javaParams['data']['communityId'] = $params['community_id'];
        $javaParams['data']['describe'] = $params['community_id']."小程序二维码";
//        $javaParams['data']['communityId'] = '1254991620133425154';
//        $javaParams['data']['describe'] = $results[0]['community_name']."小程序二维码";
        $javaParams['data']['queryParam'] = "x=1";
        $javaParams['data']['urlParam'] = "pages/homePage/homePage/homePage";
        $javaParams['token'] = $params['token'];
        $javaParams['url'] = '/corpApplet/selectCommunityQrCode';
        $javaResult = $javaService->selectCommunityQrCode($javaParams);
     */
    public function civilStatistics($params){
        if(empty($params['community_id'])){
            return $this->failed("小区id必填");
        }

        //报事保修统计
        $data['repair'] = self::doRepairStatistics($params);
        //巡更巡检统计
        $data['inspect'] = self::doInspectStatistics($params);

        $qrCode = self::getQrCode($params);
        $data['qr_code_url'] = $qrCode['qr_code_url'];
        $data['bang_code_url'] = $qrCode['bang_code_url'];

        //垃圾分类
        $data['rubbish']['rubbish_sort'] = '无';     //有无垃圾分类时间和地点
        $data['rubbish']['rubbish_score'] = '无';    //垃圾分类居民评分

        //保洁绿化
        $data['cleaning']['cleaning_plan'] = '无';   //有无工作计划
        $data['cleaning']['cleaning_work'] = '无';   //有无上传工作记录

        //物业收支公开
        $data['property']['regular'] = '无';   //财务定期公示
        $data['property']['certificate'] = '无';   //财务凭证上传

        //居民问题反馈率
        $data['residents']['industry_rate'] = '无';      //业委会处理率
        $data['residents']['dwell_rate'] = '无';      //居委会处理率
        $data['residents']['street_rate'] = '无';         //街道处理率

        //文明志愿者
        $data['volunteer']['volunteer_count'] = '无';      //志愿者人数
        $data['volunteer']['activity_count'] = '无';      //志愿者参加活动人数
        $data['volunteer']['rate'] = '无';               //志愿者人数比
        $data['volunteer']['all_time'] = '无';               //公益活动总时长
        $data['volunteer']['average_time'] = '无';               //公益活动平均时长

        //社会公益宣传
        $data['publicize']['notice_count'] = '无';          //小区公告数量

        //邻里活动
        $data['neighborhood']['activity_count'] = '无';          //物业活动举办次数
        $data['neighborhood']['activity_rate'] = '无';          //物业活动签到率

        //曝光台
        $data['exposure']['count'] = '无';          //曝光次数
        //调用java接口
        $javaService = new JavaOfCService();
        $javaParams['id'] = $params['community_id'];
//        $javaParams['id'] = "1200020193290747905";
        $javaParams['token'] = $params['token'];
        $javaResult = $javaService->civilizationStatistics($javaParams);
        if(!empty($javaResult)){
            $data['volunteer']['volunteer_count'] = !empty($javaResult['volunteerNum'])?$javaResult['volunteerNum']:'无';      //志愿者人数
            $data['volunteer']['activity_count'] = !empty($javaResult['volunteerActNum'])?$javaResult['volunteerActNum']:'无';      //志愿者参加活动人数
            $data['volunteer']['rate'] = !empty($javaResult['volunteerPercent'])?$javaResult['volunteerPercent']."%":'-';               //志愿者人数比
            $data['volunteer']['all_time'] = !empty($javaResult['publicBenefitDuration'])?$javaResult['publicBenefitDuration']."小时":'无';               //公益活动总时长
            $data['volunteer']['average_time'] = !empty($javaResult['averagePublicBenefitDuration'])?$javaResult['averagePublicBenefitDuration']."小时":'无';               //公益活动平均时长

            //社会公益宣传
            $data['publicize']['notice_count'] = !empty($javaResult['noticeNum'])?$javaResult['noticeNum']:'无';          //小区公告数量

            //邻里活动
            $data['neighborhood']['activity_count'] = !empty($javaResult['actNum'])?$javaResult['actNum']:'无';          //物业活动举办次数
            $data['neighborhood']['activity_rate'] = !empty($javaResult['actSignPercent'])?$javaResult['actSignPercent']."%":'无';          //物业活动签到率

            //曝光台
//            $data['exposure']['count'] = '0';          //曝光次数
        }
        //文明志愿者


        return $this->success($data);
    }

    //获得一区一码二维码 和帮帮吗
    public function getQrCode($params){
        $setResult = PsCommunitySet::find()->select(['id','qr_code','bang_code'])->where(['=','community_id',$params['community_id']])->asArray()->one();
        $return_qr_code = '';
        $return_bang_code = '';
        if(!empty($setResult)){
            $return_qr_code = $setResult['qr_code'];
            if(empty($setResult['qr_code'])){
                $qrParams['url'] = "pages/homePage/homePage/homePage";
                $qrParams['token'] = $params['token'];
                $qrParams['community_id'] = $params['community_id'];
                $qrParams['queryParam'] = 'x=1&community_id='.$params['community_id'];
                $qrParams['queryParam'] = urlencode($qrParams['queryParam']);
                $qrCodeUrl = self::generateQrCode($qrParams);
                $setParams['community_id'] = $params['community_id'];
                if(!empty($qrCodeUrl)){
                    $editParams['qr_code'] = $qrCodeUrl;
                    $return_qr_code = $qrCodeUrl;
                }
            }
            $return_bang_code = $setResult['bang_code'];
            if(empty($setResult['bang_code'])){
                $bangParams['url'] = "pages/homePage/homePage/homePage";
                $bangParams['token'] = $params['token'];
                $bangParams['community_id'] = $params['community_id'];
                $bangParams['queryParam'] = 'backCode=1&community_id='.$params['community_id'];
                $bangParams['queryParam'] = urlencode($bangParams['queryParam']);
                $bangCodeUrl = self::generateQrCode($bangParams);
                if(!empty($bangCodeUrl)){
                    $editParams['bang_code'] = $bangCodeUrl;
                    $return_bang_code = $bangCodeUrl;
                }
            }
            if(!empty($editParams)){
                PsCommunitySet::updateAll($editParams,['id'=>$setResult['id']]);
            }

        }else{
            $qrParams['url'] = "pages/homePage/homePage/homePage";
            $qrParams['token'] = $params['token'];
            $qrParams['community_id'] = $params['community_id'];
            $qrParams['queryParam'] = 'x=1&community_id='.$params['community_id'];
            $qrParams['queryParam'] = urlencode($qrParams['queryParam']);
            print_r($qrParams);die;
            $qrCodeUrl = self::generateQrCode($qrParams);
            $setParams['community_id'] = $params['community_id'];
            $setParams['community_name'] = !empty($params['community_name'])?$params['community_name']:'';
            if(!empty($qrCodeUrl)){
                $setParams['qr_code'] = $qrCodeUrl;
                $return_qr_code = $qrCodeUrl;
            }

            $bangParams['url'] = "pages/homePage/homePage/homePage";
            $bangParams['token'] = $params['token'];
            $bangParams['community_id'] = $params['community_id'];
            $bangParams['queryParam'] = 'backCode=1&community_id='.$params['community_id'];
            $bangParams['queryParam'] = urlencode($bangParams['queryParam']);
            $bangCodeUrl = self::generateQrCode($bangParams);
            if(!empty($bangCodeUrl)){
                $setParams['bang_code'] = $bangCodeUrl;
                $return_bang_code = $bangCodeUrl;
            }
            $setModel = new PsCommunitySet(['scenario'=>'add']);
            if($setModel->load($setParams,'')&&$setModel->validate()){
                $setModel->saveData();
            }
        }
        $qr_url = Yii::$app->modules['ali_small_lyl']->params['qr_code_url'];
        $dui_url = Yii::$app->modules['ali_small_lyl']->params['dui_code_url'];
        return [
            'qr_code_url'=>!empty($return_qr_code)?$return_qr_code:$qr_url,
            'bang_code_url'=>!empty($return_bang_code)?$return_bang_code:$dui_url,
        ];

    }


    public function doInspectStatistics($params){
        $fields = ['count(id) as num','run_status'];
        $inspectResult = PsInspectRecord::find()->select($fields)->where(['=','community_id',$params['community_id']])->groupBy(['run_status'])->asArray()->all();
        $countAll = 0;
        $overdue = '无';
        $overdue_rate = '无';
        if(!empty($inspectResult)){
            foreach($inspectResult as $key=>$value){
                switch($value['run_status']){
                    case 1:     //逾期
                        $overdue += $value['num'];
                        break;
                    case 2:
                    case 3:
                        break;

                }
                $countAll+=$value['num'];
            }
            if($overdue>0){
                $overdue_rate = number_format($overdue/$countAll,2).'%';
            }
        }
        return [
            'countAll'=>$countAll,      // 计划数量
            'inspectPlan'=>$countAll>0?'有':'无',
            'overdue_rate'=>$overdue_rate, //逾期率
        ];
    }

    /*
     *   物业报事报修响应平均时间：
     *  （物业接单时间 - 业主上报时间）/ 上报件数
     *   < 60分钟，按分钟显示；> 60 分钟按小时计算。如90分钟，显示为1.5小时。
     */
    public function doRepairStatistics($params){
        //报事保修统计
        $repairFields = ['sum((c.create_at-r.create_at)) as deal_at'];
        $repairModel = PsRepair::find()->alias('r')
            ->leftJoin(['c'=>PsRepairRecord::tableName()],'c.repair_id=r.id')
            ->select($repairFields)
            ->where(['=','r.community_id',$params['community_id']])->andWhere(['=','c.status',1]);
        $totalsTime = $repairModel->asArray()->one();
        $answerTime = '无';
        if($totalsTime['deal_at']>0){
            //订单总数
            $repairCount = PsRepair::find()->select(['id'])->where(['=','community_id',$params['community_id']])->count();
            //报事保修相应时间
            $averageTime = ceil($totalsTime['deal_at']/$repairCount);
            if($averageTime>60*60){
                //显示小时
                $answerTime = number_format($averageTime/3600,2)."小时";
            }else{
                //显示分钟
//                $answerTime = number_format($averageTime/60,1)."分钟";
                $answerTime = round($averageTime/60,2)."分钟";
            }
        }

        $scoreFields = ['sum(s.start_num) as score','count(s.id) as totals'];
        $scoreResult = PsRepairAppraise::find()->alias('s')
                    ->leftJoin(['r'=>PsRepair::tableName()],'s.repair_id=r.id')
                    ->select($scoreFields)->where(['=','r.community_id',$params['community_id']])
                    ->asArray()->one();
        $averageScore = '无';
        if(!empty($scoreResult['score'])){
            $averageScore = number_format($scoreResult['score']/$scoreResult['totals'],1)."分";
        }
        return [
            'answerTime' => $answerTime,  //报事报修平均相应时长
            'averageScore' => $averageScore,    //报修平均分
        ];
    }
}