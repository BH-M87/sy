<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/22
 * Time: 9:31
 * Desc:兑换记录service
 */
namespace service\property_basic;

use app\models\PsDeliveryRecords;
use app\models\PsInspectRecord;
use app\models\PsRepair;
use app\models\PsRepairAppraise;
use app\models\PsRepairRecord;
use common\core\Curl;
use common\core\PsCommon;
use service\BaseService;
use Yii;
use yii\db\Exception;

class DeliveryRecordsService extends BaseService{

    const USE_SCORE = '/internal/volunteer/use-score';

//        if(empty($params['user_id'])){
//            return $this->failed("用户id不能为空");
//        }
//        if(empty($params['community_id'])){
//            return $this->failed("小区id不能为空");
//        }
//        if(empty($params['room_id'])){
//            return $this->failed("房屋id不能为空");
//        }
//        if(empty($params['product_id'])){
//            return $this->failed("商品id不能为空");
//        }
//        if(empty($params['product_num'])){
//            return $this->failed("商品数量不能为空");
//        }
//        if(empty($params['volunteer_id'])){
//            return $this->failed("志愿者id不能为空");
//        }

//        $javaParams['communityId'] = $params['community_id'];
//        $javaParams['roomId'] = $params['room_id'];
//        $javaParams['token'] = $params['token'];
//        $javaService = new JavaOfCService();
//        $result = $javaService->getResidentFullAddress($javaParams);
//        if(!isset($result['fullName'])||empty($result['fullName'])){
//            return $this->failed("住户信息不存在");
//        }
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
//            $recordsParams['cust_name'] = !empty($result['memberName'])?$result['memberName']:'';
//            $recordsParams['cust_mobile'] = !empty($result['memberMobile'])?$result['memberMobile']:'';
//            $recordsParams['address'] = !empty($result['fullName'])?$result['fullName']:'';
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
                $trans->commit();
                return $this->success(['id'=>$model->attributes['id']]);
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
                foreach($result['list'] as $key=>$value){
                    $result['list'][$key]['create_at_msg'] = !empty($value['create_at'])?date('Y/m/d',$value['create_at']):'';
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

    //文明码统计
    public function civilStatistics($params){
        if(empty($params['community_id'])){
            return $this->failed("小区id必填");
        }

        //报事保修统计
        $data['repair'] = self::doRepairStatistics($params);
        //巡更巡检统计
        $data['inspect'] = self::doInspectStatistics($params);



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
//        $fakeData = self::fakeData($params);
//        $data = array_merge($data,$fakeData);


        return $this->success($data);
    }

    public function fakeData($params){
        $key = mb_substr($params['community_id'],-1);
        $data= [
            '0'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                      'volunteer_count' => '71',         //志愿者人数
                       'activity_count' => '26',         //志愿者参加活动人数
                       'rate' => '2%',                   //志愿者人数比
                       'all_time' => '146小时',               //公益活动总时长
                       'average_time' => '2小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '1'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '42',         //志愿者人数
                    'activity_count' => '16',         //志愿者参加活动人数
                    'rate' => '5%',                   //志愿者人数比
                    'all_time' => '64小时',               //公益活动总时长
                    'average_time' => '1.5小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '2'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '96',         //志愿者人数
                    'activity_count' => '37',         //志愿者参加活动人数
                    'rate' => '10%',                   //志愿者人数比
                    'all_time' => '100小时',               //公益活动总时长
                    'average_time' => '1.1小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '3'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '74',         //志愿者人数
                    'activity_count' => '29',         //志愿者参加活动人数
                    'rate' => '7%',                   //志愿者人数比
                    'all_time' => '80小时',               //公益活动总时长
                    'average_time' => '1小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '4'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '154',         //志愿者人数
                    'activity_count' => '57',         //志愿者参加活动人数
                    'rate' => '11%',                   //志愿者人数比
                    'all_time' => '200小时',               //公益活动总时长
                    'average_time' => '1小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '5'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '209',         //志愿者人数
                    'activity_count' => '60',         //志愿者参加活动人数
                    'rate' => '16%',                   //志愿者人数比
                    'all_time' => '210小时',               //公益活动总时长
                    'average_time' => '1小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '6'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '86',         //志愿者人数
                    'activity_count' => '23',         //志愿者参加活动人数
                    'rate' => '5%',                   //志愿者人数比
                    'all_time' => '200小时',               //公益活动总时长
                    'average_time' => '2小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '7'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '124',         //志愿者人数
                    'activity_count' => '36',         //志愿者参加活动人数
                    'rate' => '5%',                   //志愿者人数比
                    'all_time' => '326小时',               //公益活动总时长
                    'average_time' => '3小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '8'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '124',         //志愿者人数
                    'activity_count' => '36',         //志愿者参加活动人数
                    'rate' => '5%',                   //志愿者人数比
                    'all_time' => '326小时',               //公益活动总时长
                    'average_time' => '3小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
            '9'=>[
                //居民问题反馈率
                'residents'=>[
                    'industry_rate' =>'0',  //业委会处理率
                    'dwell_rate'=>0,        //居委会处理率
                    'street_rate'=>0,       //街道处理率
                ],


                //文明志愿者
                'volunteer'=>[
                    'volunteer_count' => '83',         //志愿者人数
                    'activity_count' => '36',         //志愿者参加活动人数
                    'rate' => '9%',                   //志愿者人数比
                    'all_time' => '116小时',               //公益活动总时长
                    'average_time' => '1.4小时',           //公益活动平均时长
                ],

                //社会公益宣传
                'publicize'=>[
                    'notice_count' => '0',      //小区公告数量
                ],

                //邻里活动
                'neighborhood'=>[
                    'activity_count' => '0',         //物业活动举办次数
                    'activity_rate' => '0',          //物业活动签到率
                ],

                //曝光台
                'exposure'=>[
                    'count' => '0',          //曝光次数
                ],
            ],
        ];

        return $data[$key];
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