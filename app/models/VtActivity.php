<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 11:19
 * Desc: 活动
 */
namespace app\models;

class VtActivity extends BaseModel
{


    public static function tableName()
    {
        return 'vt_activity';
    }

    public function rules()
    {
        return [

            [['name', 'code', 'start_at', 'end_at', 'group_status'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','name', 'start_at', 'end_at', 'group_status'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['detail']],
            [["id",  'start_at', 'end_at', 'group_status', 'create_at', 'update_at'], 'integer'],
            [['name', 'code', 'start_at', 'end_at', 'group_status','content'], 'trim'],
            [['content'], 'string'],
            [['code'], 'string', "max" => 20],
            [['name'], 'string', "max" => 50],
            [['link_url', 'qrcode'], 'string', "max" => 255],
            ['group_status', 'in', 'range' => [1, 2], 'on' => ['add','edit']],
            [['code'], 'codeInfo', 'on' => ["add"]], //活动code唯一
            [['id'], 'dataInfo', 'on' => ["edit"]], //活动是否存在
            [['start_at', 'end_at'], 'timeVerification', 'on' => ["add","edit"]], //活动code唯一
            [["create_at", 'update_at'], "default", 'value' => time(), 'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'              => '活动id',
              'code'            => '活动code',
              'name'            => '活动名称',
              'start_at'        => '开始时间',
              'end_at'          => '结束时间',
              'content'         => '活动规则',
              'group_status'    => '选手分组 1启用 2禁用',
              'link_url'        => '页面链接',
              'qrcode'          => '二维码',
              'create_at'       => '创建时间',
              'update_at'       => '修改时间',
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
        return self::updateAll($param, ['id' => $param['id']]);
    }

    /*
     * code唯一
     */
    public function codeInfo($attribute){
        if(!empty($this->code)){
            $res = self::find()->select(['id'])->where(['=','code',$this->code])->asArray()->one();
            if(!empty($res)){
                return $this->addError($attribute, "投票活动code已存在，请重新输入");
            }
        }
    }

    /*
     * 时间验证
     */
    public function timeVerification($attribute){
        if(!empty($this->start_at)&&!empty($this->end_at)){
            $nowTime = time();
            if($this->start_at<$nowTime){
                return $this->addError($attribute, "投票活动开始时间应大于当前时间");
            }

            if($this->start_at>$this->end_at){
                return $this->addError($attribute, "投票活动开始时间应小于投票结束时间");
            }
        }
    }

    /*
     * 验证数据是否存在
     */
    public function dataInfo($attribute){
        if(!empty($this->id)){
            $res = self::find()->select(['id'])->where(['=','id',$this->id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该投票活动不存在");
            }
        }
    }
}