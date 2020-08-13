<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/6/28
 * Time: 16:15
 */
namespace app\modules\ding_property_app\services;
use app\models\PsBill;
use app\models\PsRepair;
use common\core\PsCommon;
use service\BaseService;
use service\inspect\InspectionEquipmentService;
use service\issue\RepairService;
use service\property_basic\JavaDDService;
use service\property_basic\JavaService;
use Yii;

class HomeService extends BaseService
{
    public static $statusLabel = [
        '1'   => '待处理',
        '2'   => '待完成',
        '3'   => '已完成',
        '4'   => '已结束',
        '5'   => '已复核',
        '6'   => '已作废',
    ];

    public static $repairTypeLabel = [
        1 => '公共区域',
        2 => '户内'
    ];

    /**
     * 查询报事报修列表
     * @param $userId
     * @param array $status
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRepairList($userId, $status = [])
    {
        if (empty($status)) {
            $status = [1,2,3,4];
        }
        $repairIdList = PsRepairAssign::find()
            ->select(['repair_id'])
            ->where(['user_id' => $userId])
            ->asArray()
            ->column();

        $repairList = PsRepair::find()
            ->select(['ps_repair.room_username as owner_name', 'ps_repair.contact_mobile',
                'ps_repair.room_address as owner_address', 'ps_repair.id as issue_id', 'ps_repair.repair_no as issue_no',
                'ps_repair.create_at as created_at', 'ps_repair.expired_repair_time', 'ps_repair.expired_repair_type',
                'ps_repair.repair_type_id','ps_community.name as community_name','ps_repair.status'
            ])
            ->leftJoin('ps_community', 'ps_community.id = ps_repair.community_id')
            ->where(['ps_repair.id' => $repairIdList])
            ->andWhere(['ps_repair.status' => $status])
            ->orderBy('ps_repair.status asc,ps_repair.id desc')
            ->asArray()
            ->all();
        if ($repairList) {
            foreach ($repairList as $k => $v) {
                $repairList[$k]['owner_name'] = $v['owner_name'] ? $v['owner_name'] : '';
                $repairList[$k]['owner_phone'] = $v['contact_mobile'] ? $v['contact_mobile'] : '';
                $repairList[$k]['expired_repair_time'] = $this->transformDate($v['expired_repair_time'], $v['expired_repair_type']);
                $repairList[$k]['created_at'] = date("Y-m-d H:i", $v['created_at']);
                $repairList[$k]['status_label'] = self::$statusLabel[$v['status']];

                //查询报修类型
                $tmpType = RepairService::service()->getRepairTypeById($v['repair_type_id']);
                $repairList[$k]['repair_type_label'] = !empty($tmpType) ? $tmpType['name'] : '';

            }
        } else {
            $repairList = [];
        }
        return $repairList;
    }

    /**
     * 将时间转换为今天，明天，日期输出
     * @param $time
     * @return string
     */
    public function transformDate($time, $expiredType)
    {
        $today    = date("Y-m-d",time());
        $tomorrow = date("Y-m-d", strtotime("+1 day"));
        $reqDate  = $time ? date("Y-m-d", $time) : '';
        $str = "";
        if ($reqDate == $today) {
            $str .= "今天";
        } elseif ($reqDate == $tomorrow) {
            $str .= "明天";
        } else {
            $str .= $reqDate;
        }
        $str .= ' '.!empty(RepairService::$expiredType[$expiredType]) ? RepairService::$expiredType[$expiredType] : '';
        return $str;
    }

