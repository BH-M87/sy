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
use service\inspect\RecordService;

use app\models\PsRepair;
use app\models\PsInspectRecord;
use app\models\PsCommunityComment;
use app\models\PsCommunityCommentDetail;

use service\property_basic\JavaNewService;

class ScreenService extends BaseService
{
    public static $repairStatus = ['1' => '已接单', '2' => '开始处理', '3' => '已完成', '6' => '已关闭', '7' => '待处理'];
    // 统计报表
    public function report($p)
    {
        //$community_id = '1284053287097896961';
        $community_id = $p['community_id'];

        $r['repair']['repairTotal'] = PsRepair::find()->where(['community_id' => $p['community_id']])->count();
        $r['repair']['finishTotal'] = PsRepair::find()->where(['community_id' => $p['community_id'], 'status' => 3])->count();
        $r['repair']['hardTotal'] = PsRepair::find()->where(['community_id' => $p['community_id'], 'hard_type' => 2])->count();

        $person = JavaNewService::service()->javaPost('/sy/board/statistics/personBoard',['communityId' => $community_id])['data'];

        $r['people']['total'] = $person['totalPersonCount'] ?? 0;
        $r['people']['visit'] = $person['visitorCount'] ?? 0;
        $r['people']['peopleList'] = $person['list'];

        $car = JavaNewService::service()->javaPost('/sy/board/statistics/carShopBoard',['communityId' => $community_id])['data'];

        $weekarray=array("日","一","二","三","四","五","六"); //先定义一个数组
        if (empty($car['carTime'])) {
            $car['carTime'] = [
                "星期".$weekarray[date("w", time())],
                "星期".$weekarray[date("w", time()-24*3600)],
                "星期".$weekarray[date("w", time()-2*24*3600)],
                "星期".$weekarray[date("w", time()-3*24*3600)],
                "星期".$weekarray[date("w", time()-4*24*3600)],
                "星期".$weekarray[date("w", time()-5*24*3600)],
                "星期".$weekarray[date("w", time()-6*24*3600)],
            ];
        }

        $r['car']['carIn'] = $car['carIn'] ?? 0;
        $r['car']['carOut'] = $car['carOut'] ?? 0;
        $r['car']['carTime'] = $car['carTime'];
        $r['car']['carList'] = [
            ['name' => '入场', 'type' => 'line', 'stack' => '总量', 'data' => $car['carList'][0]['value'] ?? [0,0,0,0,0,0,0]],
            ['name' => '出场', 'type' => 'line', 'stack' => '总量', 'data' => $car['carList'][1]['value'] ?? [0,0,0,0,0,0,0]],
        ];

        $device = JavaNewService::service()->javaPost('/sy/board/statistics/deviceBoard',['communityId' => $community_id])['data'];
   
        if ($device['deviceList']) {
            foreach ($device['deviceList'] as $k => $v) {
                switch ($v['name']) {
                    case '智能井盖':
                        $wellCover = $v['value'];
                        break;
                    case '环境监测':
                        $environment = $v['value'];
                        break;
                    case '给排水':
                        $water = $v['value'];
                        break;
                    case '充电桩':
                        $charge = $v['value'];
                        break;
                    case '智能电梯':
                        $lift = $v['value'];
                        break;
                    case '智能消防':
                        $fire = $v['value'];
                        break;
                    case '视频监控':
                        $video = $v['value'];
                        break;
                    case '人行门禁':
                        $people = $v['value'];
                        break;
                    case '停车道闸':
                        $stop = $v['value'];
                        break;
                    case '智能垃圾桶':
                        $trash = $v['value'];
                        break;
                    default:
                        break;
                }
            }
        }

        $r['device'] = [
            'doorTotal' => $device['accessControlCount'],
            'stopTotal' => $device['gateCount'],
            'wellCover' => $wellCover ?? 0,
            'environment' => $environment ?? 0,
            'water' => $water ?? 0,
            'charge' => $charge ?? 0,
            'fire' => $fire ?? 0,
            'video' => $video ?? 0,
            'people' => $people ?? 0,
            'stop' => $stop ?? 0,
            'lift' => $lift ?? 0,
            'trash' => $trash ?? 0,
        ];

        return $r;
    }

