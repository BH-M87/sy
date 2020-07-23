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

use service\property_basic\JavaOfCService;

class ScreenService extends BaseService
{
    // 大屏
    public function index($p)
    {
        $r['base'] = [ // 基础信息
            'buildingNum' => '1500', 'roomNum' => '3240', 'rentOut' => '500', 'self' => '2740', 
            'memberNum' => '6462', 'register' => '5000', 'flow' => '1462',
            'parkingNUm' => '300', 'underground' => '200', 'ground' => '100',
            'score' => '5.0', 'eventNum' => '100'
        ];

        return $r;
    }
    
    // 大屏 实时
    public function list($p)
    {
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

        $r['repair'] = [ // 报事报修
            [
                ['typeMsg' => '社区风险', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/7/20 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '张三', 'address' => '南区8-2-101']
            ],
            [
                ['typeMsg' => '社区风险', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/7/20 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '张三', 'address' => '南区8-2-101']
            ],
            [
                ['typeMsg' => '社区风险', 'title' => '禁烟时段燃放烟花爆竹', 'createAt' => '2020/7/20 18:34:45', 'statusMsg' => '未处理', 'operatorName' => '张三', 'address' => '南区8-2-101']
            ]
        ];

        return $r;
    }
}