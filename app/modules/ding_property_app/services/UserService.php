<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/6/28
 * Time: 10:37
 */
namespace app\modules\ding_property_app\services;
use app\models\PsAdPositionModel;
use app\models\PsAdvert;
use app\models\PsCommunityModel;
use app\models\PsGroups;
use app\models\PsLoginToken;
use app\models\PsMenus;
use app\models\PsRepair;
use app\models\PsRepairAssign;
use app\models\PsUser;
use app\models\PsWaterMeter;
use app\models\ZjyRoleMenu;
use app\models\ZjyUserRole;
use service\BaseService;
use service\manage\CommunityService;
use Yii;

class UserService extends BaseService
{
    //菜单icon
    public $indexMenuIcon = [
        '040111' => 'icon-baoshibaoxiu red',
        '040112' => 'icon-wodegongdan yellow',
        '040101' => 'icon-gongdanfenpei blue',
        '040201' => 'icon-yinangongdan red',
        '131107' => 'icon-chaoshuibiao blue',
        '131108' => 'icon-chaodianbiao orange',
        '1402' => 'icon-xungengdian orange',
        '1403' => 'icon-xungengxianlu yellow',
        '1404' => 'icon-xungengjihua green',
        '1405' => 'icon-woderenwu yellow',
        '1406' => 'icon-kaishixungeng blue',
        '1407' => 'icon-tongjibaobiao blue',
        '2001' => 'icon-xunjiandian orange',
        '2002' => 'icon-xunjianxianlu blue',
        '2003' => 'icon-xunjianjihua green',
        '2007' => 'icon-woderenwu yellow',
        '1310' => 'icon-wuyeshoufei blue',
        '0607' => 'icon-lajidaifafang orange',
        '0608' => 'icon-lajijiancha blue',

        '2105' => 'icon-tonghangmayanzheng green',
        '2106' => 'icon-fangketonghang blue',
        '2107' => 'icon-fangkechaxun yellow',
        '2108' => 'icon-fangkebulu red',
    ];

    //菜单名称
    public $indexMenuName = [
        '040111' => '报事报修',
        '040112' => '我的工单',
        '040101' => '分配工单',
        '040201' => '疑难工单',
        '131107' => '抄水表',
        '131108' => '抄电表',
        '1402' => '巡更点设置',
        '1403' => '巡更路线设置',
        '1404' => '巡更计划设置',
        '1405' => '我的任务',
        '1406' => '开始巡更',
        '1407' => '统计报表',
        '2001' => '巡检点',
        '2002' => '巡检路线',
        '2003' => '巡检计划',
        '2007' => '我的任务',
        '1310' => '物业收费',
        '0607' => '垃圾袋发放',
        '0608' => '垃圾检查',

        '2105' => '通行码验证',
        '2106' => '访客通行',
        '2107' => '访客查询',
        '2108' => '访客补录',
    ];

    //排序值
    public $indexMenuOrder = [
        '040111' => '1',
        '040112' => '2',
        '040101' => '3',
        '040201' => '4',
        '020305' => '5',
    ];

    //报事报修排序
    public $repairMenuOrder = [
        '040111' => '1',
        '040112' => '2',
        '040101' => '3',
        '040201' => '4',
    ];

    //巡更排序
    public $patrolMenuOrder = [
        '1402' => '1',
        '1403' => '2',
        '1404' => '3',
        '1405' => '4',
        '1406' => '5',
        '1407' => '6',
    ];

    /**
     * 获取用户信息
     * @param $phone
     * @return array|string
     */
    public function getUserInfo($phone)
    {
        return $this->getUserByPhone($phone);
    }

    /**
     * 根据手机号查询用户信息
     * @update by shenyang v4.6，用户禁用的时候，清理缓存比较麻烦，所以这个该方法的不加缓存
     * @param $phone
     * @return string
     */
    public function getUserByPhone($phone)
    {
        $userInfo = PsUser::find()
            ->select(['id','username','mobile','truename','level','is_enable','group_id','property_company_id','ding_icon'])
            ->where(['mobile'=>$phone,'system_type'=>2,'is_enable'=>1])
            ->asArray()->one();
        if (!$userInfo) {
            return "该用户不存在！";
        }

        //该用户是否已绑定了小区
        $userInfo['groupname'] = "";

        //查询用户所在的组
        $userGroup = PsGroups::find()
            ->select(['name'])
            ->where(['id' => $userInfo['group_id']])
            ->asArray()
            ->one();
        if ($userGroup) {
            $userInfo['groupname'] = $userGroup['name'];
        }

        if (!CommunityService::service()->getUserCommunityIds($userInfo['id'])) {
            return "该用户未绑定小区！";
        }

        if (!$userInfo) {
            return "该用户不存在！";
        }

        $userInfo['operator_id'] = $userInfo['id'];

        return $userInfo;
    }

