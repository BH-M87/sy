<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/5/18
 * Time: 14:46
 * Desc: 系统设置
 */
namespace app\models;

class PsSystemSet extends BaseModel {

    public static function tableName()
    {
        return 'ps_system_set';
    }

    public function rules()
    {
        return [
            [['company_id'], 'required', 'message' => '{attribute}不能为空', 'on' => ['add','detail','edit']],
            [['id', 'payment_set', 'create_at', 'update_at'], 'integer'],
            [['notice_content'], 'string', 'max' => 200],
            [['company_id'], 'string', 'max' => 30],
            [['payment_set'], 'in', 'range' => [1,2]],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_id' => '公司id',
            'payment_set' => '无间断缴费',
            'notice_content' => '缴费通知单备注',
            'create_at' => '创建时间',
            'update_at' => '修改时间',
        ];
    }

    /***
     * 新增
     * @return true|false
     */
    public function saveData()
    {
        return $this->save();
    }

    /***
     * 修改
     * @return bool
     */
    public function edit($param)
    {
        $param['update_at'] = time();
        return self::updateAll($param, ['company_id' => $param['company_id']]);
    }

    /*
     * 获得详情
     */
    public function getDetail($params){
        return self::find()->select(['payment_set','notice_content'])->where(['=','company_id',$params['company_id']])->asArray()->one();
    }
}