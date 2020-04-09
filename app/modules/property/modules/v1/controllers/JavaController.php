<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/11/27
 * Time: 14:49
 * Desc: 调用java接口
 */
namespace app\modules\property\modules\v1\controllers;

use service\property_basic\JavaService;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;

use yii\base\Exception;

class JavaController extends BaseController{


    /*
     * 小区下拉
     */
    public function actionCommunityNameList(){
        try{

            $data = $this->request_params;
            $result = JavaService::service()->communityNameList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
     * 苑期区名称下拉
     */
    public function actionGroupNameList(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->groupNameList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
     * 楼栋名称下拉
     */
    public function actionBuildingNameList(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->buildingNameList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
     * 单元名称下拉
     */
    public function actionUnitNameList(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->unitNameList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
     *房屋名称 下拉
     */
    public function actionRoomNameList(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->roomNameList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
     *住户身份下拉
     */
    public function actionMemberTypeEnum(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->memberTypeEnum($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
     * 住户列表
     */
    public function actionResidentList(){
        try{
            $data = $this->request_params;
            $data['pageNum'] = !empty($data['page'])?$data['page']:'';
            $data['pageSize'] = !empty($data['rows'])?$data['rows']:'';
            $result = JavaService::service()->residentList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
      * 部门列表
      */
    public function actionTreeList(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->treeList($data);

            array_unshift($result['children'], ['id' => '', 'name' => '全部', 'children' => []]);
            
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
     * 员工列表
     */
    public function actionUserList(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->userList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }


    /*
     * 房屋详情
     */
    public function actionRoomInfo(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->roomDetail($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /*
     * 获得小区下所有房屋
     */
    public function actionUnitTree(){
        try{
            $data = $this->request_params;
            if(empty($data['id'])){
                return PsCommon::responseFailed('小区id必填');
            }
            $result = JavaService::service()->unitTree($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    //获取部门下用户
    public function actionListUserUnderDept(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->listUserUnderDept($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    //获取部门下绑定钉钉用户
    public function actionBindUserList(){
        try{
            $data = $this->request_params;
            $result = JavaService::service()->bindUserList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}