    /**
     * 获取用户信息by id
     * @param $user_id
     * @return array|string|\yii\db\ActiveRecord|null
     */
    public function getUserById($user_id)
    {
        $userInfo = PsUser::find()
            ->select(['id','username','mobile','truename','level','is_enable','group_id','property_company_id','ding_icon'])
            ->where(['id'=>$user_id,'system_type'=>2,'is_enable'=>1])
            ->asArray()->one();
        if (!$userInfo) {
            return "该用户不存在！";
        }
        //该用户是否已绑定了小区
        $userInfo['groupname'] = "";

        //查询用户所在的组
        $userGroup = PsGroups::find()
            ->select(['name'])
            ->where(['id' => $userInfo['group_id']])
            ->asArray()
            ->one();
        if ($userGroup) {
            $userInfo['groupname'] = $userGroup['name'];
        }

        if (!CommunityService::service()->getUserCommunityIds($userInfo['id'])) {
            return "该用户未绑定小区！";
        }

        if (!$userInfo) {
            return "该用户不存在！";
        }

        $userInfo['operator_id'] = $userInfo['id'];

        return $userInfo;
    }

    /**
     * 生成token值
     * @param $phone
     * @return array|string
     */
    public function generalToken($user_id,$phone)
    {
        //存入数据库，如果有则更新
        $loginToken = PsLoginToken::find()
            ->where(['user_id' => $user_id])
            ->andWhere(['app_type' => 2])
            ->one();
        if ($loginToken) {
            //判断是否已过有效期
            if (time() >= ($loginToken->expired_time - 3600)) {
                //更新token
                $token = md5('linyilianapp'. $phone . microtime());
                $timeExpired = time() + Yii::$app->getModule('lylapp')->params['api_token_expired_time'] * 86400;

                $loginToken->token     = $token;
                $loginToken->expired_time = $timeExpired;
                $loginToken->save();
            }
        } else {
            $token = md5('linyilianapp'. $phone . microtime());
            $timeExpired = time() + Yii::$app->getModule('ding_property_app')->params['api_token_expired_time'] * 86400;
            $loginToken = new PsLoginToken();
            $loginToken->token     = $token;
            $loginToken->user_id   = $user_id;
            $loginToken->app_type  = 2;
            $loginToken->expired_time = $timeExpired;
            $loginToken->create_at = time();
            $loginToken->save();
        }
        $user['token'] = $loginToken->token;

        return $user;
    }

    /**
     * 更新token值,token正确返回用户id
     * @param $token
     * @return bool|int
     */
    public function refreshToken($token)
    {
        $token = PsLoginToken::find()
            ->select(['user_id', 'expired_time'])
            ->where(['token' => $token, 'app_type' => 2])
            ->one();
        if (!$token) {
            return false;
        }

        if (time() > $token->expired_time) {
            //token过期
            PsLoginToken::deleteAll(['token' => $token, 'app_type' => 2]);
            return false;
        }

        //更新token过期时间
        $timeExpired = time() + Yii::$app->getModule('ding_property_app')->params['api_token_expired_time'] * 86400;
        $token->expired_time = $timeExpired;
        $token->save();
        return $token->user_id;
    }

