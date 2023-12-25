<?php

namespace iFile\huaweiobs;

use iFile\base\S3StorageAdapter;
use Obs\ObsClient;
use iFile\exceptions\FileStorageException;
use Obs\ObsException;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Client;

/**
 * 华为obs适配器
 *
 * @see https://support.huaweicloud.com/api-obs_php_sdk_api_zh/obs_php_sdk_api_zh-api.pdf
 */
class ObsAdapter extends S3StorageAdapter
{
    const ADAPTER_TYPE = 'huaweiobs';

    /**
     * 存储配置
     * @var ObsConfiguration
     */
    public $config;

    /**
     * Obs客户端
     * @var ObsClient
     */
    private $client = null;

    /**
     * Obs客户端(内网)
     * @var ObsClient
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
     * 最小允许的分片大小 100K
     * @return int
     */
    protected function getMinChunkSize()
    {
        return 102400;
    }

    /**
     * 获取Obs客户端
     * @param bool $internal 是否使用内部网络
     * @return ObsClient
     * @throws ObsException
     */
    protected function getClient($internal = true)
    {
        if (is_null($this->client)) {
            $endpoint = $this->getEndpoint(false);
            $this->client = new ObsClient([
                'key' => $this->config->accessId,
                'secret' => $this->config->accessKey,
                'endpoint' => $endpoint,
            ]);
        }

        if (is_null($this->intenalClient)) {
            $endpoint = $this->getEndpoint(true);
            $this->intenalClient = new ObsClient([
                'key' => $this->config->accessId,
                'secret' => $this->config->accessKey,
                'endpoint' => $endpoint,
            ]);
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

        $result = $client->putObject([
            'Bucket' => $this->config->bucket,
            'Key' => $object,
            'SourceFile' => $file,
        ]);
        return $result['ObjectURL'];
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

        try {
            // 创建分片任务
            $uploadResponse = $client->initiateMultipartUpload([
                'Bucket' => $this->config->bucket,
                'Key' => $object,
            ]);
            $uploadId = $uploadResponse['UploadId'];

            // 上传分片
            $handle = fopen($file, 'r');
            $chunkSize = $this->getChunkSize();
            $partNumber = 1;
            $parts = [];

            while (!feof($handle)) {
                $content = fread($handle, $chunkSize);

                $partResponse = $client->uploadPart([
                    'Bucket' => $this->config->bucket,
                    'Key' => $object,
                    'PartNumber' => $partNumber,
                    'UploadId' => $uploadId,
                    'Body' => $content,
                ]);

                $parts []= [
                    'PartNumber' => $partNumber,
                    'ETag' => $partResponse['ETag'],
                ];
                $partNumber += 1;
            }

            $response = $client->completeMultipartUpload([
                'Bucket' => $this->config->bucket,
                'Key' => $object,
                'UploadId' => $uploadId,
                'Parts' => $parts,
            ]);

            /**
             * 返回完整链接地址: bucket + endpoint + objectName
             * 目前location格式不符合要求。单独拼接对象地址
             * @see https://support.huaweicloud.com/api-obs/obs_04_0102.html#obs_04_0102__table32583578
             */
            $endpoint = str_replace(['http://', 'https://'], '', rtrim($this->getEndpoint(false), '/'));
            $url = vsprintf('https://%s.%s/%s', [
                $this->config->bucket,
                $endpoint,
                $response['Key'],
            ]);

            return $url;
        } catch (ObsException $e) {
            $res = $client->abortMultipartUpload([
                'Bucket' => $this->config->bucket,
                'Key' => $object,
                'UploadId' => $uploadId,
            ]);
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

        $result = $client->putObject([
            'Bucket' => $this->config->bucket,
            'Key' => $object,
            // body 同时支持文本和 Resource
            'Body' => $content,
        ]);
        return $result['ObjectURL'];
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
        $response = $client->createSignedUrl([
            'Method' => strtoupper($method),
            'Bucket' => $this->config->bucket,
            'Key' => urldecode($object),
            'Expires' => $expiration,
        ]);

        return $response['SignedUrl'];
    }

    /**
     * 删除文件,不存在由返回false,删除失败则异常
     * @param string $object 文件对象或对象url
     */
    protected function internalDeleteFile($object)
    {
        $client = $this->getClient();
        if (!$this->doesObjectExist($object)) {
            return false;
        }

        $client->deleteObject([
            'Bucket' => $this->config->bucket,
            'Key' => $object,
        ]);
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
        $client->getObject([
            'Bucket' => $this->config->bucket,
            'Key' => $object,
            'SaveAsFile' => $file,
        ]);
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
        $resp = $client->getObject([
            'Bucket' => $this->config->bucket,
            'Key' => $object,
            'SaveAsStream' => true,
        ]);

        /**
         * @var Stream
         */
        $stream = $resp['Body'];
        return $stream->getContents();
    }

    /**
     * 生成sts凭证
     * @param array $stsConfig
     * @return array
     */
    protected function internalCreateStsCredentials($stsConfig)
    {
        $token = $this->getIAMToken($stsConfig);

        $curl = new Client;

        $response = $curl->request(
            'POST',
            'https://iam.myhuaweicloud.com/v3.0/OS-CREDENTIAL/securitytokens',
            [
                'json' => [
                    'auth' => [
                        'identity' => [
                            'methods' => [
                                'token',
                            ],
                            'token' => [
                                'duration_seconds' => $stsConfig['DurationSeconds'],
                            ]
                        ]
                    ]
                ],
                'headers' => [
                    'X-Auth-Token' => $token,
                ],
            ]
        );

        $contentStr = $response->getBody()->getContents();
        $content = json_decode($contentStr, true);

        $result = [
            'AccessKeyId' => $content['credential']['access'],
            'AccessKeySecret' => $content['credential']['secret'],
            'Expiration' => strtotime($content['credential']['expires_at']),
            'SecurityToken' => $content['credential']['securitytoken']
        ];

        return  $result;
    }

    /**
     * 生成post签名
     * @param int $expiration
     * @param array $conditions
     * @return array
     */
    protected function internalCreateFormSignature($expiration, $startWith, $conditions)
    {
        $urlComponents = parse_url($this->getEndpoint(false));

        $client = $this->getClient();
        $response = $client->createPostSignature([
            'Bucket' => $this->config->bucket,
            'Expires' => $expiration,
            'FormParams' => [
                'x-obs-acl' => ObsClient::AclPublicRead,
            ],
        ]);

        $result = [
            'action' => sprintf('%s://%s.%s', $urlComponents['scheme'], $this->config->bucket, $urlComponents['host']),

            'policy' => $response['Policy'],
            'AccessKeyId' => $this->config->accessId,
            'signature' => $response['Signature'],

            'Expires' => $expiration,
        ];

        return $result;
    }

    private function getIAMToken($stsConfig)
    {
        $cacheKey = "global:config:obs-token";
        $token = \yii::$app->cache->get($cacheKey);

        if (empty($token)) {
            $curl = new Client;
            $response = $curl->request(
                'POST',
                'https://iam.myhuaweicloud.com/v3/auth/tokens?nocatalog=true',
                [
                    'json' => [
                        "auth" => [
                            "identity" => [
                                "methods" => [
                                    "password"
                                ],
                                "password" => [
                                    "user" => [
                                        "domain" => [
                                            "name" => $stsConfig['domain']
                                        ],
                                        "name" => $stsConfig['name'],
                                        "password" => $stsConfig['password']
                                    ]
                                ]
                            ],
                            "scope" => [
                                "domain" => [
                                    "name" => $stsConfig['domain']
                                ]
                            ]
                        ]
                    ]
                ]
            );

            $tokenList = $response->getHeader('X-Subject-Token');
            $token = $tokenList[0];

            \yii::$app->cache->set($cacheKey, $token, 7200);
        }

        return $token;
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
        switch ($command) {
            case 'exists':
                $exists = $this->doesObjectExist($object);
                if (!$exists) {
                    throw new FileStorageException("文件对象不存在");
                }
                break;
            case 'notexists':
                $exists = $this->doesObjectExist($object);
                if ($exists) {
                    throw new FileStorageException("文件对象已存在");
                }
                break;
            default:
                throw new FileStorageException("不支持的check-commnad:{$command}");
        }
    }

    /**
     * 通过获取对象的元数据，判断是否存在
     * @return bool
     */
    private function doesObjectExist($key)
    {
        try {
            $this->getClient()->getObjectMetadata([
                'Bucket' => $this->config->bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (ObsException $e) {
            return false;
        }
    }
}
