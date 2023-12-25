<?php

namespace iFile\alioss;

use iFile\base\S3StorageAdapter;
use OSS\OssClient;
use iFile\exceptions\FileStorageException;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Sts\Sts;

/**
 * 阿里oss适配器
 *
 * @author ray
 */
class OssAdapter extends S3StorageAdapter
{
    const ADAPTER_TYPE = 'alioss';

    /**
     * 存储配置
     * @var OssConfiguration
     */
    public $config;

    /**
     * Oss客户端
     * @var OssClient
     */
    private $client = null;

    /**
     * Oss客户端(内网)
     * @var OssClient
     */
    private $intenalClient = null;

    /**
     * 获取存储服务类型
     * @return string
     */
    public function getType()
    {
        return self::ADAPTER_TYPE;
    }

     /**
     * 最大允许的分片大小 5G
     * @return int
     */
    protected function getMaxChunkSize()
    {
        return 5368709120;
    }

    /**
     * 最小允许的分片大小 10K
     * @return int
     */
    protected function getMinChunkSize()
    {
        return 102400;
    }

    /**
     * 获取Oss客户端
     * @param bool $internal 是否使用内部网络
     * @return OssClient
     * @throws \OSS\Core\OssException
     */
    protected function getClient($internal = true)
    {
        if (is_null($this->client)) {
            $endpoint = $this->getEndpoint(false);
            $this->client = new OssClient($this->config->accessId, $this->config->accessKey, $endpoint);
        }

        if (is_null($this->intenalClient)) {
            $endpoint = $this->getEndpoint(true);
            $this->intenalClient = new OssClient($this->config->accessId, $this->config->accessKey, $endpoint);
        }

        return $internal ? $this->intenalClient : $this->client;
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
        $fileSize = filesize($file);

        // 自动分片
        if ($this->config->autoChunk && $fileSize > $this->getChunkSize()){
            return $this->multiUploadFile($file, $object, $override);
        }

        $result = $client->uploadFile($this->config->bucket, $object, $file);
        return $result['info']['url'];
    }

    /**
     * 分片上传, 上传成功返回访问地址
     * @param string $file 本地文件路径
     * @param string $object 文件对象
     * @param bool $override 存在时是否覆盖
     * @return string
     */
    protected function internalMultiUploadFile($file, $object, $override)
    {
        !$override && $this->checkObject($object, 'notexists');
        $client = $this->getClient();
        $options = [OssClient::OSS_PART_SIZE => $this->getChunkSize()];
        $result = $client->multiuploadFile($this->config->bucket, $object, $file, $options);
        if (!empty($result['body'])) {
            // 获取分片上传文件访问地址
            $multipartUploadResult = $result['body'];
            $multipartUploadResult = json_decode(json_encode(simplexml_load_string($multipartUploadResult)), true);
            return $multipartUploadResult['Location'];
        } else {
            // 获取简单上传文件访问地址
            return $result['info']['url'];
        }
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
        // 支持流上传
        if (is_resource($content)) {
            $fstat = fstat($content);
            $options['length'] = $fstat['size'];
            $options['fileUpload'] = $content;
            unset($content);
        }

        $result = $client->putObject($this->config->bucket, $object, $content, $options);
        return $result['info']['url'];
    }

    /**
     * 获取文件签名后的访问地址
     * @param string $object 文件对象或对象url
     * @param string $method 对象操作命令 读取使用GET,上传使用PUT
     * @param int $expiration 过期时间(单位:秒)
     * @return string
     */
    protected function internalGetFileUrl($object, $method, $expiration)
    {
        $client = $this->getClient(false);
        $url = $client->signUrl($this->config->bucket, $object, $expiration, strtoupper($method));
        return $url;
    }

    /**
     * 删除文件,不存在由返回false,删除失败则异常
     * @param string $object 文件对象或对象url
     */
    protected function internalDeleteFile($object)
    {
        $client = $this->getClient();
        if (!$client->doesObjectExist($this->config->bucket, $object)) {
            return false;
        }

        $client->deleteObject($this->config->bucket, $object);
    }

