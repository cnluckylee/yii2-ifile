<?php

namespace iFile\base;
use iFile\exceptions\FileStorageException;

/**
 * S3标准存储适配器
 *
 * @author ray
 */
abstract class S3StorageAdapter extends FileStorageAdapter
{
    /**
     * 存储配置
     * @var S3StorageConfiguration
     */
    public $config;

    /**
     * 获取endpoint,当指定获取内网地址时,若获取不到则返回公网地址
     * @param bool $internal 获取内网地址
     * @return string
     */
    public function getEndpoint($internal = true)
    {
        if (is_string($this->config->endPoint)) {
            return $this->config->endPoint;
        }

        $key = $internal ? 'internal' : 'public';
        if (is_array($this->config->endPoint) && key_exists($key, $this->config->endPoint)) {
            return $this->config->endPoint[$key];
        }

        return '';
    }

    /**
     * 操作指定bucket
     * @param string $bucketName
     * @return \iFile\base\S3StorageAdapter
     * @throws FileStorageException
     */
    public function useBucket($bucketName)
    {
        if ($bucketName != $this->config->bucket && !in_array($bucketName, $this->config->allowBuckets)) {
            throw new FileStorageException("指定的bucket:{$bucketName} 不在配置的白名单中");
        }

        $this->config->bucket = $bucketName;
        return $this;
    }

    /**
     * 生成sts凭证, https://help.aliyun.com/document_detail/28756.html?spm=a2c4g.11186623.6.789.6fdb39af7XoAqD
     * @param array $config sts配置,默认从配置中取
     * @return array
     * @throws FileStorageException
     */
    public function createStsCredentials($config = [])
    {
        try {
            $beginTs = microtime(true);
            $stsConfig = array_merge($this->config->sts, $config);
            // 未指定时使用默认值
            if (!key_exists('DurationSeconds', $this->config->sts)) {
                $stsConfig['DurationSeconds'] = 3600;
            }

            if ($stsConfig['DurationSeconds'] < 900) {
                $stsConfig['DurationSeconds'] = 900;
            }

            if (!key_exists('RoleSessionName', $this->config->sts)) {
                $stsConfig['RoleSessionName'] = sprintf('%s-client-name', $this->getType());
            }

            $msgArgs = array_merge(['type' => $this->getType()], $stsConfig);
            $credentials = $this->internalCreateStsCredentials($stsConfig);
            return $credentials;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('生成sts凭证出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }
    }

    /**
     * 创建post签名,可用于前端通过表单方式上传
     * @param int $expiration
     * @param array $conditions 策略条件
     * @return array
     * @throws FileStorageException
     */
    public function createFormSignature($expiration = 60, $startWith = '', $conditions = [])
    {
        try {
            $beginTs = microtime(true);
            // 设置大小范围0~1G
            $commonConditions[] = ['content-length-range', 0, 1024*1024*1024];
            if ($startWith) {
                $commonConditions[] = ['starts-with', '$key', $startWith];
            }

            $conditions = array_merge($commonConditions, $conditions);
            $msgArgs = ['type' => $this->getType(), 'expiration' => $expiration, 'conditions' => $conditions];
            $signatures = $this->internalCreateFormSignature($expiration, $startWith, $conditions);
            return $signatures;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('生成签名出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }
    }

    /**
     * 上传目录
     * @param string $localDirectory
     * @param string $prefix
     * @param bool $recursive
     * @param string $exclude
     * @return array
     * @throws FileStorageException
     */
    protected function internalUploadDirectory($localDirectory, $prefix, $recursive, $exclude)
    {
        $result = ['succeededList' => [], 'failedList' => []];
        if (!$localDirectory) {
            throw new FileStorageException('参数localDirectory不能为空');
        }

        if (!is_string($prefix)) {
            throw new FileStorageException('参数prefix必须为字符串');
        }

        $directory = \OSS\Core\OssUtil::encodePath($localDirectory);
        if (!is_dir($directory)) {
            throw new FileStorageException("参数错误,{$directory}不是目录");
        }

        // 读取目录文件
        $fileLists = \OSS\Core\OssUtil::readDir($directory, $exclude, $recursive);
        if (!$fileLists) {
            throw new FileStorageException("{$directory}是空目录");
        }

        // 循环上传
        foreach ($fileLists as $item) {
            if (is_dir($item['path'])) {
                continue;
            }

            $object = ($prefix ? $prefix . '/' : '') . $item['file'];
            try {
                $objectUrl = $this->internalUploadFile($item['path'], $object, true);
                $result["succeededList"][$object] = $objectUrl;
            } catch (\Exception $e) {
                $result["failedList"][$object] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * 生成sts凭证
     */
    abstract protected function internalCreateStsCredentials($stsConfig);

    /**
     * 生成post签名
     */
    abstract protected function internalCreateFormSignature($expiration, $startWith, $conditions);
}
