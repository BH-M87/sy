<?php
namespace service\alipay;

use service\common\SmsService;
use Yii;

class ReceiptService extends  BaseService {
    /*
     * 查询用户密码
     * */
    public   static  function getPayPwd( $user_id){
        $params = [":user_id" => $user_id];
        $where =  " user_id=:user_id";
        $sql = "select id,password from ps_pay_pwd  where ".$where;
        $model  = Yii::$app->db->createCommand($sql,$params)->queryOne();
        return $model;
    }
    /*
     * 新增用户密码
     * */
    public static function addPayPwd($user_id,$password ){
      $model = self::getPayPwd($user_id);
        if( empty($model)) {
            $params = [
                "user_id"=>$user_id,
                "password"=> Yii::$app->security->generatePasswordHash($password),
                "create_at"=> time(),
            ];
            Yii::$app->db->createCommand()->insert("ps_pay_pwd", $params)->execute();
            return true;
        } else {
            return false;
        }
    }
    /*
     * 修改用户密码
     * */
    public   static  function editPayPwd($user_id,$data ){
        $model = self::getPayPwd($user_id);
        if( !empty($model)) {
            if( Yii::$app->security->validatePassword($data["old_pwd"],$model["password"])) {
                $params = [
                    "password"=> Yii::$app->security->generatePasswordHash($data["new_pwd"]),
                    "update_at"=> time(),
                ];
                Yii::$app->db->createCommand()->update('ps_pay_pwd',
                    $params,
                    "id=:id",
                    [":id" => $model["id"]]
                )->execute();
                return   ["status" => true, "errorMsg" => "编辑成功"];
            } else {
                return ["status"=>false,"errorMsg"=>"旧密码不正确"];
            }
        } else {
            return ["status"=>false,"errorMsg"=>"还未设置密码"];
        }
    }
    /*
     * 重置密码发送验证吗
     */
    public static function addSendCode( $user_id ,$mobile)
    {
        $params = [":user_id" => $user_id, ":create_at" => time() - 600,];
        $sql = "select id,code,status,create_at from ps_send_code  where type=1 and status=0  and user_id=:user_id and create_at>:create_at ";
        $model = Yii::$app->db->createCommand($sql, $params)->queryOne();
        if (empty($model)) {
//            $code  = 2345;
            $code = rand(1000, 999999);
            $params = [
                "code" => $code,
                "user_id" => $user_id,
                "mobile" => $mobile,
                "status" => 0,
                "type" => 1,
                "create_at" => time(),
            ];
            Yii::$app->db->createCommand()->insert("ps_send_code", $params)->execute();
        } else {
            $code = $model["code"];
        }
        SmsService::service()->init(10, $mobile)->send([$code]);
    }

    /*
     * 重置密码
     */
    public static function resetPayPwd($user_id, $data) {
        $model = self::getPayPwd($user_id);
        if( !empty($model)) {
            $params = [":user_id" => $user_id];
            $sql    = "select id,code,status,create_at from ps_send_code  where type=1 and user_id=:user_id order by create_at desc limit 1";
            $code   = Yii::$app->db->createCommand($sql,$params)->queryOne();
            if( !empty($code) ) {
                if($code["code"] == $data["code"] ) {
                    if($code["status"]==0 && $code["create_at"]>(time()-600)) {
                        $params = [
                            "password"=> Yii::$app->security->generatePasswordHash($data["new_pwd"]),
                            "update_at"=> time(),
                        ];
                        Yii::$app->db->createCommand()->update('ps_pay_pwd',
                            $params,
                            "id=:id",
                            [":id" => $model["id"]]
                        )->execute();

                        Yii::$app->db->createCommand()->update('ps_send_code',
                            ["status"=>1],
                            "id=:id",
                            [":id" => $code["id"]]
                        )->execute();
                        return   ["status" => true, "errorMsg" => "重置成功"];
                    } else {
                        return ["status"=>false,"errorMsg"=>"验证码已使用或验证码已过期"];
                    }
                } else {
                    return ["status"=>false,"errorMsg"=>"验证码错误"];
                }
            } else {
                return ["status"=>false,"errorMsg"=>"未发送短信验证码"];
            }
        } else {
            return ["status"=>false,"errorMsg"=>"还未设置密码"];
        }
    }
    /*
     * 验证收款密码
     */
    public static function verifyPayPwd( $user_id, $token, $password){
             $db = Yii::$app->db;
        $now_time = time();
        // 判断 是否超过3次
        $model = self::getPayPwd($user_id);
        if( !empty($model)) {
            if( Yii::$app->security->validatePassword($password,$model["password"])) {
                $where = [":token" => $token];
                Yii::$app->redis->set('wy_pay_pwd_'.$token,$now_time+600);
                $db->createCommand("delete from ps_pay_token where token=:token",$where)->execute();
                $db->createCommand()->insert("ps_pay_token", ["token"=>$token,"create_at"=>$now_time])->execute();
                $db->createCommand("delete from ps_pay_token_log where token=:token",$where)->execute();
                return ["status"=>true,"is_verify"=>"yes"];
            } else {
                $where = [":now_time" => time() - 600, ":token" => $token];
                $token_sql = "select * from ps_pay_token_log where token=:token and create_at>:now_time ";
                $token_log = Yii::$app->db->createCommand($token_sql,$where)->queryOne();
                if( empty($token_log) ) {
                    $db->createCommand()->insert("ps_pay_token_log", ["token"=>$token,"num"=>1,"create_at"=>$now_time])->execute();
                    return ["status"=>true,"is_verify"=>"no","error_num"=>1];
                } else {
                    if( $token_log["num"]==2 ) {
                        Yii::$app->redis->del('wy_user_'.$token);
                        $where = [":token" => $token];
                        $db->createCommand("delete from ps_login_token where token=:token",$where)->execute();
                        $db->createCommand("delete from ps_pay_token_log where token=:token",$where)->execute();
                        return ["status"=>true,"is_verify"=>"no","error_num"=>3];
                    } else {
                        $db->createCommand("update ps_pay_token_log set num=2 where id=".$token_log["id"])->execute();
                        return ["status"=>true,"is_verify"=>"no","error_num"=>2];
                    }
                }
            }
        } else {
            return ["status"=>false,"errorMsg"=>"还未设置密码"];
        }
    }
    private function getTokenError($token) {

        if( empty($token_log) ) {
            $params ["token"] = $token;
            $params ["num"] = 1;
            $params ["create_at"] = time();
            Yii::$app->db->createCommand()->insert("ps_pay_token_log", $params)->execute();
            return ["num"=>1];
        } else {
            if( $token_log["num"]==2 ) {
                Yii::$app->redis->del('wy_user_'.$token);
                $where = [":token" => $token];
                Yii::$app->db->createCommand("delete from ps_login_token where token=:token",$where)->execute();
                Yii::$app->db->createCommand("delete from ps_pay_token_log where token=:token",$where)->execute();
            } else {
                $sql = "update ps_pay_token_log set num=2 where id=".$token_log["id"];
                Yii::$app->db->createCommand()->update($sql)->execute();
                return ["num"=>2];
            }
        }
    }