    // 服务评价
    public function comment($p)
    {
        $arr = explode('/', $p['month']);
        $comment_year = $arr['0'];
        $comment_month = $arr['1'];

        $detail = PsCommunityCommentDetail::find()->select('count(id) c, score')
            ->filterWhere(['community_id' => $p['community_id']])
            ->andFilterWhere(['=', 'comment_year', $comment_year])
            ->andFilterWhere(['=', 'comment_month', $comment_month])
            ->groupBy('score')->asArray()->all();
        $data = [0,0,0,0,0];
        if (!empty($detail)) {
            foreach ($detail as $k => $v) {
                switch ($v['score']) {
                    case '1.0':
                        $data[0] = (int)$v['c'];
                        break;
                    case '2.0':
                        $data[1] = (int)$v['c'];
                        break;
                    case '3.0':
                        $data[2] = (int)$v['c'];
                        break;
                    case '4.0':
                        $data[3] = (int)$v['c'];
                        break;
                    case '5.0':
                        $data[4] = (int)$v['c'];
                        break;
                    default:
                        break;
                }
            }
        }

        $r = PsCommunityComment::find()->select('score avg, total')
            ->filterWhere(['community_id' => $p['community_id']])
            ->andFilterWhere(['=', 'comment_year', $comment_year])
            ->andFilterWhere(['=', 'comment_month', $comment_month])->asArray()->one();

        $r['avg'] = $r['avg'] ?? "0";
        $r['total'] = $r['total'] ?? "0";

        $r['commentList'] = [ // 服务评价
            ['name' => '服务评价', 'type' => 'bar', 'barWidth' => 20, 'data' => $data],
        ];
        
        return $r;
    }

    // 大屏
    public function index($p)
    {   
        $community_id = '1290165028708810753';

        $get_url = "116.62.92.115:106/v1/weather/geo";
        $curl_data = ["tenant_id" => 1, 'lat' => '30.266705', 'lon' => '119.965092'];
        $r['weather'] = json_decode(Curl::getInstance()->post($get_url, $curl_data), true)['data']['weather'];
        
        $repairCount = PsRepair::find()
            ->where(['>=', 'create_at', strtotime(date('Y-m-d', time()))])
            ->andWhere(['<', 'create_at', strtotime(date('Y-m-d', time()))+86400])->count();

        $inspectCount = PsInspectRecord::find()
            ->where(['>=', 'create_at', strtotime(date('Y-m-d', time()))])
            ->andWhere(['<', 'create_at', strtotime(date('Y-m-d', time()))+86400])->count();

        $base = JavaNewService::service()->javaPost('/sy/board/statistics/corpBoard',['communityId' => $community_id])['data'];
        $r['base'] = [ // 基础信息
            'buildingNum' => '11',//$base['buildingCount'], 
            'roomNum' => '1540',//$base['roomInfoVO']['roomCount'] ?? 0, 
            'rentOut' => '307',//$base['roomInfoVO']['leaseCount'] ?? 0, 
            'self' => '1233',//$base['roomInfoVO']['localCount'] ?? 0, 
            'memberNum' => $base['residentInfoVO']['residentCount'] ?? 0, 
            'register' => $base['residentInfoVO']['householdCount'] ?? 0, 
            'flow' => $base['residentInfoVO']['floatingCount'] ?? 0,
            'parkingNUm' => '1592', 
            'underground' => '1479', 
            'ground' => '113',
            'score' => '5.0', 
            'eventNum' => $repairCount+$inspectCount ?? 0
        ]; // $base['eventCount']

        // 健康码
        $r['healthy']['healthyTotalMobile'] = 457; // 手机扫码进入人次
        $r['healthy']['healthyTotal'] = 345; // 手环扫码进入人次

        // 共享积分 小区积分排名、积分总数、积分参与人数、积分参与人数比例
        $r['integral']['integralRank'] = 1;
        $r['integral']['integralTotal'] = 21309;
        $r['integral']['integralUser'] = 3657;
        $r['integral']['integralRate'] = '78%';

        // 设备
        $r['device']['deviceTotal_1'] = 5;
        $r['device']['deviceTotal_2'] = 4;
        
        $person = JavaNewService::service()->javaPost('/sy/board/statistics/personBoard',['communityId' => $community_id])['data'];
        // 人员信息
        $r['visit']['visittotal'] = 1750;
        $r['visit']['visitlist'] = [ // 访客信息
            ['name' => '访客人次', 'type' => 'bar', 'data' => [320, 332, 301, 334, 390, 334, 390]],
            ['name' => '进入人次', 'type' => 'bar', 'data' => [220, 182, 191, 234, 290, 334, 390]],
            ['name' => '出去人次', 'type' => 'bar', 'data' => [150, 232, 201, 154, 190, 334, 390]],
        ];
        $r['visit']['visittime'] = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];

