<?php

namespace iFile\minio;

use iFile\base\S3StorageConfiguration;

/**
 * minIO存储配置
 *
 * @author ray
 */
class MinioConfiguration extends S3StorageConfiguration
{
    /**
     * 指示endpoint是否使用路径风格方式即:http://minio.com/bucket
     * false时为http://bucket.minio.com
     * @var string
     */
    public $usePathStyleEndpoint = true;
    
    /**
     * 用于锁定API版本
     * @var string
     */
    public $version = 'latest';
    
    /**
     * 签名版本(minio目前使用v4版本)
     * @var string
     */
    public $signatureVersion = 'v4';
}
