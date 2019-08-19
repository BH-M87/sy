<?php

namespace app\models;

use app\common\core\Regular;
use Yii;

/**
 * This is the model class for table "ps_guide".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $title
 * @property integer $type
 * @property string $phone
 * @property string $address
 * @property integer $status
 * @property integer $create_at
 * @property integer $update_at
 * @property integer $hours_start
 * @property integer $hours_end
 * @property integer $img_url
 */
class PsGuide extends BaseModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_guide';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'title', 'type', 'phone', 'address','hours_start','hours_end','img_url'], 'required','message' => '{attribute}不能为空！','on'=>'add'],
            [['community_id', 'type', 'status', 'create_at', 'update_at','hours_start','hours_end'], 'integer'],
            [['status'], 'in', 'range' => [1, 2]],
            [['type'], 'in', 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10,11,12,13,14,15,16,17,18,19]],
            [['title','phone'], 'string', 'max' => 15],
            [['hours_start','hours_end'], 'integer', 'min'=>0,'max'=>23,'on'=>['add','update']],
            [['address'], 'string', 'max' => 50],
            ['type','required','message' => '{attribute}不能为空！','on'=>'list'],
            [['phone'], 'match', 'pattern'=>Regular::telOrPhone(), 'message'=>'联系电话必须是区号-电话格式或者手机号码格式'],
            [['img_url'], 'string', 'max' => 200],
            [['create_at','update_at'],'default','value' => time(),'on' => 'add'],
            [['status'],'default','value' => 1,'on' => 'add'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'title' => '标题',
            'type' => '类型',
            'phone' => '联系电话',
            'address' => '地址',
            'status' => '状态',
            'create_at' => '创建时间',
            'update_at' => '更新时间',
            'hours_start' => '营业开始时间',
            'hours_end' => '营业结束时间',
            'img_url' => '图片url',
        ];
    }

    //列表 总数
    public function getList($param){
        $model = self::find()->where(['=','community_id',$param['community_id']]);
        if(!empty($param['type'])){
            $model->andWhere(['=','type',$param['type']]);
        }
        $count = $model->count();
        $model->orderBy('id desc');
        $offset = ($param['page']-1)*$param['rows'];
        $model->offset($offset)->limit($param['rows']);
        $data = $model->asArray()->all();
        return [
            'data'  =>  $data,
            'count' =>  $count
        ];
    }
}

