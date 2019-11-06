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
use app\models\User;
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
        return  UserInfo::find()->select(['user_id','username as user_name'])->where(['user_id'=>$idList])->asArray()->all();
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

        if($user_info['node_type'] == 0){
            $user_info['dept_id'] = $user_info['org_code'];
        } elseif ($user_info['node_type'] == 1) {
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
        } elseif ($user_info['node_type'] == 7) {
            //其他层级，需向上查询第一个node_type 不为7的父级的org_code
            $deptId = $user_info['dept_id'];
            $orgCode = $this->_getParentOrgCode($deptId);
            if (!$orgCode) {
                throw new MyException("用户组织不存在！");
            }
            $user_info['dept_id'] = $orgCode;
        } elseif ($user_info['node_type'] == 0) {
            $user_info['dept_id'] = $user_info['org_code'];
        }
        //根据所属的组织，查找拥有的小区权限
        $user_info['community_id'] = $this->getCommunityList($user_info['node_type'],$user_info['dept_id']);
        if ($user_info['node_type'] == 6) {
            $user_info['property_company_id'] = PsCommunityModel::find()->where(['id' => $user_info['community_id'][0]])->one()['pro_company_id'];
        }
        $user_info['truename'] = $user_info['username'];
        return $user_info;
    }

    public function getManageUserInfoById($id)
    {
        $companyName = '';
        //$companyName = CompanyService::service()->getNameById($user['property_company_id']);
        $userInfo = User::find()->where(['id'=>$id])->asArray()->all();
        $user_info = [
            'id' => $id,
            'property_company_id' => 0,
            'property_company_name' => "",
            'username' => $userInfo['username'],
            'truename' => $userInfo['username'],
            'mobile' => $userInfo['mobileNumber'],
            'system_type' => 1 ,
            'login_time'=>time(),
            'level' => 1,
            'user_type' => 2,
            'community_id' => "0"
        ];

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
            case "0":
                //查找这个区县的id
                $id = Department::find()->select(['id'])->where(['org_code'=>$dept_id])->asArray()->scalar();
                //找到这个街道下面所有的街道id
                $jiedao = Department::find()->select(['id'])->where(['parent_id'=>$id,'department_level'=>2])->asArray()->column();
                //找到这个街道下面所有的社区id
                $shequ = Department::find()->select(['id'])->where(['parent_id'=>$jiedao,'department_level'=>3])->asArray()->column();
                //找到这些社区下面所有的小区code
                $department = Department::find()->select(['org_code'])->where(['parent_id'=>$shequ,'department_level'=>4])->asArray()->column();
                break;
            case "1":
                //查找这个街道的id
                $id = Department::find()->select(['id'])->where(['org_code'=>$dept_id])->asArray()->scalar();
                //找到这个街道下面所有的社区id
                $shequ = Department::find()->select(['id'])->where(['parent_id'=>$id,'department_level'=>3])->asArray()->column();
                //找到这些社区下面所有的小区code
                $department = Department::find()->select(['org_code'])->where(['parent_id'=>$shequ,'department_level'=>4])->asArray()->column();
                break;
            case "2":
                //查找这个社区的id
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
     * 获取部门的名称
     * @param $code
     * @return false|null|string
     */
    public function getDepartmentNameByCode($code)
    {
        return Department::find()->select(['department_name'])->where(['org_code'=>$code])->scalar();
    }

    /**
     * 获取用户的姓名
     * @param $id
     * @return false|null|string
     */
    public function getUserNameById($id)
    {
        return UserInfo::find()->select(['username'])->where(['user_id'=>$id])->asArray()->scalar();
    }

    /**
     * 递归查询node_type 为7 的父级的 org_code
     * @param $depId
     * @return mixed
     * @throws MyException
     */
    private function _getParentOrgCode($depId, $orgCode = '')
    {
        global $orgCode;
        $model = Department::find()
            ->select('id, org_code,parent_id,node_type')
            ->where(['id' => $depId])
            ->asArray()
            ->one();
        if (!$model) {
            throw new MyException("部门不存在");
        }
        if (!in_array($model['node_type'], ['1','2','3','4','5','6'])) {
            $depId = $model['parent_id'];
            $this->_getParentOrgCode($depId, $orgCode);
        } else {
            $orgCode = $model['org_code'];
        }
        return $orgCode;
    }

    //根据区县的code查找街道的code
    public function getStreetCodeByCounty($dept_id)
    {
        //查找这个区县的id
        $id = Department::find()->select(['id'])->where(['org_code'=>$dept_id])->asArray()->scalar();
        //找到这个街道下面所有的街道org_code
        return Department::find()->select(['org_code'])->where(['parent_id'=>$id,'node_type'=>1])->asArray()->column();
    }

    //根据社区的code查找街道的code
    public function getStreetCodeByDistrict($dept_id)
    {
        //查找这个社区所属的街道的id
        $id = Department::find()->select(['parent_id'])->where(['org_code'=>$dept_id])->asArray()->scalar();
        //找到这个街道下面所有的街道org_code
        return Department::find()->select(['org_code'])->where(['id'=>$id,'node_type'=>1])->asArray()->column();
    }

    //处理搜索小区
    public function dealSearchCommunityId($street_code,$district_code,$community_code,$userInfo)
    {
        //根据小区code查找对应的小区id
        if($community_code){
            return $this->getCommunityList(6,$community_code);
        }
        //根据社区code查找对应的小区id
        if($district_code){
            return $this->getCommunityList(2,$district_code);
        }
        //根据街道code查找对应的小区id
        if($street_code){
            return $this->getCommunityList(1,$street_code);
        }
        if($userInfo){
            return $this->getCommunityList($userInfo['node_type'],$userInfo['dept_id']);
        }
        return [];

    }

    /**
     * 返回全部的人员，目前最高级别是区县
     * 如果是全选街道就传街道id-d
     * 如果是单选人员就传人员id-p
     * add by zq 2019-11-6
     * @param $receive_user_list
     * @return array
     */
    public function dealReceiveUserList($receive_user_list)
    {
        $newList = [];
        if($receive_user_list){
            foreach($receive_user_list as $key=>$value){
                $a = explode("-",$value);
                //添加的是人员id
                if($a[1] == "p"){
                    $newList[] = $a[0];
                }
                //添加的是部门Id
                if($a[1] == "d"){
                    $d = Department::find()->where(['id'=>$a[0]])->asArray()->one();
                    $userIdList =[];
                    //todo 因为目前没有做权限的校验，所以这里直接查这个部门下所有的人员，后续得根据权限做修改
                    switch($d['node_type']){
                        case "0":
                            $userIdList = UserInfo::find()->select(['user_id'])->where(['qx_org_code'=>$d['org_code']])->andWhere(['<>','admin_type',1])->asArray()->column();
                            break;
                        case "1":
                            $userIdList = UserInfo::find()->select(['user_id'])->where(['jd_org_code'=>$d['org_code']])->asArray()->column();
                            break;
                        case "2":
                            $userIdList = UserInfo::find()->select(['user_id'])->where(['sq_org_code'=>$d['org_code']])->asArray()->column();
                            break;
                        case "3":
                            $userIdList = UserInfo::find()->select(['user_id'])->where(['xq_org_code'=>$d['org_code']])->asArray()->column();
                            break;
                    }
                    //合并数组，并去重
                    $newList = array_unique(array_merge($newList,$userIdList));
                    //重新排序
                    sort($newList);
                }
            }

        }
        return $newList;

    }


}