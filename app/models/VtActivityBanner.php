<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 13:51
 * Desc: 活动分组
 */
namespace app\models;

class VtActivityBanner extends BaseModel{
    public static function tableName()
    {
        return 'vt_activity_banner';
    }

    public function rules()
    {
        return [

            [['activity_id', 'img','link_url'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [["id",  'activity_id'], 'integer'],
            [['img','link_url'], 'trim'],
            [['img','link_url'], 'string', "max" => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'              => '分组id',
            'activity_id'     => '活动ID',
            'img'             => 'banner图片',
            'link_url'        => 'banner外链',
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