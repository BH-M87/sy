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
            [['id'], 'dataInfo', 'on' => ["edit","detail"]], //活动是否存在
            [['start_at', 'end_at'], 'timeVerification', 'on' => ["add","edit"]], //活动code唯一
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

}