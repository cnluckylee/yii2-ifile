<?php

namespace iFile;

use iFile\gateway\GatewayConfiguration;
use iFile\interfaces\IFileConfigurationInterface;
use iFile\alioss\OssConfiguration;
use iFile\minio\MinioConfiguration;
use iFile\exceptions\FileStorageException;
use iFile\huaweiobs\ObsConfiguration;

/**
 * 存储驱动工厂
 *
 * @author Ray
 */
class FileStorageFactory
{
    /**
     * 根据配置创建指定存储驱动客户端
     * @param IFileConfigurationInterface|null $configuration
     * @return base\S3StorageAdapter
     * @throws exceptions\FileStorageException
     */
    public static function createClient(IFileConfigurationInterface $configuration = null)
    {
        // 默认取yii参数配置文件
        $configuration = is_null($configuration) ?  new ParamsFileConfiguration() : $configuration;
        $config = $configuration->getConfig();
        $type = $configuration->getType();
        switch (strtolower($type)) {
            case alioss\OssAdapter::ADAPTER_TYPE:
                static::checkValues(['accessId', 'accessKey', 'endPoint', 'bucket'], $config);
                $storageConfig = new OssConfiguration($config);
                $storageClient = new alioss\OssAdapter($storageConfig);
                break;
            case minio\MinioAdapter::ADAPTER_TYPE:
                static::checkValues(['accessId', 'accessKey', 'endPoint', 'bucket'], $config);
                $storageConfig = new MinioConfiguration($config);
                $storageClient = new minio\MinioAdapter($storageConfig);
                break;
            case gateway\GatewayAdapter::ADAPTER_TYPE:
                static::checkValues(['accessId', 'accessKey', 'endPoint', 'bucket'], $config);
                $storageConfig = new GatewayConfiguration($config);
                $storageClient = new gateway\GatewayAdapter($storageConfig);
                break;
            case huaweiobs\ObsAdapter::ADAPTER_TYPE:
                static::checkValues(['accessId', 'accessKey', 'endPoint', 'bucket'], $config);
                $storageConfig = new ObsConfiguration($config);
                $storageClient = new huaweiobs\ObsAdapter($storageConfig);
                break;
            default:
                throw new exceptions\FileStorageException("无效的类型type:{$type}");
        }

        return $storageClient;
    }

    private static function checkValues($keys, $config)
    {
        foreach ($keys as $key) {
            if (!key_exists($key, $config) || $config[$key] === '') {
                throw new FileStorageException("配置错误,{$key}不能为空");
            }
        }
    }
}
