<?php
/**
 * 供应商相关操作
 * User: wenchao.feng
 * Date: 2019/1/31
 * Time: 11:19
 */
namespace backend\controllers;
use backend\models\IotSupplierCommunity;
use backend\models\IotSuppliers;
use backend\models\PsCommunityModel;
use service\basic_data\IotNewService;
use service\basic_data\PushConfigService;
use service\basic_data\SupplierService;
use yii\web\Controller;
use yii\data\ActiveDataProvider;

class SupplierController extends Controller
{
    public $layout = "main";
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $model = new IotSuppliers();
        $dataProvider = new ActiveDataProvider([
            'query' => IotSuppliers::find()->orderBy('id desc'),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }
    //更新供应商
    public function actionUpdate()
    {
        if (empty($_POST['id'])) {
            $id = !empty($_GET['id']) ? $_GET['id'] : 0;
            if (!$id) {
                die("供应商id不能为空！");
            }

            
            $model = IotSuppliers::find()->where(['id' => $id])->asArray()->one();
            return $this->render('update', [
                'model' => $model
            ]);
        } else {
            $id = !empty($_POST['id']) ? $_POST['id'] : 0;
            $name = !empty($_POST['name']) ? $_POST['name'] : '';
            $contactor = !empty($_POST['contactor']) ? $_POST['contactor'] : '';
            $mobile = !empty($_POST['mobile']) ? $_POST['mobile'] : '';

            if (!$id) {
                die("供应商id不能为空！");
            }

            if (!$name) {
                die("供应商名称不能为空！");
            }

            if (!$contactor) {
                die("供应商联系人不能为空！");
            }

            if (!$mobile) {
                die("供应商联系人电话不能为空！");
            }
            $model = IotSuppliers::findOne($id);
            $model->name = $name;
            $model->contactor = $contactor;
            $model->mobile = $mobile;
            if ($model->save()) {
                return $this->redirect(['/supplier']);
            } else {
                die("保存失败！");
            }
        }

    }

    //删除供应商
    public function actionDelete()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            die("供应商id不能为空！");
        }
        $model = IotSuppliers::findOne($id);
        if (!$model) {
            die("供应商不存在！");
        }
        if ($model->delete()) {
            return $this->redirect(['/supplier']);
        } else {
            die("删除失败！");
        }
    }

    //添加供应商
    public function actionAdd()
    {
        if (!empty($_POST)) {
            $name = !empty($_POST['name']) ? $_POST['name'] : '';
            $contactor = !empty($_POST['contactor']) ? $_POST['contactor'] : '';
            $mobile = !empty($_POST['mobile']) ? $_POST['mobile'] : '';
            $type = !empty($_POST['type']) ? $_POST['type'] : '';
            $supplier_name = !empty($_POST['type']) ? $_POST['supplier_name'] : '';

            if (!$name) {
                die("供应商名称不能为空！");
            }
            if (!$contactor) {
                die("供应商联系人不能为空！");
            }
            if (!$mobile) {
                die("供应商联系人电话不能为空！");
            }
            if (!$type) {
                die("供应商类型不能为空！");
            }
            if (!$supplier_name) {
                die("供应商标识不能为空！");
            }
            $model = new IotSuppliers();
            $model->name = $name;
            $model->contactor = $contactor;
            $model->mobile = $mobile;
            $model->type = $type;
            $model->supplier_name = $supplier_name;
            $model->created_at = time();
            if ($model->save()) {
                return $this->redirect(['/supplier']);
            } else {
                die("保存失败！");
            }
        } else {
            return $this->render('add');
        }
    }

    //入驻小区管理列表
    public function actionCommunitys()
    {
        $communityName = !empty($_POST['community_name']) ? $_POST['community_name'] : '';
        $supplierName = !empty($_POST['supplier_name']) ? $_POST['supplier_name'] : '';
        $authCode = !empty($_POST['auth_code']) ? $_POST['auth_code'] : '';
        $supplierType = !empty($_POST['supplier_type']) ? $_POST['supplier_type'] : '';
        $query = IotSupplierCommunity::find()
            ->select('pc.id, pc.auth_code, pc.auth_at, pc.interface_type, pc.supplier_type,pc.created_at,
            comm.name as community_name,ps.name as supplier_name')
            ->alias('pc')
            ->leftJoin('ps_community comm','pc.community_id = comm.id')
            ->leftJoin('iot_suppliers ps', 'pc.supplier_id = ps.id')
            ->where("1=1");
        if ($communityName) {
            $query->andWhere(['like', 'comm.name', $communityName]);
        }
        if ($supplierName) {
            $query->andWhere(['like', 'ps.name', $supplierName]);
        }
        if ($authCode) {
            $query->andWhere(['pc.auth_code' => $authCode]);
        }
        if ($supplierType) {
            $query->andWhere(['pc.supplier_type' => $supplierType]);
        }
        $query->orderBy('pc.id desc');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        $model = new IotSupplierCommunity();

        //查询所有供应商
        $suppliers = IotSuppliers::find()
            ->select(['name'])
            ->orderBy('id desc')
            ->asArray()
            ->column();
        //查询所有小区
        $communitys = PsCommunityModel::find()
            ->select(['name'])
            ->where(['status' => 1])
            ->orderBy('id desc')
            ->asArray()
            ->column();
        return $this->render('communitys', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'suppliers' => $suppliers,
            'communitys' => $communitys,
            'communityName' => $communityName,
            'supplierName' => $supplierName,
            'authCode' => $authCode,
            'supplierType' => $supplierType,
        ]);
    }

    //添加小区
    public function actionCommunityAdd()
    {
        if (!empty($_POST)) {
            $req['community_id'] = !empty($_POST['community_name']) ? $_POST['community_name'] : '';
            $req['supplier_id'] = !empty($_POST['supplier_name']) ? $_POST['supplier_name'] : '';
            $req['supplier_type'] = !empty($_POST['supplier_type']) ? $_POST['supplier_type'] : '';
            $req['interface_type'] = !empty($_POST['interface_type']) ? $_POST['interface_type'] : '';
            $req['open_alipay_parking'] = !empty($_POST['open_alipay_parking']) ? $_POST['open_alipay_parking'] : '';

            //查询是否存在
            $model = IotSupplierCommunity::find()
                ->where(['supplier_id' => $req['supplier_id'], 'community_id' => $req['community_id'],
                    'supplier_type' => $req['supplier_type']])
                ->asArray()
                ->one();
            if ($model) {
                die("此小区已开通");
            }

            $result = SupplierService::service()->bindCommunity($req);
            if ($result["code"]) {
                return $this->redirect(['/supplier/communitys']);
            } else {
                die("保存失败！");
            }
        } else {
            //查询所有供应商
            $suppliers = IotSuppliers::find()
                ->select(['name', 'id'])
                ->orderBy('id desc')
                ->asArray()
                ->all();
            //查询所有小区
            $communitys = PsCommunityModel::find()
                ->select(['name', 'id'])
                ->where(['status' => 1])
                ->orderBy('id desc')
                ->asArray()
                ->all();

            return $this->render('community-add', [
                'suppliers' => $suppliers,
                'communitys' => $communitys,
            ]);
        }
    }

    //删除小区
    public function actionCommunityDelete()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            die("供应商id不能为空！");
        }
        $model = IotSupplierCommunity::findOne($id);
        if (!$model) {
            die("小区开通记录不存在！");
        }
        if ($model->delete()) {
            return $this->redirect(['/supplier/communitys']);
        } else {
            die("删除失败！");
        }
    }

    //小区初始化
    public function actionCommunityInit()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            die("供应商id不能为空！");
        }
        $model = IotSupplierCommunity::findOne($id);
        if (!$model) {
            die("小区开通记录不存在！");
        }

        $data['supplier_id'] = $model->supplier_id;
        $data['community_id'] = $model->community_id;
        $result = SupplierService::service()->commDataInit($data);
        if ($result["code"]) {
            echo "<script>alert('同步成功');window.location.href='/supplier/communitys';</script>";
        } else {
            die("同步失败！");
        }
    }

    //楼宇初始化
    public function actionBuildInit()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            die("供应商id不能为空！");
        }
        $model = IotSupplierCommunity::findOne($id);
        if (!$model) {
            die("小区开通记录不存在！");
        }
        $result = SupplierService::service()->buildDataInit($model->community_id);
        if ($result["code"]) {
            echo "<script>alert('同步成功');window.location.href='/supplier/communitys';</script>";
        } else {
            die($result['msg']);
        }
    }

    //房屋初始化
    public function actionRoomInit()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            die("供应商id不能为空！");
        }
        $model = IotSupplierCommunity::findOne($id);
        if (!$model) {
            die("小区开通记录不存在！");
        }
        $result = SupplierService::service()->roomDataInit($model->community_id);
        if ($result["code"]) {
            echo "<script>alert('同步成功');window.location.href='/supplier/communitys';</script>";
        } else {
            die($result['msg']);
        }
    }

    //住户初始化
    public function actionUserInit()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            die("供应商id不能为空！");
        }
        $model = IotSupplierCommunity::findOne($id);
        if (!$model) {
            die("小区开通记录不存在！");
        }
        $result = SupplierService::service()->roomuserDataInit($model->community_id);
        if ($result["code"]) {
            echo "<script>alert('同步成功');window.location.href='/supplier/communitys';</script>";
        } else {
            die($result['msg']);
        }
    }

    //推送配置注册
    public function actionPushRegister()
    {
        if (!empty($_POST)) {
            $id = !empty($_POST['id']) ? $_POST['id'] : 0;
            $requestUrl = !empty($_POST['request_url']) ? $_POST['request_url'] : '';
            $aesKey = !empty($_POST['aes_key']) ? $_POST['aes_key'] : '';
            $callBackTag = !empty($_POST['call_back_tag']) ? $_POST['call_back_tag'] : '';

            $checkedCommunity = IotSupplierCommunity::find()->where(['id' => $id])->asArray()->one();
            if (!$checkedCommunity) {
                echo "<script>alert('注册失败，小区暂未开通');window.location.href='/supplier/communitys';</script>";
            }

            if ($checkedCommunity['supplier_type'] == 1) {
                //道闸
                $publicCallBackTag = ['checkUrl', 'carPortData', 'parkAdd', 'parkEdit', 'parkDelete', 'carAdd',
                    'carEdit', 'carDelete', 'carUserAdd', 'carUserEdit', 'payNotify', 'getCarBill', 'getDeviceList',
                    'pushMsgToDevice'];
            } else {
                //门禁
                $publicCallBackTag = ['checkUrl', 'communityAdd', 'buildingAdd', 'buildingDelete',
                    'roomAdd', 'roomEdit', 'roomDelete',
                    'roomuserAdd', 'roomuserEdit', 'roomuserDelete',
                    'deviceAdd', 'deviceEdit', 'deviceDelete', 'deviceEnabled', 'deviceDisabled',
                    'residentCardAdd', 'residentCardEdit', 'residentCardDelete', 'residentCardEnabled', 'residentCardDisabled',
                    'manageCardAdd', 'manageCardEdit', 'manageCardDelete', 'manageCardEnabled', 'manageCardDisabled'];
            }
            //校验注册回调方法
            if (!empty($callBackTag)) {
                $callBackArr = explode(',', $callBackTag);
                foreach ($callBackArr as $val) {
                    if (!in_array($val, $publicCallBackTag)) {
                        echo "<script>alert('注册失败，存在不合法的回调方法');window.location.href='/supplier/communitys';</script>";
                    }
                }
            }

            //查看是否已经注册过，未注册过去注册，注册过更新
            $model = ParkingPushConfig::find()
                ->select(['id', 'aes_key', 'call_back_tag', 'request_url' , 'is_connect'])
                ->where(['supplier_id' => $checkedCommunity['supplier_id'], 'community_id' => $checkedCommunity['community_id']])->one();
            if ($model) {
                //更新
                if (empty($callBackTag)) {
                    $data['call_back_tag'] = implode(",", $publicCallBackTag);
                } else {
                    $data['call_back_tag'] = $callBackTag;
                }

                $data['supplier_id'] = $checkedCommunity['supplier_id'];
                $data['community_id'] = $checkedCommunity['community_id'];
                $data['auth_code'] = $checkedCommunity['auth_code'];
                $data['request_url'] = $requestUrl;
                $data['aes_key'] = $aesKey;
                $result = PushConfigService::service()->update($data);
            } else {
                //注册
                //默认注册所有方法
                if (empty($callBackTag)) {
                    $data['call_back_tag'] = implode(",", $publicCallBackTag);
                } else {
                    $data['call_back_tag'] = $callBackTag.",checkUrl";
                }

                $data['supplier_id'] = $checkedCommunity['supplier_id'];
                $data['community_id'] = $checkedCommunity['community_id'];
                $data['is_connect'] = 0;
                $data['created_at'] = time();
                $data['auth_code'] = $checkedCommunity['auth_code'];
                $data['request_url'] = $requestUrl;
                $data['aes_key'] = $aesKey;
                $result = PushConfigService::service()->register($data);
            }
            if ($result["code"]) {
                echo "<script>alert('更新成功');window.location.href='/supplier/communitys';</script>";
            } else {
                echo "<script>alert('更新失败,".$result["msg"]."');window.location.href='/supplier/communitys';</script>";
            }
        } else {
            $id = !empty($_GET['id']) ? $_GET['id'] : 0;
            if (!$id) {
                die("供应商id不能为空！");
            }
            $checkedCommunity = IotSupplierCommunity::find()->where(['id' => $id])->asArray()->one();
            if (!$checkedCommunity) {
                die("小区暂未开通！");
            }

            //查看已有的推送配置信息
            $pushConfigData = ParkingPushConfig::find()
                ->select(['id', 'aes_key', 'call_back_tag', 'request_url' , 'is_connect'])
                ->where(['supplier_id' => $checkedCommunity['supplier_id'], 'community_id' => $checkedCommunity['community_id']])
                ->asArray()
                ->one();

            //查询所有供应商
            $suppliers = IotSuppliers::find()
                ->select(['name', 'id'])
                ->orderBy('id desc')
                ->asArray()
                ->all();
            //查询所有小区
            $communitys = PsCommunityModel::find()
                ->select(['name', 'id'])
                ->where(['status' => 1])
                ->orderBy('id desc')
                ->asArray()
                ->all();
            return $this->render('push-register', [
                'suppliers' => $suppliers,
                'communitys' => $communitys,
                'checkedCommunity' => $checkedCommunity,
                'pushConfigData' => $pushConfigData
            ]);
        }
    }

    //JAVA那边所有的供应商列表
    public function actionJavaSupplierList()
    {

        $models = [];
        $list = IotNewService::service()->getProductSn();
        if($list['code'] == 1){
            $models = $list['data'];
        }
        $dataProvider = new ActiveDataProvider([
            'models' => $models,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        return $this->render('java-supplier-list',[
            'model'=>$models,
            'dataProvider' => $dataProvider,
        ]);
    }


}