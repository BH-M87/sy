<?php
namespace app\models;
use Yii;

/**
 * This is the model class for table "ps_community_groups".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 */
class PsCommunityBuilding extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_community_building';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'group_id'], 'integer'],
            [['group_name', 'name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'group_id' => 'Group ID',
            'group_name' => 'Group Name',
            'name' => 'Name',
        ];
    }
}