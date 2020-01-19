<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/1/19
 * Time: 10:07
 * Desc: 巡检任务service
 */
namespace service\inspect;

use app\models\PsInspectRecord;
use service\property_basic\CommonService;

class RecordService extends BaseService {

    //任务状态
    public $status = [
        ['key'=>1,'name'=>'待巡检'],
        ['key'=>2,'name'=>'巡检中'],
        ['key'=>3,'name'=>'已完成'],
        ['key'=>4,'name'=>'已关闭'],
    ];

    //任务执行状态
    public $runStatus = [
        ['key'=>1,'name'=>'逾期'],
        ['key'=>2,'name'=>'旷巡'],
        ['key'=>3,'name'=>'正常'],
    ];

    //巡检列表
    public function recordList($params){
        //获得所有小区id
        $commonService = new CommonService();
        $javaParams['token'] = $params['token'];
        $communityInfo = $commonService->getCommunityInfo($javaParams);
        $params['communityIds'] = $communityInfo['communityIds'];
        $model = new PsInspectRecord();
        print_r($params);die;
    }

    //任务状态下拉
    public function statusDrop(){
        return ['list'=>$this->status];
    }

    //任务执行状态下拉
    public function runStatusDrop(){
        return ['list'=>$this->runStatus];
    }
}