<?php

namespace iFile;

use iFile\alioss\OssAdapter;
use iFile\gateway\GatewayAdapter;
use iFile\minio\MinioAdapter;

/**
 * 存储操作选项
 *
 * @author ray
 */
class Options
{
    const OSS = OssAdapter::ADAPTER_TYPE;
    const MINIO = MinioAdapter::ADAPTER_TYPE;
    const GATEWAY = GatewayAdapter::ADAPTER_TYPE;
    
    public function __construct($properties = [])
    {
        if ($properties) {
               foreach ($properties as $name => $value) {
               $this->$name = $value;
            }
        }
    }
    
    /**
     * 内容类型
     * @var string
     */
    public $contentType;
    
    private static $options = [
        'contentType' => [self::OSS => 'Content-Type', self::MINIO => 'ContentType', self::GATEWAY => 'ContentType'],
    ];
    
    /**
     * 转换为具体的存储驱动的选项
     * @param string $storageType
     * @return array
     */
    public function toArray($storageType)
    {
        $options = [];
        $vars = get_object_vars($this);
        foreach ($vars as $var => $value) {
            if (!isset($this->$var)) {
                continue;
            }
            $key = self::$options[$var][$storageType];
            $options[$key] = $value;  
        }
       
        return $options;
    }
}
