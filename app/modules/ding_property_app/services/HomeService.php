<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/6/28
 * Time: 16:15
 */
namespace app\modules\ding_property_app\services;
use app\models\PsRepair;
use service\BaseService;
use service\issue\RepairService;
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
        $menus = UserService::service()->getDingUserMenu($reqArr);
        return $menus;
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