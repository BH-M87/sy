<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "ps_alipay_apply_info".
 *
 * @property integer $id
 * @property integer $apply_id
 * @property integer $status
 * @property string $info
 * @property integer $uid
 * @property integer $created_at
 */
class PsPropertyAlipayInfo extends BaseModel
{

    public static $status_desc = [
        '1' => '待提交',
        '2' => '审核中',
        '3' => '待确认',
        '4' => '待授权',
        '5' => '已签约',
        '6' => '已驳回',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_property_alipay_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['apply_id', 'status', 'uid', 'created_at'], 'integer'],
            [['info'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'apply_id' => 'Apply ID',
            'status' => 'Status',
            'info' => 'Info',
            'uid' => 'Uid',
            'created_at' => 'Created At',
        ];
    }

    public static function getList($params,$field = '*',$order = 'pa.id',$page = true)
    {
        $tb1 = self::find()->orderBy('id desc');
        $tb2 = self::find()->from(['tb1' => $tb1])->groupBy('apply_id');
        $model = self::find()->alias('tb1')->select($field)
            ->from(['tb1' => $tb2])
            ->rightJoin('ps_property_alipay as pa','tb1.apply_id = pa.id')
            ->andFilterWhere(['pa.user_id' => $params['user_id'] ?? null])
            ->andFilterWhere(['tb1.status' => $params['status'] ?? null])
            ->andFilterWhere(['like', 'pa.enterprise_name', $params['enterprise_name'] ?? null])
            ->andFilterWhere(['like', 'pa.alipay_account', $params['alipay_account'] ?? null]);
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
            $v['status_desc'] = self::$status_desc[$v['status']];
            if ($v['status'] == 3) {
                $company = PsPropertyCompany::find()->where(['id' => $v['company_id']])->one();
                $v['info'] = $company['nonce'] ? Yii::$app->params['auth_to_us_url'] . "&nonce=" . $company['nonce'] : '';
            }
        }
    }
}
