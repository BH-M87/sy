<?php
/**
 * 供应商相关操作
 * User: wenchao.feng
 * Date: 2019/1/31
 * Time: 11:19
 */
namespace app\modules\property\modules\v1\controllers;
use app\models\PsPropertyAlipay;
use app\models\PsPropertyAlipayInfo;
use app\models\PsPropertyCompany;
use common\core\F;
use common\MyException;
use Yii;
use yii\web\Controller;
use yii\data\Pagination;
use yii\data\ActiveDataProvider;


class CompanyH5Controller extends Controller
{
    public $layout = "main";

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



}