    /**
     * 查询钉钉首页数据
     * @param $reqArr
     * @return mixed
     */
    public function getDingHomeIndex($reqArr)
    {
        if(empty($reqArr['community_id'])){
            return PsCommon::responseFailed('小区id必填');
        }
        //物业公司是否设置收款账号
        $javaService = new JavaService();
        $javaParams['token'] = $reqArr['token'];
        $javaParams['id'] = $reqArr['corpId'];
        $javaResult = $javaService->authJudgeAuth($javaParams);
        $corpId = $reqArr['ddCorpId'];
        $menu = [
            [
                'icon'=>"http://static.zje.com/2020040710054928444.png",
                'name'=>"报事报修",
                'port' => '/digit-property-repair',
                'icon_type' => '1',
                'url'=>"/pages/index/typeRepair/index"
            ],
            [
                'icon'=>"http://static.zje.com/2020040710071219575.png",
                'name'=>"巡检执行",
                'port' => '/digit-property-ckexecute',
                'icon_type' => '1',
                'url'=>"/pages/Inspection/index/index"
            ],
            [
                'icon'=>"http://static.zje.com/2020040710080931069.png",
                'name'=>"巡检管理",
                'port' => '/digit-property-ckmgt',
                'icon_type' => '1',
                'url'=>"/pages/patrolManagement/index/index"
            ],
            [
                 'icon' => "http://static.zje.com/2020041509563541640.png",
                 'name' => "访客通行",
                 'port' => '/digit-property-visitorpass',
                'icon_type' => '1',
                 'url' => "/pages/visitor/index/index"
            ],
            //  [
            //      'icon'=>"http://zje-health-static.zje.com/pension/bjgl2.png",
            //     'name'=>"报警管理",
            //      'icon_type' => '1',
            //     'url'=>"/pages/alarm/index/index",
            //   ],
            // [
            // 'icon' => "http://static.zje.com/2020041509563541640.png",
            // 'name' => "事件中心",
            // 'icon_type' => '2',
            // 'url' => "dingtalk://dingtalkclient/action/open_micro_app?appId=47657&corpId={$corpId}&page=pages/eventCenterList/eventCenterList"
            // ],
            // [
            //     'icon' => "http://static.zje.com/2020081117513559255.png",
            //     'name' => "临时登记",
            //     'icon_type' => '2',
            //     'url' => "dingtalk://dingtalkclient/action/open_micro_app?appId=47657&corpId={$corpId}&page=pages/register/index/index"
            // ]
        ];
        if($javaResult){
            //判断小区是否有账单
            $count = PsBill::find()->select(['id'])->where(['=','community_id',$reqArr['community_id']])->count();
            if($count>0){
                $menu= [
                  [
                        'icon'=>"http://static.zje.com/2020040710054928444.png",
                        'name'=>"报事报修",
                        'port' => '/digit-property-repair',
                        'icon_type' => '1',
                        'url'=>"/pages/index/typeRepair/index"
                  ],
                  [
                        'icon'=>"http://static.zje.com/2020040710071219575.png",
                        'name'=>"巡检执行",
                        'port' => '/digit-property-ckexecute',
                        'icon_type' => '1',
                        'url'=>"/pages/Inspection/index/index"
                  ],
                  [
                        'icon'=>"http://static.zje.com/2020040710074024088.png",
                        'name'=>"物业收费",
                        'port' => '/digit-property-pay',
                        'icon_type' => '1',
                        'url'=>"/pages/property-payment/index/index"
                  ],
                  [
                        'icon'=>"http://static.zje.com/2020040710080931069.png",
                        'name'=>"巡检管理",
                        'port' => '/digit-property-ckmgt',
                        'icon_type' => '1',
                        'url'=>"/pages/patrolManagement/index/index"
                  ],
                    [

                        'icon' => "http://static.zje.com/2020041509563541640.png",
                        'name' => "访客通行",
                        'port' => '/digit-property-visitorpass',
                        'icon_type' => '1',
                        'url' => "/pages/visitor/index/index"
                    ],
                    // [
                    //     'icon'=>"http://zje-health-static.zje.com/pension/bjgl2.png",
                    //     'name'=>"报警管理",
                    //     'icon_type' => '1',
                    //     'url'=>"/pages/alarm/index/index",
                    // ],
                    // [
                    //     'icon' => "http://static.zje.com/2020041509563541640.png",
                    //     'name' => "事件中心",
                    //     'icon_type' => '2',
                    //     'url' => "dingtalk://dingtalkclient/action/open_micro_app?appId=47657&corpId={$corpId}&page=pages/eventCenterList/eventCenterList"
                    // ],
                    // [
                    //     'icon' => "http://static.zje.com/2020081117513559255.png",
                    //     'name' => "临时登记",
                    //     'icon_type' => '2',
                    //     'url' => "dingtalk://dingtalkclient/action/open_micro_app?appId=47657&corpId={$corpId}&page=pages/register/index/index"
                    // ]
                ];
            }
        }
        return $menu;
    }

    /**
     * 查看二级菜单
     * @param $reqArr
     * @return array|string
     */
    public function getMenus($reqArr)
    {
        $menus = UserService::service()->getMenus($reqArr);
        return $menus;
    }

    /**
     * 查看三级菜单
     * @param $reqArr
     * @return array|string
     */
    public function getViewMenus($reqArr)
    {
        $menus = UserService::service()->getViewMenus($reqArr);
        return $menus;
    }

    public function getDetailMenus($reqArr)
    {
        $menus = UserService::service()->getDetailMenus($reqArr);
        return $menus;
    }
}