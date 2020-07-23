<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 13:51
 * Desc: 活动分组
 */
namespace app\models;

class VtActivityGroup extends BaseModel{
    public static function tableName()
    {
        return 'vt_activity_group';
    }

    public function rules()
    {
        return [

            [['activity_id', 'name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','activity_id', 'name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [["id",  'activity_id',"create_at","update_at"], 'integer'],
            [['name'], 'trim'],
            [['name'], 'string', "max" => 20],
            [['id','activity_id'],'dataInfo','on'=>['edit']],
            [["create_at",'update_at'],"default",'value' => time(),'on'=>['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'              => '分组id',
            'activity_id'     => '活动ID',
            'name'            => '分组名称',
            'create_at'       => '新增时间',
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

    //验证数据是否存在
    public function dataInfo($attribute){
        if(!empty($this->id)&&!empty($this->activity_id)){
            $res = self::find()->select(['id'])->where(['=','id',$this->id])->andWhere(['=','activity_id',$this->activity_id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该分组不存在");
            }
        }
    }
}