    /**
     * 查询钉钉用户的菜单
     * @param $menus
     * @param $userInfo
     * @return mixed
     */
    public function getDingUserMenu($userInfo)
    {
        $userId = $userInfo['id'];
        $communitys = CommunityService::service()->getUserCommunityIds($userId);
        //查询小区及小区名称
        $re['community_list'] = PsCommunityModel::find()
            ->select(['id', 'name'])
            ->where(['id' => $communitys])
            ->andWhere(['status' => 1])
            ->asArray()
            ->all();

        //查询首页广告
        $positionId = PsAdPositionModel::find()
            ->select(['id'])
            ->where(['name' => '钉钉首页广告'])
            ->asArray()
            ->scalar();
        $imgUrl = "";
        if ($positionId) {
            $ads = PsAdvert::find()
                ->select(['img_url'])
                ->where(['ad_position_id' => $positionId, 'status' => 1])
                ->orderBy('sort_no asc')
                ->limit(1)
                ->asArray()
                ->one();
            if ($ads) {
                $imgUrl = $ads['img_url'];
            }
        }
        $re['index_ads'] = [
            'imgUrl' => $imgUrl
        ];

        $re['index_menus'] = [];
        //菜单整理
        //报事报修菜单
        $repairMenus = ['040111', '040112', '040101', '040201'];
        //移动抄表菜单
        $waterMenus = ['131107','131108','1310'];
        //巡更菜单
        $patrolMenus = ['1402', '1403', '1404', '1405', '1406', '1407'];
        //巡检菜单
        $inspectMenus = ['2001', '2002', '2003', '2007'];
        //垃圾分类
        $garbageMenus = ['0607', '0608'];
        //访客通行
        $vistorMenus = ['2105', '2106', '2107', '2108'];


        //查询此用户已经配置的菜单权限
        //$hasMenus = $this->getUserMenus($userInfo['group_id']);
        //2019-5-30陈科浪修改，菜单权限跟着role_id角色id走
        //\Yii::info("ding-user:".json_encode($userInfo)."\r\n", 'api');
        $hasMenus = $this->getUserMenusByRole($userInfo);
        //\Yii::info("has-menus:".json_encode($hasMenus)."\r\n", 'api');
        $indexMenus = $repairRale = $waterRale =$electRale = $patrolRale = $inspectRale = $garbageRale = $vistorRale = [];
        foreach ($hasMenus as $data) {
            $menu = [];
            $menu['key'] = $data['key'];
            $menu['icon'] = $this->indexMenuIcon[$data['key']];
            $menu['name'] = $this->indexMenuName[$data['key']];
            if (in_array($menu['key'], $vistorMenus)) {
                $indexMenus[0] = "访客通行";
                $vistorRale[]=$menu;
            }elseif (in_array($menu['key'], $repairMenus)) {
                $indexMenus[1] = "报事报修";
                $repairRale[]=$menu;
            } elseif (in_array($menu['key'], $waterMenus)) {
                $indexMenus[2] = "物业收费";
                $waterRale[]=$menu;
            } elseif (in_array($menu['key'], $inspectMenus)) {
                $indexMenus[3] = "设备巡检";
                $inspectRale[]=$menu;
            }elseif (in_array($menu['key'], $patrolMenus)) {
                $indexMenus[4] = "日常巡更";
                $patrolRale[]=$menu;
            }elseif (in_array($menu['key'], $garbageMenus)) {
                $indexMenus[5] = "垃圾分类";
                $garbageRale[]=$menu;
            }
        }

        //组装首页icon
        ksort($indexMenus);

        foreach ($indexMenus as $k => $v) {
            if ($v == "报事报修") {
                $_menu['title'] = "报事报修";
                $_menu['imgUrl'] = "images/patrol/index-1.png";
                $_menu['menu_list'] = $repairRale;
                array_push($re['index_menus'], $_menu);
            } elseif ($v == "物业收费") {
                $_menu['title'] = "物业收费";
                $_menu['imgUrl'] = "images/patrol/index-2.png";
                $_menu['menu_list'] = $waterRale;
                array_push($re['index_menus'], $_menu);
            } elseif ($v == "日常巡更") {
                $_menu['title'] = "日常巡更";
                $_menu['imgUrl'] = "images/patrol/index-3.png";
                $_menu['menu_list'] = $patrolRale;
                array_push($re['index_menus'], $_menu);
            } elseif ($v == "设备巡检") {
                $_menu['title'] = "设备巡检";
                $_menu['imgUrl'] = "images/patrol/index-4.png";
                $_menu['menu_list'] = $inspectRale;
                array_push($re['index_menus'], $_menu);
            }elseif ($v == "垃圾分类") {
                $_menu['title'] = "垃圾分类";
                $_menu['imgUrl'] = "images/patrol/index-4.png";
                $_menu['menu_list'] = $garbageRale;
                array_push($re['index_menus'], $_menu);
            }elseif ($v == "访客通行") {
                $_menu['title'] = "访客通行";
                $_menu['imgUrl'] = "images/patrol/index-4.png";
                $_menu['menu_list'] = $vistorRale;
                array_push($re['index_menus'], $_menu);
            }
        }
        return $re;
    }

