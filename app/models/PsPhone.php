<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/10/19
 * Time: 15:25
 * Desc: 常用电话model
 */
namespace app\models;

class PsPhone extends BaseModel
{

    public $typeMsg = ['1'=>'小区服务', '2'=>'公共服务'];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_phone';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','community_name', 'contact_name', 'contact_phone', 'type'], 'required', 'on' => 'add'],
            [['id','community_id', 'community_name','contact_name', 'contact_phone', 'type'], 'required', 'on' => 'edit'],
            [['id', 'community_id'], 'required', 'on' => ['detail']],
            [['id', 'community_id'], 'infoData', 'on' => ["detail"]],
            [['id','community_id', 'contact_name'], 'nameUnique', 'on' => ['add','edit']],   //联系人名称唯一
            [['id', 'type', 'create_at', 'update_at'], 'integer'],
            [['type'], 'in', 'range' => [1, 2], 'message' => '{attribute}取值范围错误'],
            [['community_id', 'community_name'], 'string', 'max' => 30],
            [['contact_name', 'contact_phone'], 'string', 'max' => 20],
            [['contact_phone'], 'match', 'pattern' => parent::MOBILE_RULE, 'message'=>'联系电话必须是区号-电话格式或者手机号码格式'],
            [['create_at', 'update_at'], 'default', 'value' => time(), 'on' => ['add']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
              'id' => '新增id',
              'community_id' => '小区id',
              'community_name' => '小区名称',
              'contact_name' => '联系人',
              'contact_phone' => '联系电话',
              'type' => '电话类型',
              'create_at' => '新增时间',
              'update_at' => '修改时间',
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

    /***
     * 自定义验证是否存在
     * @param $attribute
     */
    public function infoData($attribute){
        if(!empty($this->id)&&!empty($this->community_id)){
            $res = static::find()->select(['id'])->where('id=:id and community_id=:community_id',[':id'=>$this->id,':community_id'=>$this->community_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该数据不存在！");
            }
        }
    }

    /*
     * 联系人名称唯一
     */
    public function nameUnique($attribute){
        if(!empty($this->contact_name)&&!empty($this->community_id)){
            $model = static::find()->select(['id'])->where('contact_name=:contact_name and community_id=:community_id',[':contact_name'=>$this->contact_name,':community_id'=>$this->community_id]);
            if(!empty($this->id)){
                $model->andWhere(['!=','id',$this->id]);
            }
            $res = $model->asArray()->one();
            if (!empty($res)) {
                $this->addError($attribute, "该联系人已存在！");
            }
        }
    }
}