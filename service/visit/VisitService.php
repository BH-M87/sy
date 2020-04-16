<?php
namespace service\visit;

use Yii;
use yii\db\Query;
use yii\base\Exception;

use common\MyException;
use common\core\F;
use common\core\PsCommon;

use service\BaseService;
use service\property_basic\JavaService;
use service\common\QrcodeService;

use app\models\PsRoomVisitor;

class VisitService extends BaseService
{
	// 访客 新增
    public function add($p)
    {
        $p['visit_at'] = !empty($p['visit_at']) ? strtotime($p['visit_at']) : '';
        $p['password'] = substr(md5(microtime(true)), 0, 6);

        $m = new PsRoomVisitor(['scenario' => 'add']);

        if (!$m->load($p, '') || !$m->validate()) {
            return PsCommon::responseFailed($this->getError($m));
        }

        if (!$m->saveData($scenario, $p)) {
            return PsCommon::responseFailed($this->getError($m));
        }

        $id = $m->attributes['id'];

        self::createQrcode($m, $id);

        return ['id' => $id];
    }

    // 生成二维码图片
    private static function createQrcode($model, $id)
    {
        $savePath = F::imagePath('visit');
        $logo = Yii::$app->basePath . '/web/img/lyllogo.png'; // 二维码中间的logo
        $url = Yii::$app->getModule('property')->params['ding_web_host'] . '#/scanList?type=scan&id=' . $id;
        
        $imgUrl = QrcodeService::service()->generateCommCodeImage($savePath, $url, $id, $logo, $model); // 生成二维码图片
        
        PsRoomVisitor::updateAll(['qrcode' => $imgUrl], ['id' => $id]);
    }
}