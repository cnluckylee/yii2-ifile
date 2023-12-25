<?php

namespace iFile\gateway;

use iFile\base\S3StorageAdapter;
use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use iFile\exceptions\FileStorageException;
use iFile\minio\MinioAdapter;

/**
 * minIO存储适配器
 *
 * @author ray
 */
class GatewayAdapter extends MinioAdapter
{
    const ADAPTER_TYPE = 'gateway';

    /**
     * 获取存储服务类型
     * @return string
     */
    public function getType()
    {
        return self::ADAPTER_TYPE;
    }
    
    /**
     * 简单文件上传, 上传成功返回访问地址
     * @param string $file 本地文件路径
     * @param string $object 文件对象
     * @param bool $override 存在时是否覆盖
     * @return string
     */
    protected function internalUploadFile($file, $object, $override)
    {
        !$override && $this->checkObject($object, 'notexists');
        $client = $this->getClient();
        $result = $client->putObject(['Bucket' => $this->config->bucket, 'Key' => $object, 'SourceFile' => $file]);
        if (empty($result['@metadata']['headers']['x-cos-resource-id'])) {
            throw new FileStorageException("返回头部中缺少x-cos-resource-id字段");
        }

        return sprintf('%s/%s/%s/%s', $this->config->endPoint, $this->config->bucket, $result['@metadata']['headers']['x-cos-resource-id'], $object);
    }

    /**
     * 上传内容
     * @param string $content 文件内容
     * @param string $object 文件对象
     * @param bool $override 存在时是否覆盖
     * @param array $options 上传选项
     * @return string
     */
    protected function internalUploadContent($content, $object, $override, $options)
    {
        !$override && $this->checkObject($object, 'notexists');
        $client = $this->getClient();
        $params = ['Bucket' => $this->config->bucket, 'Key' => $object, 'Body' => $content];
        $result = $client->putObject(array_merge($params, $options));
        return sprintf('%s/%s/%s/%s', $this->config->endPoint, $this->config->bucket, $result['@metadata']['headers']['x-cos-resource-id'], $object);
    }
}
