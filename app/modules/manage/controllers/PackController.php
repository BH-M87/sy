<?php
namespace app\modules\manage\controllers;

use Yii;

use common\core\PsCommon;

use app\models\PsPack;

use service\manage\PackService;

class PackController extends BaseController
{
    public function actionList()
    {
        $result = PackService::service()->getList($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 添加套餐包分类
    public function actionClassifyAdd()
    {
        $valid = PsCommon::validParamArr(new PsPack(), $this->request_params, 'classify-add');

        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $result = PackService::service()->classifyAdd($this->request_params);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 套餐包分类编辑
    public function actionClassifyEdit()
    {
        if (!$this->request_params["class_id"]) {
            return PsCommon::responseFailed("分类id不能为空");
        }

        $valid = PsCommon::validParamArr(new PsPack(), $this->request_params, 'classify-add');

        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $result = PackService::service()->classifyEdit($this->request_params);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 套餐包分类查看详情
    public function actionClassifyShow()
    {
        $classId = $this->request_params["class_id"];

        if (!$this->request_params["class_id"]) {
            return PsCommon::responseFailed("分类id不能为空");
        }

        $result = PackService::service()->classifyShow($classId);

        return PsCommon::responseSuccess($result);
    }

    // 套餐包分类删除
    public function actionClassifyDelete()
    {
        $classId = $this->request_params["class_id"];

        if (!$this->request_params["class_id"]) {
            return PsCommon::responseFailed("分类id不能为空");
        }

        $result = PackService::service()->classifyDelete($classId);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 套餐包处理
    public function actionPackAdd()
    {
        if (empty($this->request_params["menus"]) || !is_array($this->request_params["menus"])) {
            return PsCommon::responseFailed("系统菜单不能为空");
        }

        $valid = PsCommon::validParamArr(new PsPack(), $this->request_params, 'pack-add');

        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $result = PackService::service()->packAdd($this->request_params);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 套餐包编辑
    public function actionPackEdit()
    {
        if (!$this->request_params["pack_id"]) {
            return PsCommon::responseFailed("套餐id不能为空");
        }

        if (empty($this->request_params["menus"]) || !is_array($this->request_params["menus"])) {
            return PsCommon::responseFailed("系统菜单不能为空");
        }

        $valid = PsCommon::validParamArr(new PsPack(), $this->request_params, 'pack-add');

        if (!$valid["status"]) {
            return PsCommon::responseFailed($valid["errorMsg"]);
        }

        $result = PackService::service()->packEdit($this->request_params);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 套餐包删除
    public function actionPackDelete()
    {
        $packId = $this->request_params["pack_id"];

        if (!$this->request_params["pack_id"]) {
            return PsCommon::responseFailed("套餐id不能为空");
        }

        $result = PackService::service()->packDelete($packId);

        if ($result["code"]) {
            return PsCommon::responseSuccess();
        } else {
            return PsCommon::responseFailed($result["msg"]);
        }
    }

    // 套餐包分类查看详情
    public function actionPackShow()
    {
        $classId = $this->request_params["pack_id"];

        if (!$classId) {
            return PsCommon::responseFailed("套餐包id不能为空");
        }

        $result = PackService::service()->packShow($classId);

        return PsCommon::responseSuccess($result);
    }

    public function actionGetClassify()
    {
        $result = PackService::service()->getClassify();

        return PsCommon::responseSuccess($result);
    }

    public function actionGetComm()
    {
        $result = PsCommon::returnKeyValue(PackService::$_Type);

        return PsCommon::responseSuccess($result);
    }

    // 获取套餐包所有按钮
    public function actionGetPackMenu()
    {
        $packId = $this->request_params["pack_id"];

        if (!$packId) {
            return PsCommon::responseFailed("不能为空");
        }

        $result = PackService::service()->getPackMenu($packId, 1);

        return PsCommon::responseSuccess($result);
    }

    // 获取系统下所有菜单和按钮
    public function actionGetSystemMenu()
    {
        $systemType = $this->request_params["type"];

        if (!$systemType) {
            $systemType = 1;
        }

        $result = PackService::service()->getSystemMenu($systemType);
        
        return PsCommon::responseSuccess($result);
    }
}