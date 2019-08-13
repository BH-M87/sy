<?php
namespace app\models;

use Yii;

class PsMenus extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'ps_menus';
    }

    public function rules()
    {
        return [
            [['name',"system_type"],'required','on'=>['add']],
            [['name', 'key', 'en_key', 'url', 'action', 'icon', 'level', 'system_type', 'sort_num', 'status', 'is_dd',
                'remark', 'parent_id', 'create_at'], 'safe']

//            [['name', 'level', 'system_type', 'create_at'], 'required'],
//            [['parent_id', 'level', 'system_type', 'create_at'], 'integer'],
//            [['name', 'key', 'action'], 'string', 'max' => 50],
//            [['url'], 'string', 'max' => 150],
//            [['icon'], 'string', 'max' => 20],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '菜单名称',
            'key' => 'key值',
            'en_key' => '按钮别名',
            'url' => '对应url',
            'action' => '后台对应方法',
            'icon' => '图标',
            'level' => '菜单等级',
            'system_type' => '系统类型',
            'sort_num' => '排序',
            'status' => '状态',
            'is_dd' => '是否钉钉菜单',
            'remark' => '备注',
            'parent_id' => '父级id',
            'create_at' => '创建时间',
        ];
    }
}
