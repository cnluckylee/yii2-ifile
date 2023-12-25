<?php

namespace iFile\minio;

use iFile\base\S3StorageAdapter;
use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use iFile\exceptions\FileStorageException;

/**
 * minIO存储适配器
 *
 * @author ray
 */
class MinioAdapter extends S3StorageAdapter
{
    const ADAPTER_TYPE = 'minio';

    /**
     * 存储配置
     * @var MinioConfiguration
     */
    public $config;

    /**
     * minio客户端
     * @var S3Client
     */
    private $client = null;

    /**
     * minio客户端(内网)
     * @var S3Client
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
     * 最大允许的分片大小:5G
     * @return int
     */
    protected function getMaxChunkSize()
    {
        return 5368709120;
    }

    /**
     * 最小允许的分片大小:5M
     * @return int
     */
    protected function getMinChunkSize()
    {
        return 5242880;
    }

    /**
     * 获取Minio客户端
     * @param bool $internal 是否使用内部网络
     * @return S3Client
     */
    protected function getClient($internal = true)
    {
        if (is_null($this->client)) {
            $endpoint = $this->getEndpoint(false);
            $this->client = new S3Client([
                'version' => $this->config->version,
                'region' => '',
                'use_path_style_endpoint' => $this->config->usePathStyleEndpoint,
                'signature_version' => $this->config->signatureVersion,
                'endpoint' => $endpoint,
                'credentials' => ['key' => $this->config->accessId, 'secret' => $this->config->accessKey]]);
        }

        if (is_null($this->intenalClient)) {
            $endpoint = $this->getEndpoint(true);
            $this->intenalClient = $this->client = new S3Client([
                'version' => $this->config->version,
                'region' => '',
                'use_path_style_endpoint' => $this->config->usePathStyleEndpoint,
                'signature_version' => $this->config->signatureVersion,
                'endpoint' => $endpoint,
                'credentials' => ['key' => $this->config->accessId, 'secret' => $this->config->accessKey]]);
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
        if ($this->config->autoChunk && $fileSize > $this->getChunkSize()) {
            return $this->multiUploadFile($file, $object, $override);
        }

        $result = $client->putObject(['Bucket' => $this->config->bucket, 'Key' => $object, 'SourceFile' => $file]);
        return $result->get('ObjectURL');
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
        $uploader = new MultipartUploader($client, $file, [
            'bucket' => $this->config->bucket,
            'key' => $object,
            'part_size' => $this->getChunkSize()
        ]);

        $result = $uploader->upload();
        return $result->get('ObjectURL');
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
        return $result['ObjectURL'];
    }

    /**
     * 获取文件访问地址
     * @param string $object 文件对象或对象url
     * @param string $method 对象操作命令
     * @param int $expiration 过期时间(单位:秒)
     * @return string
     */
    protected function internalGetFileUrl($object, $method, $expiration)
    {
        $commandName = $this->getApiCommandName($method);
        $client = $this->getClient(false);
        $command = $client->getCommand($commandName, [
            'Bucket' => $this->config->bucket,
            'Key'    => $object
        ]);
        $request = $client->createPresignedRequest($command, sprintf("+%d seconds", $expiration));
        $url = (string)$request->getUri();
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

        $client->deleteObject(['Bucket' => $this->config->bucket, 'Key' => $object]);
    }

    /**
     * 下载文件到本地文件
     * @param string $object 文件对象或对象url
     * @param string $file 本地文件路径,使用相对路径或文件名时则保存到配置downloadDir指定的目录下
     * @return string
     */
    protected function internalDownloadFile($object, $file)
    {
        $this->checkObject($object, 'exists');
        $client = $this->getClient();
        $client->getObject(['Bucket' => $this->config->bucket, 'Key' => $object, 'SaveAs' => $file]);
        return realpath($file);
    }

    /**
     * 获取文件内容
     * @param string $object 文件对象或对象url
     * @return type
     */
    protected function internalGetFileContent($object)
    {
        $client = $this->getClient();
        $this->checkObject($object, 'exists');
        $result = $client->getObject(['Bucket' => $this->config->bucket, 'Key' => $object]);
        $content = (string)$result['Body'];
        return $content;
    }

    /**
     * 生成sts凭证
     * @param array $stsConfig
     * @return array
     */
    protected function internalCreateStsCredentials($stsConfig)
    {
        // 有效范围15分钟~12小时
        if ($stsConfig['DurationSeconds'] > 43200) {
            $stsConfig['DurationSeconds'] = 43200;
        }

        $endpoint = $this->getEndpoint(true);
        $stsClient = new \Aws\Sts\StsClient([
                'version' =>  '2011-06-15',
                'region' => '',
                'use_path_style_endpoint' => $this->config->usePathStyleEndpoint,
                'signature_version' => $this->config->signatureVersion,
                'endpoint' =>  $endpoint,
                'credentials' => ['key' => $this->config->accessId, 'secret' => $this->config->accessKey]]);

        $assumeRoleResult = $stsClient->assumeRole($stsConfig);
        $credentials = $stsClient->createCredentials($assumeRoleResult);
        $result = [
            'AccessKeyId' => $credentials->getAccessKeyId(),
            'AccessKeySecret' => $credentials->getSecretKey(),
            'Expiration' => $credentials->getExpiration(),
            'SecurityToken' => $credentials->getSecurityToken()
        ];

        return $result;
    }

    /**
     * 生成post签名
     * https://docs.aws.amazon.com/AmazonS3/latest/dev/HTTPPOSTExamples.html
     * @param int $expiration
     * @param array $conditions https://docs.aws.amazon.com/AmazonS3/latest/dev/HTTPPOSTForms.html
     * @return array
     */
    protected function internalCreateFormSignature($expiration, $startWith, $conditions)
    {
        $client = $this->getClient();
        $formInputs = [];
        $postObject = new \Aws\S3\PostObjectV4($client, $this->config->bucket, $formInputs, $conditions, $expiration);
        $formInputs = $postObject->getFormInputs();
        date_default_timezone_set("PRC");
        $result = [
            'action' => sprintf('%s/%s', $this->getEndpoint(false), $this->config->bucket),
            'AWSAccessKeyId' => $this->config->accessId,
            'policy' => $formInputs['Policy'],
            'signature' => $formInputs['X-Amz-Signature'],
            'expire' => strtotime($formInputs['X-Amz-Date']),
            'dir' => $startWith
        ];

        return $result;
    }

    /**
     * 把objecturl地址转换为object
     * @param string $objectUrl
     * @return string
     */
    protected function parseObjectFormat($objectUrl)
    {
        if (preg_match("/^(http|https):/Ui", $objectUrl)) {
            $path = parse_url($objectUrl, PHP_URL_PATH);
            $objectUrl = preg_replace("/\/{$this->config->bucket}\//Ui", '', $path, 1);
        }

        return $objectUrl;
    }

    /**
     * 检测对象文件,当不满足指定检测命令时则抛异常
     * @param string $object
     * @throws FileStorageException
     */
    protected function checkObject($object, $command)
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

    private function getApiCommandName($method)
    {
        $method = strtoupper($method);
        $commandList = [
            'GET' => 'GetObject',
            'PUT' => 'PutObject'
        ];

        if (!key_exists($method, $commandList)) {
            throw new \iFile\exceptions\FileStorageException("不支持的method参数值");
        }

        return $commandList[$method];
    }
}