    /**
     * 查询二级页面菜单
     * @param $reqArr
     * @return array|string
     */
    public function getMenus($reqArr)
    {
        $userId = $reqArr['id'];
        $communitys = CommunityService::service()->getUserCommunityIds($userId);
        $type = !empty($reqArr['type']) ? $reqArr['type'] : "";

        if (!$type) {
            return "请输入查询类型！";
        }

        if (!in_array($type, [1, 2, 3])) {
            return "查询类型有误！";
        }
        //$hasMenus = $this->getUserMenus($reqArr['group_id']);
        //查询此用户已经配置的菜单权限
        $hasMenus = $this->getUserMenusByRole($reqArr);

        if ($type == 1) {
            //查询报事报修菜单
            $menus = $this->getRepairMenus($hasMenus, $userId, $communitys);
        } elseif ($type == 2) {
            //查询抄水表菜单
            $menus = $this->getWaterMenus($hasMenus, $communitys);
        } elseif ($type == 3) {
            //查询巡更菜单
            $menus = $this->getPatrolMenus($hasMenus);
        }
        return $menus;
    }

    /**
     * 查询详情页菜单列表
     * @param $reqArr
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getViewMenus($reqArr)
    {
        $userId = $reqArr['id'];
        $parentType = !empty($reqArr['parent_type']) ? $reqArr['parent_type'] : "";
        $childType  = !empty($reqArr['child_type']) ? $reqArr['child_type'] : "";
        $childMenus = [];

        //$hasMenus = $this->getUserMenusKey($reqArr['group_id']);  //2019-5-30 陈科浪修改，已经没有用了，不按组id获取权限，按角色获取
        //查询此用户已经配置的菜单权限
        $hasMenus = $this->getUserMenusKeyByRole($reqArr);
        switch ($childType) {
            case "1" :
                $parentMenu = PsMenus::find()
                    ->select(['id'])
                    ->where(['key' => '0401', 'system_type' => 2])
                    ->asArray()
                    ->one();
                if ($parentMenu) {
                    $childMenus = PsMenus::find()
                        ->select(['id', 'name', 'key'])
                        ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                        ->andWhere(['not in', 'key',['040110', '040111', '040112']])
                        ->asArray()
                        ->all();
                }
                break;
            case "2" :
                $parentMenu = PsMenus::find()
                    ->select(['id'])
                    ->where(['key' => '0402', 'system_type' => 2])
                    ->asArray()
                    ->one();
                if ($parentMenu) {
                    $childMenus = PsMenus::find()
                        ->select(['id', 'name', 'key'])
                        ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                        ->asArray()
                        ->all();
                }
                break;
            case "3" :
                $parentMenu = PsMenus::find()
                    ->select(['id'])
                    ->where(['key' => '1402', 'system_type' => 2])
                    ->asArray()
                    ->one();
                if ($parentMenu) {
                    $childMenus = PsMenus::find()
                        ->select(['id', 'name', 'key'])
                        ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                        ->asArray()
                        ->all();
                }
                break;
            case "4" :
                $parentMenu = PsMenus::find()
                    ->select(['id'])
                    ->where(['key' => '1403', 'system_type' => 2])
                    ->asArray()
                    ->one();
                if ($parentMenu) {
                    $childMenus = PsMenus::find()
                        ->select(['id', 'name', 'key'])
                        ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                        ->asArray()
                        ->all();
                }
                break;
            case "5" :
                $parentMenu = PsMenus::find()
                    ->select(['id'])
                    ->where(['key' => '1404', 'system_type' => 2])
                    ->asArray()
                    ->one();
                if ($parentMenu) {
                    $childMenus = PsMenus::find()
                        ->select(['id', 'name', 'key'])
                        ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                        ->asArray()
                        ->all();
                }
                break;
            case "6" :
                $parentMenu = PsMenus::find()
                    ->select(['id'])
                    ->where(['key' => '1407', 'system_type' => 2])
                    ->asArray()
                    ->one();
                if ($parentMenu) {
                    $childMenus = PsMenus::find()
                        ->select(['id', 'name', 'key'])
                        ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                        ->asArray()
                        ->all();
                }
                break;
            default:
                $childMenus = [];
        }

        if (!empty($childMenus)) {
            foreach ($childMenus as $k => $v) {
                $childMenus[$k]['enabled'] = 0;
                if (in_array($v['key'], $hasMenus)) {
                    $childMenus[$k]['enabled'] = 1;
                }
            }
        }
        return $childMenus;
    }

    /**
     * 详情页菜单按钮——一次性返回
     * @param $reqArr
     * @return array
     */
    public function getDetailMenus($reqArr)
    {
        $type = !empty($reqArr['type']) ? $reqArr['type'] : "";
        $childMenus = [];

        //$hasMenus = $this->getUserMenusKey($reqArr['group_id']);  //2019-5-30 陈科浪修改，已经没有用了，不按组id获取权限，按角色获取
        //查询此用户已经配置的菜单权限
        $hasMenus = $this->getUserMenusKeyByRole($reqArr);
        if ($type == 1) {
            /**报事报修**/
            //工单详情页
            $parentMenu = PsMenus::find()
                ->select(['id'])
                ->where(['key' => '0401', 'system_type' => 2])
                ->asArray()
                ->one();
            if ($parentMenu) {
                $tmpMenus = PsMenus::find()
                    ->select(['id', 'name', 'key'])
                    ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                    ->andWhere(['not in', 'key',['040110', '040111', '040112']])
                    ->asArray()
                    ->all();
                if (!empty($tmpMenus)) {
                    foreach ($tmpMenus as $k => $v) {
                        $tmpMenus[$k]['enabled'] = 0;
                        if (in_array($v['key'], $hasMenus)) {
                            $tmpMenus[$k]['enabled'] = 1;
                        }
                    }
                }

            }
            $childMenus['issue_view_menus'] = $tmpMenus;
            //疑难工单详情页
            $parentMenu = PsMenus::find()
                ->select(['id'])
                ->where(['key' => '0402', 'system_type' => 2])
                ->asArray()
                ->one();
            if ($parentMenu) {
                $tmpMenus = PsMenus::find()
                    ->select(['id', 'name', 'key'])
                    ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                    ->asArray()
                    ->all();
                if (!empty($tmpMenus)) {
                    foreach ($tmpMenus as $k => $v) {
                        $tmpMenus[$k]['enabled'] = 0;
                        if (in_array($v['key'], $hasMenus)) {
                            $tmpMenus[$k]['enabled'] = 1;
                        }
                    }
                }
            }
            $childMenus['issue_hard_view_menus'] = $tmpMenus;
        } elseif ($type == 2) {
            /**巡更**/
            //巡更点设置
            $parentMenu = PsMenus::find()
                ->select(['id'])
                ->where(['key' => '1402', 'system_type' => 2])
                ->asArray()
                ->one();
            if ($parentMenu) {
                $tmpMenus = PsMenus::find()
                    ->select(['id', 'name', 'key'])
                    ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                    ->asArray()
                    ->all();
                if (!empty($tmpMenus)) {
                    foreach ($tmpMenus as $k => $v) {
                        $tmpMenus[$k]['enabled'] = 0;
                        if (in_array($v['key'], $hasMenus)) {
                            $tmpMenus[$k]['enabled'] = 1;
                        }
                    }
                }
            }
            $childMenus['patrol_point_view_menus'] = $tmpMenus;

            //巡更路线
            $parentMenu = PsMenus::find()
                ->select(['id'])
                ->where(['key' => '1403', 'system_type' => 2])
                ->asArray()
                ->one();
            if ($parentMenu) {
                $tmpMenus = PsMenus::find()
                    ->select(['id', 'name', 'key'])
                    ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                    ->asArray()
                    ->all();
                if (!empty($tmpMenus)) {
                    foreach ($tmpMenus as $k => $v) {
                        $tmpMenus[$k]['enabled'] = 0;
                        if (in_array($v['key'], $hasMenus)) {
                            $tmpMenus[$k]['enabled'] = 1;
                        }
                    }
                }
            }
            $childMenus['patrol_line_view_menus'] = $tmpMenus;

            //巡更计划
            $parentMenu = PsMenus::find()
                ->select(['id'])
                ->where(['key' => '1404', 'system_type' => 2])
                ->asArray()
                ->one();
            if ($parentMenu) {
                $tmpMenus = PsMenus::find()
                    ->select(['id', 'name', 'key'])
                    ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                    ->asArray()
                    ->all();
                if (!empty($tmpMenus)) {
                    foreach ($tmpMenus as $k => $v) {
                        $tmpMenus[$k]['enabled'] = 0;
                        if (in_array($v['key'], $hasMenus)) {
                            $tmpMenus[$k]['enabled'] = 1;
                        }
                    }
                }
            }
            $childMenus['patrol_plan_view_menus'] = $tmpMenus;

            //统计报表
            $parentMenu = PsMenus::find()
                ->select(['id'])
                ->where(['key' => '1407', 'system_type' => 2])
                ->asArray()
                ->one();
            if ($parentMenu) {
                $tmpMenus = PsMenus::find()
                    ->select(['id', 'name', 'key'])
                    ->where(['parent_id' =>$parentMenu['id'], 'system_type' => 2])
                    ->asArray()
                    ->all();
                if (!empty($tmpMenus)) {
                    foreach ($tmpMenus as $k => $v) {
                        $tmpMenus[$k]['enabled'] = 0;
                        if (in_array($v['key'], $hasMenus)) {
                            $tmpMenus[$k]['enabled'] = 1;
                        }
                    }
                }
            }
            $childMenus['patrol_stat_view_menus'] = $tmpMenus;
        }

        return $childMenus;

    }


