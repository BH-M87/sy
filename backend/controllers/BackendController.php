<?php
/**
 * Created by PhpStorm.
 * User: zq
 * 后台绑定企业微应用地址
 * Date: 2019/4/28
 * Time: 16:05
 */

namespace backend\controllers;

use backend\models\StCorp;
use backend\models\StCorpAgent;
use backend\services\BackendService;
use yii\web\Controller;
use yii\data\ActiveDataProvider;

class BackendController extends Controller
{
    public $enableCsrfValidation = false;
    //public $layout = "main";
    //企业首页列表
    public function actionIndex()
    {
        $m = StCorp::findOne(1);
        $query = StCorp::find()->orderBy('id desc');
        $data = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ];
        $dataProvider = new ActiveDataProvider($data);
        $model = StCorp::findOne(1);
        return $this->render('index',[
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    //添加企业
    public function actionAdd()
    {
        if (!empty($_POST)) {
            $name = !empty($_POST['name']) ? $_POST['name'] : '';
            $corp_id = !empty($_POST['corp_id']) ? $_POST['corp_id'] : '';
            $company_id = !empty($_POST['company_id']) ? $_POST['company_id'] : '';
            if (!$name) {
                die("企业名称不能为空！");
            }
            if (!$corp_id) {
                die("企业corp_id不能为空！");
            }
            if (!$company_id) {
                die("关联公司ID不能为空！");
            }
            $model = StCorp::find()->where(['corp_id'=>$corp_id])->one();
            if($model){
                die("这个corp_id已经存在！");
            }

            $model = new StCorp();
            $model->corp_name = $name;
            $model->corp_id = $corp_id;
            $model->company_id = $company_id;
            $model->created_at = time();
            if ($model->save()) {
                return $this->redirect(['/backend']);
            } else {
                die("保存失败！");
            }
        } else {
            $companyList = BackendService::service()->getCompanyList();
            return $this->render('add',[
                "companyList"=>$companyList
            ]);
        }
    }

    //删除企业
    public function actionDelete()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            die("企业id不能为空！");
        }
        $model = StCorp::findOne($id);
        if (!$model) {
            die("企业不存在！");
        }
        $agent = StCorpAgent::find()->where(['corp_id'=>$model->corp_id])->one();
        if($agent){
            die("请先删除该企业下的所有微应用！");
        }
        if ($model->delete()) {
            return $this->redirect(['/backend']);
        } else {
            die("删除失败！");
        }
    }

    //微应用列表
    public function actionApplication()
    {
        $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : '';
        $company_name = !empty($_POST['company_name']) ? $_POST['company_name'] : '';
        $query = StCorpAgent::find()->alias('ca')
            ->innerJoin(['c'=>StCorp::tableName()],'c.corp_id = ca.corp_id')->where('1=1')
            ->select(['ca.id','ca.corp_id','ca.agent_id','ca.app_key','ca.app_secret','ca.created_at','c.corp_name']);
        if ($company_name) {
            $query->andWhere(['like','c.corp_name',$company_name]);
        }
        if ($agent_id) {
            $query->andWhere(['like','ca.agent_id',$agent_id]);
        }
        $query->orderBy('ca.id desc');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        //查询所有企业
        $company = StCorp::find()
            ->select(['corp_name as name'])
            ->orderBy('id desc')
            ->asArray()
            ->column();
        return $this->render('application', [
            'model' => $query->all(),
            'dataProvider' => $dataProvider,
            'company' => $company,
            'company_name'=>$company_name,
            'agent_id' => $agent_id,
        ]);
    }


    //新增微应用
    public function actionApplicationAdd()
    {
        if (!empty($_POST)) {
            $req['corp_id'] = !empty($_POST['corp_id']) ? $_POST['corp_id'] : '';
            $req['agent_id'] = !empty($_POST['agent_id']) ? $_POST['agent_id'] : '';
            $req['app_key'] = !empty($_POST['app_key']) ? $_POST['app_key'] : '';
            $req['app_secret'] = !empty($_POST['app_secret']) ? $_POST['app_secret'] : '';
            //查询是否存在
            $model = StCorpAgent::find()
                ->where(['corp_id' => $req['corp_id'], 'agent_id' => $req['agent_id']])
                ->asArray()
                ->one();
            if ($model) {
                die("该企业的这个微应用已经创建");
            }
            $model = new StCorpAgent();
            $req['created_at'] = time();
            $model->setAttributes($req);
            if ($model->save()) {
                return $this->redirect(['/backend/application']);
            } else {
                die("保存失败！");
            }
        } else {
            //查询所有企业
            $company = StCorp::find()
                ->select(['corp_name as name', 'corp_id'])
                ->orderBy('id desc')
                ->asArray()
                ->all();

            return $this->render('application-add', [
                'company' => $company,
            ]);
        }
    }

    //todo 编辑微应用
    public function actionApplicationUpdate()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : (!empty($_POST['id']) ? $_POST['id'] : 0);
        if (!$id) {
            die("供应商id不能为空！");
        }
        $info = StCorpAgent::findOne($id);
        if (!empty($_POST)) {
            $req['corp_id'] = !empty($_POST['corp_id']) ? $_POST['corp_id'] : '';
            $req['agent_id'] = !empty($_POST['agent_id']) ? $_POST['agent_id'] : '';
            $req['app_key'] = !empty($_POST['app_key']) ? $_POST['app_key'] : '';
            $req['app_secret'] = !empty($_POST['app_secret']) ? $_POST['app_secret'] : '';
            $info->setAttributes($req);
            if ($info->save()) {
                return $this->redirect(['/backend/application']);
            } else {
                die("保存失败！");
            }
        } else {
            //查询所有企业
            $company = StCorp::find()
                ->select(['corp_name as name', 'corp_id'])
                ->orderBy('id desc')
                ->asArray()
                ->all();
            $modelInfo = [];
            if($info){
                $modelInfo = $info->toArray();
            }
            return $this->render('application-update', [
                'company' => $company,
                'model' =>$modelInfo
            ]);
        }
    }

    //删除微应用
    public function actionApplicationDelete()
    {
        $id = !empty($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            die("微应用id不能为空！");
        }
        $model = StCorpAgent::findOne($id);
        if (!$model) {
            die("微应用不存在！");
        }
        if ($model->delete()) {
            return $this->redirect(['/backend/application']);
        } else {
            die("删除失败！");
        }
    }
}