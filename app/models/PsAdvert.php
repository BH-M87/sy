<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_advert".
 *
 * @property string $id 广告编号
 * @property string $ad_position_id 广告位id
 * @property string $name 广告名称
 * @property int $ad_type 1新房全局，2新房区域，3物业全局，4物业区域
 * @property string $ali_img_url 支付宝图片地址
 * @property string $local_url 广告图片服务器地址
 * @property string $img_url 图片地址
 * @property string $link 广告连接
 * @property int $status 1 显示 2 隐藏
 * @property string $sort_no 排序 最小值优先排序
 * @property int $operator_id 创建人id
 * @property string $operator_name 创建人姓名
 * @property string $create_at 创建时间
 * @property string $update_at 最近更新时间
 */
class PsAdvert extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_advert';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ad_position_id', 'ad_type', 'status', 'sort_no', 'operator_id', 'create_at', 'update_at'], 'integer'],
            [['sort_no'], 'required'],
            [['name'], 'string', 'max' => 50],
            [['ali_img_url', 'img_url'], 'string', 'max' => 128],
            [['local_url'], 'string', 'max' => 125],
            [['link'], 'string', 'max' => 1000],
            [['operator_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ad_position_id' => 'Ad Position ID',
            'name' => 'Name',
            'ad_type' => 'Ad Type',
            'ali_img_url' => 'Ali Img Url',
            'local_url' => 'Local Url',
            'img_url' => 'Img Url',
            'link' => 'Link',
            'status' => 'Status',
            'sort_no' => 'Sort No',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'create_at' => 'Create At',
            'update_at' => 'Update At',
        ];
    }
}