    //2019-5-30 调用会员中心接口获取权限
    public function getUserMenusByRole($userInfo){
        if($userInfo['level']==1){//超管账号
            $hasMenus = PsMenus::find()->select(['key','name','icon'])->andWhere(['system_type' => 2,'is_dd'=>'2'])->asArray()->all();
        }else{
            $roleAll = ZjyUserRole::find()->select("role_id")->where(['user_id'=>$userInfo['id'],'deleted'=>'0'])->asArray()->column();
            //子员工
            $hasMenus = ZjyRoleMenu::find()->alias("rale")
                ->select(['menu.id','menu.key','menu.name','menu.icon'])
                ->leftJoin('ps_menus menu', 'menu.id = rale.menu_id')
                ->where(['rale.role_id' => $roleAll])
                ->andWhere(['menu.system_type' => 2])
                ->groupBy("menu.id")
                ->asArray()
                ->all();
        }
        return $hasMenus;
    }

    /**
     * 查询报事报修相关菜单
     * @param $menus 用户所有的菜单
     * @param $userId 用户id
     * @param $communitys 用户拥有权限的所有小区
     * @return array
     */
    private function getRepairMenus($menus, $userId, $communitys)
    {
        //菜单列表
        $hasMenus = [];
        //报事报修
        $repairKeys = ['040111', '040112', '040101', '040201'];
        $repairHasKey = [];

        //查询首页菜单
        foreach ($menus as $menu) {
            if (in_array($menu['key'], $repairKeys)) {
                array_push($repairHasKey, $menu['key']);
            }
        }
        $repairHasKey = array_unique($repairHasKey);

        //数组处理，加排序
        $tmpArr = [];
        foreach ($repairHasKey as $k => $v) {
            $tmpArr[$k]['name'] = $v;
            $tmpArr[$k]['sort'] = $this->repairMenuOrder[$v];
        }
        array_multisort(array_column($tmpArr,'sort'),SORT_ASC,$tmpArr);
        $repairHasKey = array_column($tmpArr,'name');
        foreach ($repairHasKey as $k => $v) {
            $hasMenus[$k]['imgUrl'] = $this->indexMenuIcon[$v];
            $hasMenus[$k]['title'] = $this->indexMenuName[$v];
            $hasMenus[$k]['text'] = "";
            if ($v == "040111") {
                //报事报修
                $addRepairNum = PsRepair::find()
                    ->where(['created_id' => $userId])
                    ->andWhere(['community_id' => $communitys])
                    ->count('id');
                $hasMenus[$k]['text'] = "已提交{$addRepairNum}个工单";
            } elseif ($v == "040112") {
                //我的工单
                $mineRepair = PsRepairAssign::find()
                    ->leftJoin('ps_repair repair', 'ps_repair_assign.repair_id = repair.id')
                    ->groupBy('ps_repair_assign.repair_id')
                    ->where(['ps_repair_assign.user_id' => $userId])
                    ->andWhere(['repair.community_id' => $communitys])
                    ->andWhere(['repair.status' => 7])
                    ->asArray()
                    ->all();
                $mineRepairNum = count($mineRepair);
                $hasMenus[$k]['text'] = "当前{$mineRepairNum}个待处理工单";
            } elseif ($v == "040101") {
                //工单分配
                $waitDoRepairNum = PsRepair::find()
                    ->where(['status' => 1])
                    ->andWhere(['community_id' => $communitys])
                    ->andWhere(['hard_type' => 1])
                    ->count('id');
                $hasMenus[$k]['text'] = "当前{$waitDoRepairNum}个待处理工单";
            } elseif ($v == "040201") {
                //疑难工单
                $hardRepairNum = PsRepair::find()
                    ->where(['hard_type' => 2])
                    ->andWhere(['community_id' => $communitys])
                    ->count('id');
                $hasMenus[$k]['text'] = "当前{$hardRepairNum}个疑难工单";
            }
        }
        return $hasMenus;
    }

