<?php
/**
 * 党员管理相关接口
 * User: wenchao.feng
 * Date: 2019/9/4
 * Time: 18:19
 */
namespace app\modules\street\modules\v1\controllers;

use app\models\StCommunist;
use common\core\F;
use common\core\PsCommon;
use service\street\CommunistService;

class CommunistController extends BaseController
{
    //新增
    public function actionAdd()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $this->request_params['birth_time_date'] =  F::value($this->request_params, 'birth_time', '');
        $this->request_params['join_party_time_date'] =  F::value($this->request_params, 'join_party_time', '');
        $this->request_params['formal_time_date'] =  F::value($this->request_params, 'formal_time', '');
        $this->request_params['birth_time'] = $this->request_params['birth_time_date'] ? strtotime($this->request_params['birth_time_date']) : 0;
        $this->request_params['join_party_time'] = $this->request_params['join_party_time_date'] ? strtotime($this->request_params['join_party_time_date']) : 0;
        $this->request_params['formal_time'] = $this->request_params['formal_time_date'] ? strtotime($this->request_params['formal_time_date']) : 0;

        $valid = PsCommon::validParamArr(new StCommunist(), $this->request_params, 'add');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = CommunistService::service()->add($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("新增失败");
        }
    }

    //编辑
    public function actionEdit()
    {
        $this->request_params['birth_time_date'] =  F::value($this->request_params, 'birth_time', '');
        $this->request_params['join_party_time_date'] =  F::value($this->request_params, 'join_party_time', '');
        $this->request_params['formal_time_date'] =  F::value($this->request_params, 'formal_time', '');
        $this->request_params['birth_time'] = $this->request_params['birth_time_date'] ? strtotime($this->request_params['birth_time_date']) : 0;
        $this->request_params['join_party_time'] = $this->request_params['join_party_time_date'] ? strtotime($this->request_params['join_party_time_date']) : 0;
        $this->request_params['formal_time'] = $this->request_params['formal_time_date'] ? strtotime($this->request_params['formal_time_date']) : 0;
        $valid = PsCommon::validParamArr(new StCommunist(), $this->request_params, 'edit');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = CommunistService::service()->edit($this->request_params, $this->user_info);
        if($result) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed("编辑失败");
        }
    }

    public function actionView()
    {
        $valid = PsCommon::validParamArr(new StCommunist(), $this->request_params, 'view');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = CommunistService::service()->view($this->request_params);
        return PsCommon::responseSuccess($result);
    }

    public function actionDelete()
    {
        $valid = PsCommon::validParamArr(new StCommunist(), $this->request_params, 'delete');
        if(!$valid["status"] ) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }
        $result = CommunistService::service()->delete($this->request_params);
        if ($result === true) {
            return PsCommon::responseSuccess($result);
        }
    }

    public function actionList()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        $result = CommunistService::service()->getList($this->page,$this->pageSize,$this->request_params);
        return PsCommon::responseSuccess($result);
    }

    //导入模板下载
    public function actionGetDown()
    {
        $downUrl = F::downloadUrl('import_communist_templates.xlsx', 'template', 'CommunistMuBan.xlsx');
        return PsCommon::responseSuccess(['down_url' => $downUrl]);
    }

    public function actionImport()
    {
        $this->request_params['organization_type'] = $this->user_info['node_type'];
        $this->request_params['organization_id'] = $this->user_info['dept_id'];
        if (empty($_FILES['file'])) {
            return PsCommon::responseFailed('未接收到有效文件');
        }
        $re = CommunistService::service()->import($this->request_params, $_FILES['file'], $this->user_info);
        if ($re['code']) {
            return PsCommon::responseSuccess($re['data']);
        }
        return PsCommon::responseFailed($re['msg']);
    }

    public function actionGetCommon()
    {
        $result = CommunistService::service()->getCommon();
        return PsCommon::responseSuccess($result);
    }
}