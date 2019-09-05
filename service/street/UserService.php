<?php
/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 17:47
 * For: ****
 */

namespace service\street;


use app\models\UserInfo;

class UserService extends BaseService
{

    public function getUserInfoByIdList($idList)
    {
        return  UserInfo::find()->select(['id as user_id','username as user_name'])->where(['id'=>$idList])->asArray()->all();
    }
}