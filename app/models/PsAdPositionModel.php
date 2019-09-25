<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_ad_position".
 *
 * @property string $id 广告位编号
 * @property string $name 广告位名称
 * @property int $ad_position_type 广告位类型
  1单张 2轮播
 * @property int $ad_num 广告位轮询时的数量
 * @property string $page_id 页面id 1首页 2二级页面
 * @property string $img_url 广告位图示地址
 * @property string $ad_size 广告尺寸描述
 * @property int $status 广告位状态
  1 上线 2下线
 * @property string $operator_id 创建人id
 * @property string $operator_name 创建人姓名
 * @property string $create_at 创建时间
 * @property string $update_at 最近更新时间
 */
class PsAdPositionModel extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_ad_position';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            # 混合场景（新增/编辑广告位）
            [['name', 'ad_size'], 'string', 'max' => 50, 'message' => '{attribute}不能超过50个字符', 'on' => ['add', 'edit']],
            [['img_url'], 'string', 'max' => 128, 'message' => '{attribute}不能超过128个字符', 'on' => ['add', 'edit']],
            [['operator_name'], 'string', 'max' => 20, 'message' => '{attribute}不能超过20个字符', 'on' => ['add', 'edit']],
            [['ad_position_type', 'status', 'operator_id', 'create_at', 'update_at', 'page_id'], 'integer', 'message' => '{attribute}只能为整数', 'on' => ['add', 'edit']],
            # 新增广告位场景
            [['ad_position_type', 'ad_size', 'name', 'status', 'page_id', 'ad_num'], 'required', 'message' => '{attribute}不能为空', 'on' => 'add'],
            # 编辑广告位场景
            [['id', 'page_id', 'update_at', 'status', 'ad_position_type', 'ad_num'], 'required', 'message' => '{attribute}不能为空', 'on' => 'edit'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '广告位编号',
            'name' => '广告位名称',
            'ad_position_type' => '广告位类型',
            'img_url' => '广告位图示地址',
            'ad_size' => '广告位大小',
            'status' => '广告位状态',
            'operator_id' => '创建人id',
            'operator_name' => '创建人名称',
            'create_at' => '创建时间',
            'update_at' => '最近更新时间',
            'page_id'   => '页面id',
        ];
    }
}
