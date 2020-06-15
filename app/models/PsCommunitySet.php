<?php
namespace app\models;

class PsCommunitySet extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_community_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'create_at', 'update_at'], 'integer'],
            [['community_id','community_name'], 'string', 'max' => 30],
            [['qr_code', 'bang_code'], 'string', 'max' => 255],
            [['create_at','update_at'], 'default', 'value' => time(),'on'=>['add']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'community_id'      => '小区id',
            'community_name'    => '小区名称',
            'qr_code'           => '一区一码二维码',
            'bang_code'         => '帮帮码二维码',
            'create_at'         => '新增时间',
            'update_at'         => '修改时间',
        ];
    }

    /***
     * 新增
     * @return bool
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