    /**
     * 下载文件到本地文件
     * @param string $object 文件对象或对象url
     * @param string $file 本地文件路径,使用相对路径或文件名时则保存到配置downloadDir指定的目录下
     * @return string
     */
    protected function internalDownloadFile($object, $file)
    {
        $client = $this->getClient();
        $this->checkObject($object, 'exists');
        $options = [OssClient::OSS_FILE_DOWNLOAD => $file];
        $client->getObject($this->config->bucket, $object, $options);
        return realpath($file);
    }

    /**
     * 获取文件内容
     * @param string $object 文件对象或对象url
     * @return string
     */
    protected function internalGetFileContent($object)
    {
        $this->checkObject($object, 'exists');
        $client = $this->getClient();
        $content = $client->getObject($this->config->bucket, $object);
        return $content;
    }

    /**
     * 生成sts凭证
     * @param array $stsConfig
     * @return array
     */
    protected function internalCreateStsCredentials($stsConfig)
    {
        AlibabaCloud::accessKeyClient($this->config->accessId, $this->config->accessKey)->asDefaultClient();
        $client = Sts::v20150401()->assumeRole()
                ->withRoleArn($stsConfig['RoleArn'])
                ->withRoleSessionName($stsConfig['RoleSessionName'])
                ->withDurationSeconds($stsConfig['DurationSeconds']);

        if (key_exists('Policy', $stsConfig) && $stsConfig['Policy']) {
            $client->withPolicy($stsConfig['Policy']);
        }

        $client->regionId($stsConfig['RegionId']);
        $credentials = $client->connectTimeout(60)->timeout(60)->request();
        date_default_timezone_set("PRC");
        $result = [
            'AccessKeyId' => $credentials['Credentials']['AccessKeyId'],
            'AccessKeySecret' => $credentials['Credentials']['AccessKeySecret'],
            'Expiration' => strtotime($credentials['Credentials']['Expiration']),
            'SecurityToken' => $credentials['Credentials']['SecurityToken']
        ];

        return  $result;
    }

    /**
     * 生成post签名
     * https://help.aliyun.com/document_detail/31926.html?spm=a2c4g.11186623.6.1559.47847eaeTQTd7s
     * https://help.aliyun.com/document_detail/31988.html?spm=a2c4g.11186623.6.1667.13af6e28K5HR4D
     * @param int $expiration
     * @param array $conditions
     * @return array
     */
    protected function internalCreateFormSignature($expiration, $startWith, $conditions)
    {
        $now = time();
        $end = $now + $expiration;
        $expiration = $this->gmtIso8601($end);
        $arr = ['expiration' => $expiration,'conditions' => $conditions];
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->config->accessKey, true));
        $urlComponents = parse_url($this->getEndpoint(false));
        $result = [
            'action' => sprintf('%s://%s.%s', $urlComponents['scheme'], $this->config->bucket, $urlComponents['host']),
            'OSSAccessKeyId' => $this->config->accessId,
            'policy' => $base64_policy,
            'signature' => $signature,
            'expire' => $end,
            'dir' => $startWith
        ];

        return $result;
    }

    /**
     * 转换成 ISO 8601 UTC日期
     * @param int $time
     * @return string
     */
    private function gmtIso8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }

    /**
     * 把objecturl地址转换为object
     * @param string $objectUrl
     * @return string
     */
    protected function parseObjectFormat($objectUrl)
    {
        if (preg_match("/^(http|https):/Ui", $objectUrl)) {
            $objectUrl = parse_url($objectUrl, PHP_URL_PATH);
        }

        if (strpos($objectUrl, '/') === 0) {
            $objectUrl = substr($objectUrl, 1);
        }

        return $objectUrl;
    }

    /**
     * 检测对象文件,当不满足指定检测命令时则抛异常
     * @param string $object
     * @throws FileStorageException
     */
    private function checkObject($object, $command)
    {
        $client = $this->getClient();
        switch ($command) {
            case 'exists':
                $exists = $client->doesObjectExist($this->config->bucket, $object);
                if (!$exists) {
                    throw new FileStorageException("文件对象不存在");
                }
                break;
            case 'notexists':
                $exists = $client->doesObjectExist($this->config->bucket, $object);
                if ($exists) {
                    throw new FileStorageException("文件对象已存在");
                }
                break;
            default:
                throw new FileStorageException("不支持的check-commnad:{$command}");
        }
    }
}
