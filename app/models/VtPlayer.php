<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 11:19
 * Desc: 活动
 */
namespace app\models;

class VtPlayer extends BaseModel
{


    public static function tableName()
    {
        return 'vt_player';
    }

    public function rules()
    {
        return [

            [['name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['detail']],
            [["id",  'activity_id', 'group_id', 'view_num', 'vote_num','create_at', 'update_at'], 'integer'],
            [['id', 'code', 'activity_id', 'group_id', 'name','img','content'], 'trim'],
            [['content'], 'string'],
            [['code','name'], 'string', "max" => 20],
            [['img'], 'string', "max" => 255],
//            [['name'], 'nameInfo', 'on' => ["add"]], //选手名称唯一
            [['id'], 'dataInfo', 'on' => ["edit","detail"]], //选手是否存在
            [["create_at", 'update_at'], "default", 'value' => time(), 'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'              => '选手id',
              'code'            => '选手编号',
              'activity_id'     => '活动id',
              'group_id'        => '分组ID',
              'name'            => '选手名称',
              'img'             => '选手主图',
              'content'         => '选手内容',
              'view_num'        => '浏览量',
              'vote_num'        => '投票量',
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
     * 选手是否存在
     */
    public function dataInfo($attribute){
        if(!empty($this->id)){
            $res = self::find()->select(['id'])->where(['=','id',$this->id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该选手不存在");
            }
        }
    }
}