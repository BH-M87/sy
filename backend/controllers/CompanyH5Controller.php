<?php
/**
 * 供应商相关操作
 * User: wenchao.feng
 * Date: 2019/1/31
 * Time: 11:19
 */
namespace backend\controllers;
use backend\models\PsCommunityModel;
use common\core\F;
use common\MyException;
use Yii;
use yii\web\Controller;
use yii\data\Pagination;
use yii\data\ActiveDataProvider;
use backend\models\PsPropertyAlipayInfo;
use backend\models\PsPropertyAlipay;
use backend\models\PsPropertyCompany;

class CompanyH5Controller extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $model = new PsPropertyAlipay();
        $dataProvider = new ActiveDataProvider([
            'query' => $model::find()->alias('ppa')->select('ppa.*,ppc.nonce')
                ->innerJoin('ps_property_company as ppc', 'ppc.id = ppa.company_id')
                ->orderBy('ppa.id desc'),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionAdd()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            return $this->render('add');
        } else {
            $params = $_POST;
            $tran = \Yii::$app->getDb()->beginTransaction();
            $company = PsPropertyCompany::find()->where(['alipay_account' => $params['alipay_account']])->one();
            if ($company) {
                die('该支付宝账户已存在');
            }
            try {
                $company = new PsPropertyCompany();
                $company->property_name = $params['enterprise_name'];
                $company->property_type = '1';
                $company->company_type = '2';
                $company->link_man = $params['link_name'];
                $company->link_phone = $params['link_mobile'];
                $company->login_phone = $params['link_mobile'];
                $company->alipay_account = $params['alipay_account'];
                $company->nonce = F::companyCode();;
                $company->auth_type = '1';
                $company->create_at = time();
                $company->save();

                $alipay = new PsPropertyAlipay();
                $alipay->company_id = $company->id;
                $alipay->enterprise_name = $params['enterprise_name'];
                $alipay->alipay_account = $params['alipay_account'];
                $alipay->status = '3';
                $alipay->link_name = $params['link_name'];
                $alipay->link_mobile = $params['link_mobile'];
                $alipay->created_at = time();
                $alipay->save();

                $info = new PsPropertyAlipayInfo();
                $info->apply_id = $alipay->id;
                $info->status = 4;
                $info->created_at = time();
                $info->save();
                $tran->commit();
            } catch (\Exception $e) {
                $tran->rollBack();
                die('新增失败');
            }
            $this->redirect('index');
        }
    }

    public function actionBound()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $company = PsPropertyCompany::find()->alias('ppc')
                ->innerJoin('ps_property_alipay as ppa','ppa.company_id = ppc.id')->where(['ppa.status' => 2])->all();
            return $this->render('community', [
                'company' => $company,
            ]);

        } else {
            $params = $_POST;
            $communitys = explode(',',$params['communitys']);
            $data = [];
            foreach ($communitys as $v) {
                $community = PsCommunityModel::find()->where(['id' => $v])->one();
                if (empty($community->pro_company_id)) {
                    $community->pro_company_id = $params['pro_id'];
                    $community->save();
                } else {
                    $data[] = $v;
                }
            }
            if (!empty($data)) {
                die('小区ID: '.implode(',',$data).' 已绑定过物业公司!!!');
            } else {
                $this->redirect('index');
            }
        }
    }



}