    public static function verifyPayToken($token) {
        $now_time =time();
        if( Yii::$app->redis->get('wy_pay_pwd_' . $token) ) {
            $ttl = Yii::$app->redis->get('wy_pay_pwd_' . $token);
            if( $ttl && $ttl>$now_time) {
                self::updatePayTokenTime($token);
                Yii::$app->redis->set('wy_pay_pwd_'.$token,$now_time+600);
                return  ['status'=>true];
            } else {
                self::deletePayToken($token);
                return  ['status'=>false,'errorCode'=>20001,'errorMsg'=>"收款时间已过"];
            }
        } else {
            $where = [":now_time" => $now_time - 600, ":token" => $token];
            $token_sql = "select * from ps_pay_token where token=:token and create_at>:now_time ";
            $model = Yii::$app->db->createCommand($token_sql, $where)->queryOne();
            // 查看数据库中是否存在未过期的token
            if ( !empty($model)) {
                self::updatePayTokenTime($token);
                Yii::$app->redis->set('wy_pay_pwd_' . $token, $now_time + 600);
                return  ['status'=>true];
            } else {
                self::deletePayToken($token);
                return  ['status'=>false,'errorCode'=>20001,'errorMsg'=>"超时"];
            }
        }
    }
    public static function updatePayTokenTime($token) {
        Yii::$app->db->createCommand("update ps_pay_token set create_at='".time()."' where token='".$token."'")->execute();
    }
    public static function addReceiptTask( $data) {
        $params = [];
        if (!empty($data['community_id'])) {
            $arr = ['community_id' => $data["community_id"]];
            $params = array_merge($params, $arr);
        }
        if (!empty($data['file_name'])) {
            $arr = ['file_name' => $data["file_name"]];
            $params = array_merge($params, $arr);
        }

        if (!empty($data['totals'])) {
            $arr = ['totals' => $data["totals"]];
            $params = array_merge($params, $arr);
        }

        if (!empty($data['next_name'])) {
            $arr = ['next_name' => $data["next_name"]];
            $params = array_merge($params, $arr);
        }
        if (!empty($data["task_id"])) {
            Yii::$app->db->createCommand()->update("ps_receipt_task", $params, 'task_id=' . $data["task_id"])->execute();
        } else {
            $params ["create_at"] = time();
            Yii::$app->db->createCommand()->insert("ps_receipt_task", $params)->execute();
            return Yii::$app->db->getLastInsertID();
        }
    }
    public static function  getReceiptTask( $task_id ) {
        $connection = Yii::$app->db;
        $params = [":id" =>$task_id];
        $model  = $connection->createCommand( "select * from ps_receipt_task where task_id=:id",
            $params)->queryOne();
        return $model;
    }
    public static function deletePayToken($token) {
        Yii::$app->redis->del('wy_pay_pwd_'.$token);
        $where = [":token" => $token];
        Yii::$app->db->createCommand("delete from ps_pay_token where token=:token",$where)->execute();
    }

}
