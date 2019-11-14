<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_community_permission".
 *
 * @property int $id
 * @property int $user_id 用户id
 * @property string $xq_org_name 小区org_code
 * @property string $xq_org_code 小区org_name
 * @property string $jd_org_code 街道org_code
 * @property string $jd_org_name 街道org_name
 * @property string $sq_org_code 社区org_name
 * @property string $sq_org_name 社区org_name
 * @property string $ga_org_code 民警org_code
 * @property string $ga_org_name 民警org_name
 * @property string $xf_org_code 消防org_code
 * @property string $xf_org_name 消防org_name
 * @property string $cg_org_code 城管org_code
 * @property string $cg_org_name 城管org_name
 * @property string $extend
 * @property string $gmt_create
 * @property string $gmt_modified
 */
class UserCommunityPermission extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_community_permission';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['gmt_create', 'gmt_modified'], 'safe'],
            [['xq_org_name', 'xq_org_code', 'jd_org_code', 'jd_org_name', 'sq_org_code', 'sq_org_name', 'ga_org_code', 'ga_org_name', 'xf_org_code', 'xf_org_name', 'cg_org_code', 'cg_org_name', 'extend'], 'string', 'max' => 50],
            [['user_id', 'xq_org_code'], 'unique', 'targetAttribute' => ['user_id', 'xq_org_code']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'xq_org_name' => 'Xq Org Name',
            'xq_org_code' => 'Xq Org Code',
            'jd_org_code' => 'Jd Org Code',
            'jd_org_name' => 'Jd Org Name',
            'sq_org_code' => 'Sq Org Code',
            'sq_org_name' => 'Sq Org Name',
            'ga_org_code' => 'Ga Org Code',
            'ga_org_name' => 'Ga Org Name',
            'xf_org_code' => 'Xf Org Code',
            'xf_org_name' => 'Xf Org Name',
            'cg_org_code' => 'Cg Org Code',
            'cg_org_name' => 'Cg Org Name',
            'extend' => 'Extend',
            'gmt_create' => 'Gmt Create',
            'gmt_modified' => 'Gmt Modified',
        ];
    }
}