        $person[1]['name'] = '户籍人口';
        $r['visit']['member'] = $person;
        // 车辆信息
        $r['car']['cartotal'] = 432;
        $r['car']['carpublic'] = 132;
        $r['car']['carfree'] = 100;
        $r['car']['cartime'] = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];
        $r['car']['carlist'] = [ 
            ['name' => '进入车辆', 'type' => 'bar', 'data' => [320, 332, 301, 334, 390, 334, 390]],
            ['name' => '离开车辆', 'type' => 'bar', 'data' => [220, 182, 191, 234, 290, 334, 390]],
        ];
        $r['car']['carInOut'] = [ 
            ['carIn' => '浙A780C8', 'timeIn' => '8-13 19:22:12', 'carOut' => '浙B12CC4', 'timeOut' => '8-13 19:23:18'],
            ['carIn' => '浙A123BB', 'timeIn' => '8-13 19:14:13', 'carOut' => '沪C1SE14', 'timeOut' => '8-13 19:22:42'],
            ['carIn' => '浙K344D3', 'timeIn' => '8-13 19:13:14', 'carOut' => '浙A12SB4', 'timeOut' => '8-13 19:12:32'],
        ];
        // 房屋信息
        $room = JavaNewService::service()->javaPost('/sy/board/statistics/roomTagBoard',['communityId' => $community_id])['data'];
        $r['room']['roomlist'] = $room;

        return $r;
    }
    
    // 大屏 实时
    public function list($p)
    {
        $community_id = '1290165028708810753';

        $r['record'] = [ // 出入记录
            ['time' => '19:20:22', 'address' => '公寓大门门禁', 'name' => '刘**', 'type' => '1'],
            ['time' => '19:19:12', 'address' => '公寓大门门禁', 'name' => '吴**', 'type' => '2'],
            ['time' => '19:17:32', 'address' => '公寓大门门禁', 'name' => '张**', 'type' => '1'],
            ['time' => '19:14:28', 'address' => '公寓大门门禁', 'name' => '李**', 'type' => '2'],
            ['time' => '19:07:29', 'address' => '公寓大门门禁', 'name' => '王**', 'type' => '2'],
        ];
          
        $activity = JavaNewService::service()->javaPost('/sy/board/statistics/activityPage',['communityId' => $community_id, 'pageNum' => 1, 'pageSize' => 10])['data'];
        $r['activity'] = $activity['list'] ?? []; // 社区活动

        $r['alarm'] = [ // 实时告警
            ['typeMsg' => '垃圾报警', 'address' => '8幢2单元1楼', 'createAt' => '2020/08/17 15:34:45', 'title' => '检测到垃圾堆放'],
            ['typeMsg' => '人脸报警', 'address' => '5幢1单元1楼', 'createAt' => '2020/08/10 19:34:45', 'title' => '重点区域有人非法入侵'],
            ['typeMsg' => '门禁报警', 'address' => '3幢3单元12楼', 'createAt' => '2020/08/09 18:54:45', 'title' => '门禁报警'],
            ['typeMsg' => '人脸报警', 'address' => '展厅二楼', 'createAt' => '2020/08/01 16:34:45', 'title' => '重点区域有人非法入侵'],
            ['typeMsg' => '烟雾报警', 'address' => '7幢1单元', 'createAt' => '2020/7/20 18:34:45', 'title' => '烟雾报警'],
        ];

        $r['report'] = [ // 一键上报
            [
                ['typeMsg' => '消防安全', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/08/13 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '张一三'],
                ['typeMsg' => '消防安全', 'title' => '车辆占用消防通道', 'createAt' => '2020/08/12 18:34:45', 'statusMsg' => '已处理', 'operatorName' => '李大致'],
                ['typeMsg' => '消防安全', 'title' => '8幢2单元入口玻璃破裂', 'createAt' => '2020/08/11 18:34:45', 'statusMsg' => '已处理', 'operatorName' => '王麻子'],
            ],
            [
                ['typeMsg' => '消防安全', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/08/10 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '王麻子'],
                ['typeMsg' => '消防安全', 'title' => '车辆占用消防通道', 'createAt' => '2020/08/09 18:34:45', 'statusMsg' => '已处理', 'operatorName' => '王麻子'],
                ['typeMsg' => '消防安全', 'title' => '8幢2单元入口玻璃破裂', 'createAt' => '2020/08/08 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '李大致'],
            ],
            [
                ['typeMsg' => '消防安全', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/08/07 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '李大致'],
                ['typeMsg' => '消防安全', 'title' => '车辆占用消防通道', 'createAt' => '2020/08/06 18:34:45', 'statusMsg' => '已处理', 'operatorName' => '王麻子'],
                ['typeMsg' => '消防安全', 'title' => '8幢2单元入口玻璃破裂', 'createAt' => '2020/08/01 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '张一三'],
            ]
        ];

        $r['inspect'] = RecordService::service()->recordList(['page' => 1, 'pageSize' => 10, 'community_id' => $community_id]);

        // 报事报修
        $repair = PsRepair::find()->alias('A')
            ->select('B.name typeMsg, A.repair_content, A.create_at, A.status, A.operator_name, A.room_address, A.status')
            ->leftJoin('ps_repair_type B', 'A.repair_type_id = B.id')
            ->where(['A.community_id' => $community_id])
            ->orderBy('A.create_at desc')->limit(9)->asArray()->all();
        if (!empty($repair)) {
            foreach ($repair as $k => &$v) {
                if ($v['status'] == 7) {
                    $v['operator_name'] = '';
                }
                $v['statusMsg'] = self::$repairStatus[$v['status']];
                $v['create_at'] = date('Y/m/d H:i:s', $v['create_at']);
            }
        }

        $r['repair'] = self::partition($repair, 3);

        return $r;
    }

    // 大屏 中间 告警
    public function center($p)
    {
        $arr = ['检测到垃圾堆放', '检测到垃圾堆放', '检测到垃圾堆放', '车辆占用消防通道'];

        $key = array_rand($arr);
            
        $start = 5;
        if ($key == 0 || $key == 2) {
            $start = 5;
        }

        $r['list'] = [ // 实时告警
            ['id' => time(), 'title' => $arr[$key], 'start' => $start]
        ];

        return $r;
    }

    // 把一个数组分成几个数组 $arr 是数组 $num 是数组的个数
    function partition($arr, $num)
    {
        if (!empty($arr)) {
            $listcount = count($arr); // 数组的个数
            
            switch ($listcount / 3) {
                case '0':
                    $num = 1;
                    break;
                case '1':
                    $num = 2;
                    break;
                case '2':
                    $num = 3;
                    break;
                default:
                    $num = 1;
                    break;
            }

            $parem = floor($listcount / $num); // 分成$num 个数组每个数组是多少个元素
     
            $paremm = $listcount % $num; // 分成$num 个数组还余多少个元素
            $start = 0;
            for ($i = 0; $i < $num; $i++) {
                $end = $i < $paremm ? $parem + 1 : $parem;
                $newarray[$i] = array_slice($arr, $start, $end);
                $start = $start + $end;
            }
            return $newarray;
        }
        return [];
    }
}
