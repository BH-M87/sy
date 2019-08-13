<?php

namespace service\common;

use common\core\F;
use service\BaseService;
use Yii;

class CsvService extends BaseService
{
    /**
     * csv数据写到文件流中
     * @param $fp
     * @param $header
     * @param $data
     */
    private function _saveCsv($fp, $header, $data)
    {
        if (!empty($header)) {
            foreach ($header as $key => $value) {
                $header[$key] = iconv('utf-8', 'gbk', $value);
            }
            fputcsv($fp, $header);
        }

        $num = 0;
        $limit = 100000;       // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $count = count($data); // 逐行取出数据，不浪费内存

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $num++;
                if ($num > $limit) { // 刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $num = 0;
                }
                $row = $data[$i];
                foreach ($row as $key => $value) {
                    $v = iconv('utf-8', 'gbk', $value);
                    $row[$key] = "{$v}";
                }
                fputcsv($fp, $row);
            }
        }
        fclose($fp);
    }

    /**
     * 导出CSV文件
     * @param array $data 数据
     * @param array $header_data 首行数据
     * @param string $file_name 文件名称
     * @return string
     */
    public function exportCsv($header_data = [], $data = [], $file_name = '')
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $file_name);
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        return $this->_saveCsv($fp, $header_data, $data);
    }

    /**
     * 将csv保存到本地临时文件以供下载（过期会删除）
     * @param integer $isConfig 是否config模式, (0, 1)
     * @param array $header header or config
     * @param array $data 数据
     * @param string $filePrefix 文件名前缀
     * @param string $type 文件类型,temp or error
     * @return string 文件名
     */
    public function saveTempFile($isConfig, $header, $data, $filePrefix = '', $type = 'temp')
    {
        $data = array_values($data);//重建数据的key，防止一些[1=>'aa']的数据
        $pdir = $this->_generateParentDir();
        $dir = F::excelPath($type.'/'.$pdir);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $fileName = $filePrefix . $this->_generateName('csv');
        $filePath = $dir . '/' . $fileName;
        $fp = fopen($filePath, 'w');
//        if (!flock($fp, LOCK_EX | LOCK_NB)) {
//            return $this->failed('文件被占用，请稍后重试');
//        }
        if ($isConfig) {
            $this->_saveCsvByConfig($fp, $header, $data);
        } else {
            $this->_saveCsv($fp, $header, $data);
        }
        chmod($filePath, 0755);
        return $pdir . '/' . $fileName;
//        return F::getAbsoluteUrl() . $filePath;
    }

    private function _saveCsvByConfig($fp, $config, $data)
    {
        $header = array_column($config, 'title');
        foreach ($header as $key => $value) {
            $header[$key] = iconv('utf-8', 'gbk', $value);
        }
        fputcsv($fp, $header);

        $num = 0;
        $limit = 100000;       // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $count = count($data); // 逐行取出数据，不浪费内存

        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $num++;
                if ($num > $limit) { // 刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $num = 0;
                }
                $row = $data[$i];
                fputcsv($fp, $this->_getRow($row, $config, $count, $i));
            }
        }
        fclose($fp);
    }

    /**
     * 根据配置文件的格式，格式化数据
     * @param $row
     * @param $config
     * @return array
     */
    private function _getRow($row, $config, $count, $i)
    {
        $result = [];
        foreach ($config as $c) {
            $c['data_type'] = !empty($c['data_type']) ? $c['data_type'] : 'str';
            $c['default'] = !empty($c['default']) ? $c['default'] : '';
            $value = $c['default'];
            switch ($c['data_type']) {
                case 'join'://多字段合并成字符串
                    $tt = [];
                    $c['field'] = (array)$c['field'];
                    foreach ($c['field'] as $f) {
                        $tt[] = $row[$f];
                    }
                    $value = $tt ? implode('', $tt) : $c['default'];
                    break;
                case 'arr'://[1=>'男', 2=>'女']
                    $arr = !empty($c['params']) ? $c['params'] : [];
                    $value = !empty($arr[$row[$c['field']]]) ? $arr[$row[$c['field']]] : $c['default'];
                    break;
                case 'date'://时间戳转化为日期时间格式
                    $format = !empty($c['format']) ? $c['format'] : 'Y-m-d H:i:s';
                    $value = !empty($row[$c['field']]) ? date($format, $row[$c['field']]) : $c['default'];
                    break;
                case 'decr'://递减
                    $value = $count-$i;
                    break;
                case 'incr'://递增
                    $value = $i;
                    break;
                case 'object': //对象 ['id' => 1, 'name' => '男']
                    $value = !empty($row[$c['obj_field']][$c['field']]) ? $row[$c['obj_field']][$c['field']] : $c['default'];
                    break;
                default: //默认str，直接读取$row[$field]
                    if (isset($row[$c['field']])) {// 可能会存在值为0的情况
                        if (is_array($row[$c['field']]) && !empty($c['split'])) {
                            $value = implode($c['split'], $row[$c['field']]);
                        } else {
                            $value = "\t".$row[$c['field']]; // 加\t前面的0不会消失
                        }
                    }
                    break;
            }
            $value = iconv('utf-8', 'gbk', $value);
            $result[] = "{$value}";
        }
        return $result;
    }

    /**
     * 生成上层目录名
     * @return false|string
     */
    private function _generateParentDir()
    {
        return date('Y-m-d');//按照日期时间分目录
    }

    /**
     * 创建新的文件名称(以时间区分)
     */
    private function _generateName($ext)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msec = round($msec, 3) * 1000;//获取毫秒
        return date('YmdHis') . $msec . rand(100, 999) . '.' . $ext;
    }
}