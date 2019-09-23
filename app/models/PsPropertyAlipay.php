<?php

namespace app\models;

use common\MyException;
use app\models\PsUser;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "ps_alipay_apply".
 *
 * @property integer $id
 * @property string $enterprise_name
 * @property string $alipay_account
 * @property integer $status
 * @property integer $type
 * @property integer $apply_type
 * @property string $prove_img
 * @property string $business_img
 * @property string $link_name
 * @property string $link_mobile
 * @property string $email
 * @property integer $user_id
 * @property integer $created_at
 * @property integer $updated_at
 */
class PsPropertyAlipay extends BaseModel
{

    public $nonce = '';
    public static $type_desc = [
        '1' => '不动产管理-物业管理',
    ];
    public static $status_desc = [
        '1' => '申请中',
        '2' => '已签约',
        '3' => '待授权',
        '4' => '已驳回',
        '5' => '待确认',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_property_alipay';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'type', 'apply_type', 'user_id', 'created_at', 'updated_at','company_id'], 'integer'],
            [['enterprise_name', 'alipay_account','type','prove_img','business_img','link_name','link_mobile','email','user_id'], 'required','message' => '{attribute}必填','on' => ['create','edit']],
            [['enterprise_name', 'alipay_account', 'email'], 'string', 'max' => 50],
            [['prove_img', 'business_img'], 'string', 'max' => 255],
            [['link_name'], 'string', 'max' => 20],
            [['type'],'in', 'range'=> [1]],
            [['link_mobile'], 'string', 'max' => 11],
            [['user_id'], 'checkInfo','on' => ['create']],
            [['user_id'], 'getCompany','on' => ['create','edit']],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'enterprise_name' => '企业名称',
            'company_id' => '公司ID',
            'alipay_account' => '支付宝账号',
            'status' => '状态',
            'type' => '类目',
            'apply_type' => '申请类型',
            'prove_img' => '经营资格',
            'business_img' => '店铺招牌',
            'link_name' => '联系人',
            'link_mobile' => '联系电话',
            'email' => '电子邮箱',
            'user_id' => '管理员ID',
            'created_at' => '申请时间',
            'updated_at' => 'Updated At',
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at']
                ],
                'value' => time()
            ]
        ];
    }

    public function checkInfo()
    {
        $user_alipay = PsPropertyAlipay::find()->where(['user_id' => $this->user_id])->one();
        if (!empty($user_alipay)) {
            return $this->addError('user_id', '不能重复申请');
        }
    }

    public function getCompany()
    {
        $user = PsUser::find()->where(['id' => $this->user_id])->one();
        if ($user->user_type != 'admin' && $user->level != 1 && $user->system_type != 2) {
            return $this->addError('user_id', '权限不足，联系超管');
        }
        $company = PsPropertyCompany::find()->where(['user_id' => $this->user_id])->one();
        if (!empty($company)) {
            $this->enterprise_name = $company->property_name;
            $this->company_id = $company->id;
        } else {
            return $this->addError('enterprise_name', '公司不存在');
        }
    }

    public static function getList($params,$field = '*',$order = 'id',$page = true)
    {
        $model = self::find()->select($field)
            ->andFilterWhere(['user_id' => $params['user_id'] ?? null])
            ->andFilterWhere(['status' => $params['status'] ?? null])
            ->andFilterWhere(['like', 'enterprise_name', $params['enterprise_name'] ?? null])
            ->andFilterWhere(['like', 'alipay_account', $params['alipay_account'] ?? null]);
        $count = $model->count();
        if ($count > 0) {
            $model->orderBy($order.' desc');
            if ($page) {
                $model->offset((($params['page'] ?? 1) - 1) * ($params['rows'] ?? 10))->limit($params['rows'] ?? 10);
            }
            $data = $model->asArray()->all();
            self::afterList($data);
        }
        return ['totals'=>$count,'list'=>$data ?? []];
    }


    /**
     * 列表结果格式化
     * @author yjh
     * @param $data
     */
    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $v['created_at'] = date('Y-m-d H:i:s',$v['created_at']);
            $v['status_desc'] = PsPropertyAlipay::$status_desc[$v['status']];
            if ($v['status'] == 3) {
                $company = PsPropertyCompany::find()->where(['id' => $v['company_id']])->one();
                $v['info'] = $company['nonce'] ? Yii::$app->params['auth_to_us_url'] . "&nonce=" . $company['nonce'] : '';
            }
        }
    }
}
