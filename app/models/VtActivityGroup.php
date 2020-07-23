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
            [["id",  'activity_id'], 'integer'],
            [['name'], 'trim'],
            [['name'], 'string', "max" => 20],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'              => '分组id',
            'activity_id'     => '活动ID',
            'name'            => '分组名称',
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
}