    /**
     * 查询水表菜单
     * @param $menus
     * @param $communitys
     * @return array
     */
    private function getWaterMenus($menus, $communitys)
    {
        //菜单列表
        $hasMenus    = [];
        $waterKeys   = ['020305'];
        $waterHasKey = [];

        //水表菜单
        foreach ($menus as $menu) {
            if (in_array($menu, $waterKeys)) {
                array_push($waterHasKey, $menu);
            }
        }

        //抄水表
        $waterNum = PsWaterMeter::find()
            ->where(['community_id' => $communitys])
            ->andWhere(['has_reading' => 2])
            ->count('id');

        $hasMenus[0]['imgUrl'] = $this->indexMenuIcon['020305'];
        $hasMenus[0]['title']  = $this->indexMenuName['020305'];
        $hasMenus[0]['text']  = "当前{$waterNum}户水表未抄";
        return $hasMenus;
    }

    /**
     * 日常巡更菜单
     * @param $menus
     * @return array
     */
    private function getPatrolMenus($menus)
    {
        //菜单列表
        $hasMenus = [];
        //巡更菜单

        $patrolKeys = ['1402','1403','1404','1405','1406','1407'];
        $patrolHasKey = [];

        //查询首页菜单
        foreach ($menus as $menu) {
            if (in_array($menu, $patrolKeys)) {
                array_push($patrolHasKey, $menu);
            }
        }
        $patrolHasKey = array_unique($patrolHasKey);
        //数组处理，加排序
        $tmpArr = [];
        foreach ($patrolHasKey as $k => $v) {
            $tmpArr[$k]['name'] = $v;
            $tmpArr[$k]['sort'] = $this->patrolMenuOrder[$v];
        }
        array_multisort(array_column($tmpArr,'sort'),SORT_ASC,$tmpArr);
        $patrolHasKey = array_column($tmpArr,'name');
        foreach ($patrolHasKey as $k => $v) {
            $hasMenus[$k]['imgUrl'] = $this->indexMenuIcon[$v];
            $hasMenus[$k]['title']  = $this->indexMenuName[$v];
            $hasMenus[$k]['text']   = "";
        }
        return $hasMenus;
    }

    //2019-5-30 调用会员中心接口获取权限
    public function getUserMenusKeyByRole($userInfo){
        if($userInfo['level']==1){//超管账号
            $hasMenus = PsMenus::find()->select(['key','name','icon'])->andWhere(['system_type' => 2,'is_dd'=>'2'])->asArray()->all();
        }else{
            $roleAll = ZjyUserRole::find()->select("role_id")->where(['user_id'=>$userInfo['id'],'deleted'=>'0'])->asArray()->column();
            //子员工
            $hasMenus = ZjyRoleMenu::find()->alias("rale")
                ->select(['menu.key'])
                ->leftJoin('ps_menus menu', 'menu.id = rale.menu_id')
                ->where(['rale.role_id' => $roleAll])
                ->andWhere(['menu.system_type' => 2])
                ->asArray()
                ->column();
        }
        return $hasMenus;
    }

}