<?php
/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 17:47
 * For: 获取JAVA user_info 表相关信息
 */

namespace service\street;


use app\models\Department;
use app\models\PsCommunityModel;
use app\models\UserInfo;
use common\MyException;

class UserService extends BaseService
{

    /**
     * 获取user_info表的id-name
     * @param $idList
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getUserInfoByIdList($idList)
    {
        return  UserInfo::find()->select(['id as user_id','username as user_name'])->where(['id'=>$idList])->asArray()->all();
    }

    /**
     * 获取user_info的基础信息
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function getUserInfoById($id)
    {
        //token验证
        $user_info = UserInfo::find()
            ->select(['id','mobile_number','username','dept_id','node_type','org_code',
                'jd_org_code', 'sq_org_code', 'xq_org_code', 'cg_org_code', 'xf_org_code', 'ga_org_code'])
            ->where(['user_id'=>$id])->asArray()->one();
        if (!$user_info) {
            throw new MyException("用户不存在！");
        }
        if ($user_info['node_type'] == 1) {
            $user_info['dept_id'] = $user_info['jd_org_code'];
        } elseif ($user_info['node_type'] == 2) {
            $user_info['dept_id'] = $user_info['sq_org_code'];
        } elseif ($user_info['node_type'] == 3) {
            $user_info['dept_id'] = $user_info['ga_org_code'];
        } elseif ($user_info['node_type'] == 4) {
            $user_info['dept_id'] = $user_info['xf_org_code'];
        } elseif ($user_info['node_type'] == 5) {
            $user_info['dept_id'] = $user_info['cg_org_code'];
        } elseif ($user_info['node_type'] == 6) {
            $user_info['dept_id'] = $user_info['xq_org_code'];
        } else {
            throw new MyException("用户组织不存在！");
        }
        //根据所属的组织，查找拥有的小区权限
        $user_info['community_id'] = $this->getCommunityList($user_info['node_type'],$user_info['dept_id']);
        $user_info['truename'] = $user_info['username'];
        return $user_info;
    }

    /**
     * 获取这个人的小区id权限列表
     * @param $node_type
     * @param $dept_id
     * @return array
     */
    public function getCommunityList($node_type,$dept_id)
    {
        switch($node_type){
            case "1":
                //查找这个接到的id
                $id = Department::find()->select(['id'])->where(['org_code'=>$dept_id])->asArray()->scalar();
                //找到这个街道下面所有的社区id
                $shequ = Department::find()->select(['id'])->where(['parent_id'=>$id,'department_level'=>3])->asArray()->column();
                //找到这些社区下面所有的小区code
                $department = Department::find()->select(['org_code'])->where(['parent_id'=>$shequ,'department_level'=>4])->asArray()->column();
                break;
            case "2":
                //查找这个接到的id
                $id = Department::find()->select(['id'])->where(['org_code'=>$dept_id])->asArray()->scalar();
                //找到这个社区下面所有的小区code
                $department = Department::find()->select(['org_code'])->where(['parent_id'=>$id,'department_level'=>4])->asArray()->column();
                break;
            case "3":
                //找到对应的街道
                $jiedao = Department::find()->select(['parent_id'])->where(['org_code'=>$dept_id])->scalar();
                //找到这个街道下面所有的社区id
                $shequ = Department::find()->select(['id'])->where(['parent_id'=>$jiedao,'department_level'=>3])->asArray()->column();
                //找到这些社区下面所有的小区code
                $department = Department::find()->select(['org_code'])->where(['parent_id'=>$shequ,'department_level'=>4])->asArray()->column();
                break;
            case "4":
                //找到对应的街道
                $jiedao = Department::find()->select(['parent_id'])->where(['org_code'=>$dept_id])->scalar();
                //找到这个街道下面所有的社区id
                $shequ = Department::find()->select(['id'])->where(['parent_id'=>$jiedao,'department_level'=>3])->asArray()->column();
                //找到这些社区下面所有的小区code
                $department = Department::find()->select(['org_code'])->where(['parent_id'=>$shequ,'department_level'=>4])->asArray()->column();
                break;
            case "5":
                //找到对应的街道
                $jiedao = Department::find()->select(['parent_id'])->where(['org_code'=>$dept_id])->scalar();
                //找到这个街道下面所有的社区id
                $shequ = Department::find()->select(['id'])->where(['parent_id'=>$jiedao,'department_level'=>3])->asArray()->column();
                //找到这些社区下面所有的小区code
                $department = Department::find()->select(['org_code'])->where(['parent_id'=>$shequ,'department_level'=>4])->asArray()->column();
                break;
            case "6":
                //找到这个小区code
                $department = $dept_id;
                //$department = Department::find()->select(['org_code'])->where(['id'=>$dept_id])->asArray()->column();
                break;
            default:
                $department = [];
        }
        //根据code找到对应的小区id
        $community = PsCommunityModel::find()->select(['id'])->where(['event_community_no'=>$department])->asArray()->column();
        return $community ? $community : [];

    }

    /**
     * 获取部门的名称
     * @param $id
     * @return false|null|string
     */
    public function getDepartmentNameById($id)
    {
        return Department::find()->select(['department_name'])->where(['id'=>$id])->scalar();
    }

    /**
     * 获取用户的姓名
     * @param $id
     * @return false|null|string
     */
    public function getUserNameById($id)
    {
        return UserInfo::find()->select(['username'])->where(['id'=>$id])->asArray()->scalar();
    }


}