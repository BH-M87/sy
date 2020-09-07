<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 11:08
 * Desc: 投票活动
 */
namespace app\modules\operation\modules\v1\controllers;

use app\modules\operation\controllers\BaseController;
use common\core\F;
use service\common\ExcelService;
use service\vote\ActivityService;
use yii\base\Exception;
use common\core\PsCommon;
use Yii;

class ActivityController extends BaseController {

    public $repeatAction = ['add','edit','add-player','edit-player','export-player-data'];

    //新建活动
    public function actionAdd(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->add($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //新建活动
    public function actionEdit(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->edit($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动列表
    public function actionList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new ActivityService();
            $result = $service->getList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动详情
    public function actionDetail(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->getDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动下拉
    public function actionDropOfActivity(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->dropOfActivity($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动中组下拉
    public function actionDropOfGroup(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->dropOfGroup($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //添加选手
    public function actionAddPlayer(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->addPlayer($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //编辑选手
    public function actionEditPlayer(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->editPlayer($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //选手详情
    public function actionPlayerDetail(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->playerDetail($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //删除选手
    public function actionDelPlayer(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->delPlay($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //选手列表
    public function actionPlayerList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new ActivityService();
            $result = $service->playerList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //数据列表
    public function actionPlayerDataList(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new ActivityService();
            $result = $service->playerList($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //投票记录
    public function actionVoteRecord(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new ActivityService();
            $result = $service->voteRecord($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //选手评论列表
    public function actionCommentRecord(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new ActivityService();
            $result = $service->commentRecord($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //活动反馈列表
    public function actionFeedbackRecord(){
        try{
            $params = $this->request_params;
            $params['page'] = $this->page;
            $params['pageSize'] = $this->pageSize;
            $service = new ActivityService();
            $result = $service->feedbackRecord($params);
            if ($result['code']) {
                return PsCommon::responseSuccess($result['data']);
            } else {
                return PsCommon::responseFailed($result['msg']);
            }

        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }

    //数据导出
    public function actionExportPlayerData(){
        try{
            $params = $this->request_params;
            $service = new ActivityService();
            $result = $service->playerList($params);
            if ($result['code']) {
                $getTotals = $result['data']['totals'];
                if ($getTotals > 0) {

                    $cycle = ceil($getTotals / 1000);
                    $config["sheet_config"] = [
                        'A' => ['title' => '选手编号', 'width' => 20, 'data_type' => 'str', 'field' => 'code'],
                        'B' => ['title' => '选手名称', 'width' => 25, 'data_type' => 'str', 'field' => 'name'],
                        'C' => ['title' => '分组', 'width' => 15, 'data_type' => 'str', 'field' => 'group_name'],
                        'D' => ['title' => '浏览量', 'width' => 10, 'data_type' => 'str', 'field' => 'view_num'],
                        'E' => ['title' => '投票量', 'width' => 10, 'data_type' => 'str', 'field' => 'vote_num'],
                    ];
                    $config["save"] = true;
                    $date = date('Y-m-d',time());
                    $savePath = Yii::$app->basePath . '/web/store/zip/vote/' . $date . '/';
                    $config["save_path"] = $savePath;
                    //房屋数量查过一千则导出压缩文件
                    if ($cycle == 1) {//下载单个文件
                        $config["file_name"] = "MuBan1".F::generateName("xlsx");
                        $params['page'] = 1;
                        $params['pageSize'] = 1000;
                        $result = $service->playerList($params);
                        $file_name = ExcelService::service()->recordDown($result['data']['list'], $config);
                        //$downUrl = F::downloadUrl('vote/' . $date . '/'. $file_name, 'zip');
                        $downUrl = F::uploadExcelToOss($file_name, $savePath);
                        return PsCommon::responseSuccess(['down_url' => $downUrl]);
                    } else {//下载zip压缩包
                        for ($i = 1; $i <= $cycle; $i++) {
                            $config["file_name"] = "MuBan" . $i . ".xlsx";
                            $params['page'] = $i;
                            $params['pageSize'] = 1000;
                            $result = $service->playerList($params);
                            $config["file_name"] = "MuBan" . $i . ".xlsx";
                            ExcelService::service()->recordDown($result['data']['list'], $config);
                        }
                        $fileName = "vote".F::generateName('zip');
                        $path = $savePath . $fileName;
                        ExcelService::service()->addZip($savePath, $path);
                        //$downUrl = F::downloadUrl('vote/'.$date.'/vote.zip', 'zip');
                        $downUrl = F::uploadExcelToOss($fileName, $savePath);
                        return PsCommon::responseSuccess(['down_url' => $downUrl]);
                    }
                } else {
                    return PsCommon::responseFailed("暂无数据！");
                }
            } else {
                return PsCommon::responseFailed($result['msg']);
            }
        }catch(Exception $e){
            return PsCommon::responseFailed($e->getMessage());
        }
    }
}