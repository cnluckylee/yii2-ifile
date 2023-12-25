<?php

namespace iFile\base;

/**
 * S3标准存储配置
 *
 * @author ray
 */
class S3StorageConfiguration extends IFileConfiguration
{
    /**
     * accessId
     * @var string
     */
    public $accessId = '';
    
    /**
     * accessKey
     * @var string
     */
    public $accessKey = '';
    
    /**
     * endPoint  
     * 字符串表示公网地址如:https://abc.com
     * 数组可同时表示公网和内网
     * ['public' => 'https://abc.com', 'internal' => 'https://internal.abc.com']
     * 有内网配置时,会使用内网配置
     * @var string|array
     */
    public $endPoint = '';
    
    /**
     * 存储空间
     * @var string
     */
    public $bucket = '';
    
    /**
     * 允许操作的bucket
     * @var array
     */
    public $allowBuckets = [];

    /**
     * sts设置,如阿里oss: https://help.aliyun.com/document_detail/28763.html?spm=a2c4g.11186623.6.796.63e239afN757Kk
     * [
     *    'RoleArn' => 'acs:ram::151266687691****:role/cloud',
     *    'RoleSessionName' => 'client_name',
     *    'DurationSeconds' => 3600,
     *    'Policy' => '{
             "Statement":[
                {
                     "Action":
                 [
                     "oss:Get*",
                     "oss:List*"
                     ],
                      "Effect": "Allow",
                      "Resource": "*"
                }
                   ],
          "Version": "1"
        }'
     * ]
     * 
     * minio配置, https://docs.aws.amazon.com/aws-sdk-php/v3/api//api-sts-2011-06-15.html#assumerole
     * [
            'DurationSeconds' => <integer>,
            'ExternalId' => '<string>',
            'Policy' => '<string>',
            'PolicyArns' => [
                [
                    'arn' => '<string>',
                ],
                // ...
            ],
            'RoleArn' => '<string>', // REQUIRED
            'RoleSessionName' => '<string>', // REQUIRED
            'SerialNumber' => '<string>',
            'Tags' => [
                [
                    'Key' => '<string>', 
                    'Value' => '<string>',
                ],
                // ...
            ],
            'TokenCode' => '<string>',
            'TransitiveTagKeys' => ['<string>', ...],
        ]
     * @var array
     */
    public $sts = [];
}
