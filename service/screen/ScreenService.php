<?php
namespace service\screen;

use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\MyException;
use common\core\PsCommon;
use common\core\Curl;
use common\core\F;

use service\BaseService;

use app\models\PsRepair;

use service\property_basic\JavaOfCService;

class ScreenService extends BaseService
{
    public static $repairStatus = ['1' => '已接单', '2' => '开始处理', '3' => '已完成', '6' => '已关闭', '7' => '待处理'];
    // 大屏
    public function index($p)
    {
        $get_url = "116.62.92.115:106/v1/weather/geo";
        $curl_data = ["tenant_id" => 1, 'lat' => '30.266705', 'lon' => '119.965092'];
        $r['weather'] = json_decode(Curl::getInstance()->post($get_url, $curl_data), true)['data']['weather'];

        $r['base'] = [ // 基础信息
            'buildingNum' => '1500', 'roomNum' => '3240', 'rentOut' => '500', 'self' => '2740', 
            'memberNum' => '6462', 'register' => '5000', 'flow' => '1462',
            'parkingNUm' => '300', 'underground' => '200', 'ground' => '100',
            'score' => '5.0', 'eventNum' => '100'
        ];
        // 人员信息
        $r['visit']['visittotal'] = 1750;
        $r['visit']['visitlist'] = [ // 访客信息
            ['name' => '访客人次', 'type' => 'bar', 'data' => [320, 332, 301, 334, 390, 334, 390]],
            ['name' => '进入人次', 'type' => 'bar', 'data' => [220, 182, 191, 234, 290, 334, 390]],
            ['name' => '出去人次', 'type' => 'bar', 'data' => [150, 232, 201, 154, 190, 334, 390]],
        ];
        $r['visit']['visittime'] = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];

        $r['visit']['member'] = [ 
            ['name' => '流动人口', 'value' => '335'],
            ['name' => '户籍人口', 'value' => '310'],
            ['name' => '境外人口', 'value' => '234'],
            ['name' => '临时人口', 'value' => '115'],
        ];
        // 车辆信息
        $r['car']['cartotal'] = 432;
        $r['car']['carpublic'] = 132;
        $r['car']['carfree'] = 100;
        $r['car']['cartime'] = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];
        $r['car']['carlist'] = [ 
            ['name' => '进入车辆', 'type' => 'bar', 'data' => [320, 332, 301, 334, 390, 334, 390]],
            ['name' => '离开车辆', 'type' => 'bar', 'data' => [220, 182, 191, 234, 290, 334, 390]],
        ];
        // 房屋信息
        $r['room']['roomlist'] = [ 
            ['name' => '出租房', 'value' => '335'],
            ['name' => '营业房', 'value' => '310'],
            ['name' => '网约房', 'value' => '234'],
            ['name' => '自住房', 'value' => '115'],
            ['name' => '自定义', 'value' => '115'],
        ];

        return $r;
    }
    
    // 大屏 实时
    public function list($p)
    {
        $community_id = '1200020193290747905';
        $r['record'] = [ // 出入记录
            ['time' => '19:00:22', 'address' => '公寓大门门禁', 'name' => '刘**', 'type' => '1']
        ];

        $r['activity'] = [ // 社区活动
            ['name' => '业主大会', 'total' => '200', 'createAt' => '2020/7/20', 'rate' => '99%']
        ];

        $r['inspect'] = [ // 巡检任务
            ['taskName' => '任务名称', 'lineName' => '线路名称', 'createAt' => '2020/7/20', 'statusMsg' => '未处理', 'content' => '已处理']
        ];

        $r['alarm'] = [ // 实时告警
            ['typeMsg' => '社区风险', 'address' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/7/20 18:34:45', 'title' => '未处理']
        ];

        $r['report'] = [ // 一键上报
            [
                ['typeMsg' => '社区风险', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/7/20 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '张三']
            ],
            [
                ['typeMsg' => '社区风险', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/7/20 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '张三']
            ],
            [
                ['typeMsg' => '社区风险', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/7/20 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '张三']
            ]
        ];
        
        // 报事报修
        $r['repair'] = PsRepair::find()->alias('A')
            ->select('B.name typeMsg, A.repair_content, A.create_at, A.status, A.operator_name, A.room_address')
            ->leftJoin('ps_repair_type B', 'A.repair_type_id = B.id')
            ->where(['A.community_id' => $community_id])
            ->orderBy('A.create_at desc')->limit(10)->asArray()->all();
        if (!empty($r['repair'])) {
            foreach ($r['repair'] as $k => &$v) {
                $v['statusMsg'] = self::$repairStatus[$v['status']];
                $v['create_at'] = date('Y/m/d H:i:s', $v['create_at']);
            }
        }

        return $r;
    }
}