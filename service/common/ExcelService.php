<?php

namespace service\common;

use common\core\F;
use service\BaseService;
use Yii;
use yii\base\Exception;
use yii\helpers\FileHelper;

require_once(dirname(__DIR__) . '/../common/PhpExcel/PHPExcel.php');

class ExcelService extends BaseService
{
    public $errorMsg = '';//当前错误信息
    //错误数据
    public $errors = [];

    /**
     * 上传的excel文件检查
     * @param $file $_FILE['file']
     * @return array
     */
    public function excelUploadCheck($file, $max, $offset)
    {
        if (!$file) {
            return $this->failed('文件未上传');
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->failed('不是有效的上传文件');
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, ['xls', 'xlsx'])) {
            return $this->failed('不是有效的excel文件');
        }
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($file['tmp_name']);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(true);
            $PHPExcel = $objReader->load($file['tmp_name']);
        } catch (Exception $e) {
            return $this->failed('文件读取错误');
        }
        $total = $PHPExcel->getActiveSheet()->getHighestRow();
        if ($total - $offset <= 0) {
            return $this->failed('未检测到有效数据');
        }
        if ($total - $offset > $max) {
            return $this->failed('最多只能上传' . $max . '条数据');
        }
        return $this->success($PHPExcel);
    }

    public function excelUpload($file, $savePath)
    {
        $suffix = pathinfo($file['name'], PATHINFO_EXTENSION);
        //判断是不是excel文件
        if (!in_array($suffix, ['xls', 'xlsx'])) {
            return ["status" => true, "errorMsg" => "文件格式不正确"];
        }
        //设置上传路径

        if (!file_exists($savePath)) {//文件目录755权限
            FileHelper::createDirectory($savePath, 0755, true);
        }
        $str = date('Ymdhis') . rand(1000, 9999);
        $file_path = $str . "." . $suffix;
        if (file_exists($savePath . $file_path)) {
            $str = date('Ymdhis') . rand(0, 9999);
            $file_path = $str . "." . $suffix;
        }
        //是否上传成功
        $tmp_file = $file['tmp_name'];
        if (move_uploaded_file($tmp_file, $savePath . $file_path)) {
            try {
                $inputFileType = \PHPExcel_IOFactory::identify($savePath . $file_path);
                $objReader = \ PHPExcel_IOFactory::createReader($inputFileType);
                $objReader->setReadDataOnly(true);
                $PHPExcel = $objReader->load($savePath . $file_path);
            } catch (Exception $e) {
                return ["status" => false, "errorMsg" => "错误文件"];
            }
            //文件权限755
            chmod($savePath . $file_path, 0755);
            $total = $PHPExcel->getActiveSheet()->getHighestRow();
            return ["status" => true, "data" => ["file_name" => $file['name'], "totals" => $total, "next_name" => $file_path]];
        } else {
            return ["status" => false, "errorMsg" => "文件上传失败"];
        }
    }

    public function roominfoDown($datas, $excel_config)
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setTitle("TestExcel");
        $objActSheet = $objPHPExcel->getActiveSheet();;
        $objPHPExcel->getActiveSheet()->setCellValue('A1', "请按照模板填写收费项目，最多上传1000条");
        $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
        $objActSheet->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet_config = $excel_config['sheet_config'];
        foreach ($sheet_config as $item => $value) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($item)->setWidth($value['width'] ? $value['width'] : 20);
            $objPHPExcel->getActiveSheet()->setCellValue($item . '2', $value['title']);
        }
        if (!empty($datas)) {
            foreach ($datas as $key => $data) {
                foreach ($sheet_config as $item => $config) {
                    switch ($config['data_type']) {
                        case 'str':
                            $objPHPExcel->getActiveSheet()->setCellValueExplicit($item . ($key + 3), $data[$config["field"]], 'str');
                            break;
                        case 'protect':
                            $objValidation = $objPHPExcel->getActiveSheet()->getCell($item . ($key + 3))->getDataValidation(); //这一句为要设置数据有效性的单元格
                            $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
                                ->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                                ->setAllowBlank(false)
                                ->setShowInputMessage(true)
                                ->setShowErrorMessage(true)
                                ->setShowDropDown(true)
                                ->setErrorTitle('输入的值有误')
                                ->setError('您输入的值不在下拉框列表内.')
                                ->setPromptTitle("缴费项目")
                                ->setFormula1('"' . $config["protect"] . '"');
                            break;
                    }
                }
            }
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        if (!file_exists($excel_config["save_path"])) {
            FileHelper::createDirectory($excel_config["save_path"], 0755, true);
        }
        $file_name = $excel_config['file_name'];
        $objWriter->save($excel_config["save_path"] . $file_name);
        return $file_name;
    }

    public function recordDown($datas, $excel_config){
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setTitle("TestExcel");
        $objActSheet = $objPHPExcel->getActiveSheet();;
//        $objPHPExcel->getActiveSheet()->setCellValue('A1', "请按照模板填写收费项目，最多上传1000条");
//        $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
//        $objActSheet->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet_config = $excel_config['sheet_config'];
        foreach ($sheet_config as $item => $value) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($item)->setWidth($value['width'] ? $value['width'] : 20);
//            $objPHPExcel->getActiveSheet()->setCellValue($item . '2', $value['title']);
            $objPHPExcel->getActiveSheet()->setCellValue($item . '1', $value['title']);
        }
        if (!empty($datas)) {
            foreach ($datas as $key => $data) {
                foreach ($sheet_config as $item => $config) {
                    switch ($config['data_type']) {
                        case 'str':
                            $objPHPExcel->getActiveSheet()->setCellValueExplicit($item . ($key + 2), $data[$config["field"]], 'str');
                            break;
                    }
                }
            }
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        if (!file_exists($excel_config["save_path"])) {
            FileHelper::createDirectory($excel_config["save_path"], 0755, true);
        }
        $file_name = $excel_config['file_name'];
        $objWriter->save($excel_config["save_path"] . $file_name);
        return $file_name;
    }

    public function payBill($excel_config)
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setTitle("TestExcel");
        $objActSheet = $objPHPExcel->getActiveSheet();;
        $objPHPExcel->getActiveSheet()->setCellValue('A1', "表格中全都是必填项");
        $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
        $objActSheet->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet_config = $excel_config['sheet_config'];
        foreach ($sheet_config as $item => $value) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($item)->setWidth($value['width'] ? $value['width'] : 20);
            $objPHPExcel->getActiveSheet()->setCellValue($item . '2', $value['title']);
        }
        foreach ($sheet_config as $item => $config) {
            switch ($config['data_type']) {
                case 'protect':
                    for ($i = 0; $i <= 1000; $i++) {
                        $objValidation = $objPHPExcel->getActiveSheet()->getCell($item . ($i + 3))->getDataValidation(); //这一句为要设置数据有效性的单元格
                        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(false)
                            ->setShowInputMessage(true)
                            ->setShowErrorMessage(true)
                            ->setShowDropDown(true)
                            ->setErrorTitle('输入的值有误')
                            ->setError('您输入的值不在下拉框列表内.')
                            ->setPromptTitle("缴费项目")
                            ->setFormula1('"' . $config["protect"] . '"');
                    }
                    break;
            }
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        if (!file_exists($excel_config["save_path"])) {
            FileHelper::createDirectory($excel_config["save_path"], 0755, true);
        }
        $file_name = $excel_config['file_name'];
        $objWriter->save($excel_config["save_path"] . $file_name);
        return $file_name;
    }

    public function addZip($dir, $path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE) === true) {
            $handler = opendir($dir); //打开当前文件夹由$path指定。
            /*
            循环的读取文件夹下的所有文件和文件夹
            一定要用!==，因为如果某个文件名如果叫'0'，或者某些被系统认为是代表false，用!=就会停止循环
            */
            while (($filename = readdir($handler)) !== false) {
                if ($filename != "." && $filename != "..") {//文件夹文件名字为'.'和‘..’，不要对他们进行操作
                    $zip->addFile($dir . '/' . $filename, '/' . $filename);
                }
                @closedir($dir);
            }
            $zip->close(); //关闭处理的zip文件
        }
    }

    //设置错误
    public function setError($val, $msg)
    {
        $val = array_values($val);
        $val[] = $msg;
        $tmp = [];
        foreach ($val as $k => $v) {
            $column = $this->getColumn($k);
            $tmp[$column] = $v;
        }
        $this->errors[] = $tmp;
        return $this->errors;
    }

    //判断是否有错误
    public function getErrorCount()
    {
        return count($this->errors);
    }

    //批量设置错误
    public function batchSetError($errorList)
    {
        $this->errors = $errorList;
    }

    //保存错误文件
    public function saveErrorCsv($config)
    {
        if (!$this->errors) {
            return '';
        }
        $columns = range('A', 'Z');
        $i = 0;
        $newConfig = [];
        $config['error'] = ['title' => '错误', 'width' => '100'];
        foreach ($config as $v) {
            $v['field'] = $columns[$i];
            $newConfig[$columns[$i]] = $v;
            $i++;
        }

        return CsvService::service()->saveTempFile(1, array_values($newConfig), $this->errors, '', 'error');
    }

    public function saveErrorExcel($config)
    {
        return $this->export($this->errors, $config);
    }

    /**
     * 导出
     * edit by zq 2018-3-22 妙兜
     * @param $datas
     * @param $excelConfig
     * @return mixed
     */
    public function export($datas, $excelConfig,$diy=null,$start = 1)
    {
        $datas = $datas ? $datas : [];
        $objPHPExcel = new \PHPExcel();
        $sheet_config = $excelConfig['sheet_config'];
        $columns = range('A', 'Z');

        $i = 0;
        $count = count($datas);
        //设置diy头部
        if ($diy !== null) {
            $this->setDiy($objPHPExcel,$diy);
        }
        foreach ($sheet_config as $item => $value) {
            $column = $columns[$i];//0->A
            $activeSheet = $objPHPExcel->getActiveSheet();
            //默认设置为文本格式
            $activeSheet->getStyle($column)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            if (!empty($value['width'])) {
                $activeSheet->getColumnDimension($column)->setWidth($value['width']);
            } else {
                $activeSheet->getColumnDimension($column)->setWidth(15);
            }
            if (!empty($value['rules']['required'])) {
                $value['title'] .= '*';//自动添加*号表示必填
            }
            if (!empty($value['type']) && $value['type'] == 'date') {//设置格式为日期
                $activeSheet->getStyle($column)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            }
            if (!empty($value['items'])) {//下拉列表
                //$this->setItems($activeSheet, $column.'2', $value);
                //下载模版的时候，让用户可以直接选择供应商
                for ($z = 2; $z < $count + 2; $z++) {
                    $this->setItems($activeSheet, $column . $z, $value);
                }
            }
            $activeSheet->setCellValue($column . $start, $value['title']);
            $i++;
        }
        foreach ($datas as $key => $data) {
            $i = 0;
            foreach ($sheet_config as $item => $config) {
                $result = '';
                $column = $columns[$i];//0->A
                if (!empty($data[$item])) {
                    $result = $this->parseType($data, $item, $config);
                }
                if (!$result) {
                    $result = isset($config['default']) ? $config['default'] : '';
                }
                $objPHPExcel->getActiveSheet()->setCellValueExplicit($column . ($key + $start+1), $result, 'str');
                $i++;
            }
        }
        if (!empty($excelConfig["save"])) {
            return $this->saveExcel($objPHPExcel, $excelConfig);
        } else {
            return $this->directDown($objPHPExcel, $excelConfig);
        }
    }


    //保存到文件
    public function saveExcel($objPHPExcel, $config)
    {
        $basePath = Yii::$app->basePath . '/web/store/excel/';
        $baseUrl = F::getAbsoluteUrl() . '/store/excel/';
        $path = isset($config['path']) ? $basePath . $config['path'] . '/' : $basePath;
        $url = isset($config['path']) ? $baseUrl . $config['path'] . '/' : $baseUrl;

        //固定保存的save_path为web/store
        if (!is_dir($path)) {
            FileHelper::createDirectory($path, 0755, true);
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        if (empty($config["file_name"])) {
            $file_name = md5(uniqid(md5(microtime(true)), true)) . '.xlsx';//大概率不重复
        } else {
            $file_name = $config['file_name'];
        }
        $objWriter->save($path . $file_name);
        chmod($path . $file_name, 0755);
        //上传oss
        $downUrl = F::uploadExcelToOss($file_name, $path);
//        return $url . $file_name;
        return $downUrl;
    }

    //不保存文件，直接下载
    protected function directDown($objPHPExcel, $config)
    {
        $filename = $config['file_name'];
        header('Content-Type : application/vnd.ms-excel');
        header('Content-Disposition:attachment;filename=' . $filename);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_end_clean();
        ob_start();
        $objWriter->save("php://output");
    }

    //设置下拉列表框
    public function setItems($activeSheet, $column, $value)
    {
        $str = implode(',', $value['items']);
        $objValidation = $activeSheet->getCell($column)->getDataValidation(); //这一句为要设置数据有效性的单元格
        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
            ->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
            ->setAllowBlank(false)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setShowDropDown(true)
            ->setErrorTitle('输入的值有误')
            ->setError('您输入的值不在下拉框列表内.')
            ->setPromptTitle($value['title'])
            ->setFormula1('"' . $str . '"');
    }

    //根据配置类型，返回格式化的数据
    protected function parseType($data, $item, $config)
    {
        $type = isset($config['type']) ? $config['type'] : 'str';
        $func = 'parse' . ucfirst($type);
        return $this->$func($data, $item, $config);
    }

    //日期格式
    protected function parseDate($data, $item, $config)
    {
        $format = isset($config['format']) ? $config['format'] : 'Y-m-d H:i:s';//默认到秒
        if (!empty($data[$item])) {
            return date($format, $data[$item]);
        }
        return false;
    }

    //默认普通字符串格式
    protected function parseStr($data, $item, $config)
    {
        if (empty($data[$item])) {
            return false;
        }
        if (is_array($data[$item])) {
            $split = !empty($config['split']) ? $config['split'] : ',';
            return implode($split, $data[$item]);
        } else {
            return $data[$item];
        }
    }

    //设置diy格子
    public function setDiy($objPHPExcel,$diy)
    {
        // 设置居中
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        foreach ($diy as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue($v['start'],  $v['info']);
            $objPHPExcel->getActiveSheet()->mergeCells($k);
        }
    }

    //[1=>'激活', 2=>'失效']这种格式
    protected function parseKeys($data, $item, $config)
    {
        if (!isset($config['items'])) {
            throw new \Exception('excel config keys array can not be empty!');
        }
        return !empty($config['items'][$data[$item]]) ? $config['items'][$data[$item]] : false;
    }

    //join 多个字段合成格式
    protected function parseJoin($data, $item, $config)
    {
        if (!isset($config['fields'])) {
            throw new \Exception('excel config join fields can not be empty!');
        }
        $result = '';
        foreach ($config['fields'] as $v) {
            $result .= isset($data[$v]) ? $data[$v] : '';
        }
        return $result ? $result : false;
    }

    /**
     * 加载导入excel文件，并检查后缀，返回PHPEXCEL对象
     * @param $file $_FILES对象
     */
    public function loadFromImport($file)
    {
        if (empty($file)) {
            $this->errorMsg = '未找到上传文件';
            return false;
        }
        $suffix = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array($suffix, ['xls', 'xlsx'])) {
            $this->errorMsg = '文件格式不正确';
            return false;
        }
        return $this->load($file['tmp_name']);
    }

    //加载excel文件
    public function load($file)
    {
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($file);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(true);
            $PHPExcel  = $objReader->load($file);
            $currentSheet = $PHPExcel->getActiveSheet();
            return $currentSheet;
        } catch (\Exception $e) {
            $this->errorMsg = '文件异常';
            return false;
        }
    }

    //只支持26列，超出26列AA, AB暂不考虑
    public function getColumn($key)
    {
        $columns = range('A', 'Z');
        if (empty($columns[$key])) {
            throw new \Exception('column out of range A~Z');
        }
        return $columns[$key];
    }

    //整行数据格式化, ['A'=>1]=>['name'=>1]
    public function format($data, $config)
    {
        $keys = array_keys($config);
        $columns = range('A', 'Z');
        $cr = array_flip($columns);
        $result = [];
        foreach ($data as $k => $v) {
            if (!isset($keys[$cr[$k]])) {
                continue;
            }
            $field = $keys[$cr[$k]];
            if (!empty($config[$field]['format']) && $config[$field]['format'] == 'date') {//时间
                $v = $v ? gmdate('Y-m-d', intval(\PHPExcel_Shared_Date::ExcelToPHP($v))) : '';
            }
            $result[$field] = (string)$v;
        }
        return $result;
    }

    //整行数据验证
    public function valid($data, $config)
    {
        $errors = [];
        foreach ($config as $k => $v) {
            if (empty($v['rules'])) {
                continue;
            }
            $rule = $v['rules'];
            //check required
            if (!empty($rule['required']) && !$this->checkRequired($data, $k)) {
                $errors[] = $v['title'] . '不能为空';
                continue;
            }
            //items check输入值有效性
            if (!empty($v['items']) && !$this->checkItems($data, $k, $v['items'])) {
                $errors[] = $v['title'] . '输入值不在范围内';
                continue;
            }
        }
        return $errors;
    }

    //required
    public function checkRequired($data, $column)
    {
        if (empty($data[$column])) {
            return false;
        }
        $str = trim($data[$column]);
        return $str ? true : false;
    }

    //items check
    public function checkItems($data, $column, $items)
    {
        if (empty($data[$column])) {
            return false;
        }
        return in_array($data[$column], $items) ? true : false;
    }

    public function generateFileName($prefix = '')
    {
        list($msec, $sec) = explode(' ', microtime());
        $msec = round($msec, 3) * 1000;//获取毫秒
        return $prefix . date('YmdHis') . $msec . rand(100, 999) . '.xlsx';
    }
}