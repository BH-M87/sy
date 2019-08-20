<?php
namespace app\models;

class PsProclaim extends BaseModel
{
    public static function tableName()
    {
        return 'ps_proclaim';
    }

    public function rules()
    {
        return [
            [['community_id','title','proclaim_type','proclaim_cate','is_top'], 'required','message' => '{attribute}不能为空!', 'on' => ['add']],
            [['id','community_id','title','proclaim_type','proclaim_cate','is_top'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit']],
            [['id'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit','del','edit_show','edit_top']],
            [['is_show'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit_show']],
            [['is_top'], 'required','message' => '{attribute}不能为空!', 'on' => ['edit_top']],
            [['proclaim_type','proclaim_cate'], 'in', 'range' => [1, 2, 3],'message' => '{attribute}不正确', 'on' =>['add', 'edit']],
            [['is_top'], 'in', 'range' => [1, 2],'message' => '{attribute}不正确', 'on' =>['add', 'edit','edit_top']],
            [['is_show'], 'in', 'range' => [1, 2],'message' => '{attribute}不正确', 'on' =>['edit_show']],
            //['content', 'string', 'length' => [1, 500], 'message' => '{attribute}长度不正确', 'on' =>['add','edit']],
            ['img_url', 'string', 'length' => [1, 100], 'message' => '{attribute}长度不正确', 'on' =>['add','edit']],
            [['title'], 'string', 'max' => 30],
            [['content'], 'safe']
        ];
    }

    public function attributeLabels()
    {
        return [
            'community_id' => '小区id',
            'title' => '标题',
            'content' => '内容',
            'proclaim_type' => '公告类型',
            'proclaim_cate' => '内容分类',
            'img_url' => '图片',
            'is_top' => '是否置顶',
            'is_show' => '是否显示',
            'create_at' => '添加时间',
        ];
    }
}
