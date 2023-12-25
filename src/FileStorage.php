<?php

namespace iFile;

/**
 *  存储驱动组件
 *
 * @author YashonLvan
 */
class FileStorage extends \yii\base\Component
{
    /**
     * 存储驱动类型
     * @var string
     */
    public $type;
    
    /**
     * 配置
     * @var array
     */
    public $config;

    /**
     * 存储驱动配置
     * @var ParamsFileConfiguration
     */
    protected $storageConfig;
    
    public function init()
    {
        if (is_string($this->config)) {
            $class = new \ReflectionClass($this->config);
            $this->storageConfig = $class->newInstanceArgs();
        } else {
            $this->storageConfig = new ParamsFileConfiguration($this->type, $this->config);
        }
    }

    /**
     * 创建一个存储驱动客户端
     * @param string $bucketName 要操作的bucket,默认使用配置中的bucket
     * @return base\S3StorageAdapter
     * @throws exceptions\FileStorageException
     */
    public function createClient($bucketName = '')
    {        
        $client = FileStorageFactory::createClient($this->storageConfig);
        if ($bucketName) {
            $client->useBucket($bucketName);
        }
        
        return $client;
    }
}
