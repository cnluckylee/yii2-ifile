# 声明
源码来自明源云开放代码库，感谢明源云的开源精神，感谢作者chenxy <chenxy@mingyuanyun.com>
# iFile For Yii2.0
提供统一的文件存储客户端编程模型，支持简单上传、分片上传、下载、文件内容查看、STS、预签名等。目前已支持MinIO，阿里OSS，招商COS，华为OBS。

### 安装方法
------------
```shell
composer require cnluckylee/yii2-ifile
```
### 配置
------------
在配置文件中components中添加iFile组件
### 阿里OSS
```php
'iFile' => [
    'class' => 'iFile\FileStorage',
    'type' => 'alioss',
    'config' => [
        'endPoint' => 'https://oss-cn-shenzhen.aliyuncs.com',
        'accessId' => 'accessId',
        'accessKey' => 'accessKey',
        'bucket' => 'mybucket',
        'allowBuckets' => ['mybucket1', 'mybucket2'],
        'autoChunk' => true,
        'chunkSize' => 10*1024*1024
        'downloadDir' => '@runtime',
        'sts' => [  // 使用sts时设置此项
             //详情:https://help.aliyun.com/document_detail/28763.html?spm=a2c4g.11186623.6.796.a0683b09XmOJWd
            'RegionId' => 'cn-shenzhen',
            'RoleArn' => 'acs:ram::1409041017619204:role/aliyunosstokengeneratorrole',
            'RoleSessionName' => 'client-name',
            'DurationSeconds' => 3600,
            'Policy' => @'{
                    "Statement": [
                      {
                        "Action": [
                          "oss:*"
                        ],
                        "Effect": "Allow",
                        "Resource": ["acs:oss:*:*:mybucket/imgs/*"]
                      }
                    ],
                    "Version": "1"
                  }'
            ]
    ],
],
```

### minIO
```php
'iFile' => [
    'class' => 'iFile\FileStorage',
    'type' => 'minio',
    'config' => [
        'endPoint' => 'https://minio-test.mysre.cn',
        'accessId' => 'accessId',
        'accessKey' => 'accessKey',
        'bucket' => 'mybucket',
        'allowBuckets' => ['mybucket1', 'mybucket2'],
        'autoChunk' => true,
        'chunkSize' => 10*1024*1024
        'downloadDir' => '@runtime',
        'sts' => [ // 使用sts时设置此项
             //详情:https://docs.aws.amazon.com/aws-sdk-php/v3/api//api-sts-2011-06-15.html#assumerole
            'RoleArn' => 'arn:aws:s3:xxxxxx:xxxxxx:xxxxx',
            'DurationSeconds' => 4000,
            'RoleSessionName' => 'client-name',
            'Policy' => '{
                "Version": "2012-10-17",
                "Statement": [
                 {
                  "Effect": "Allow",
                  "Action": [
                   "s3:*"
                  ],
                  "Resource": [
                   "arn:aws:s3:::*"
                  ]
                 }
                ]
               }}'
         ]
    ],
],
```
### 招商COS
```php
'iFile' => [
    'class' => 'iFile\FileStorage',
    'type' => 'gateway',
    'config' => [
        'endPoint' => 'https://gateway-test.mysre.cn',
        'accessId' => 'accessId',
        'accessKey' => 'accessKey',
        'bucket' => 'mybucket',
        'allowBuckets' => ['mybucket1', 'mybucket2'],
        'autoChunk' => true,
        'chunkSize' => 10*1024*1024
        'downloadDir' => '@runtime',
        'sts' => [ // 使用sts时设置此项
             //详情:https://docs.aws.amazon.com/aws-sdk-php/v3/api//api-sts-2011-06-15.html#assumerole
            'RoleArn' => 'arn:aws:s3:xxxxxx:xxxxxx:xxxxx',
            'DurationSeconds' => 4000,
            'RoleSessionName' => 'client-name',
            'Policy' => '{
                "Version": "2012-10-17",
                "Statement": [
                 {
                  "Effect": "Allow",
                  "Action": [
                   "s3:*"
                  ],
                  "Resource": [
                   "arn:aws:s3:::*"
                  ]
                 }
                ]
               }}'
         ]
    ],
],
```
### 华为云OBS
```php
'iFile' => [
    'class' => 'iFile\FileStorage',
    'type' => 'huaweiobs',
    'config' => [
        'endPoint' => 'https://obs.myhuaweicloud.com',
        'accessId' => 'accessId',
        'accessKey' => 'accessKey',
        'bucket' => 'bucket',
        'allowBuckets' => ['mybucket1', 'mybucket2'],
        'autoChunk' => true,
        'chunkSize' => 10*1024*1024,
        'downloadDir' => '@runtime',
        'sts' => [
            'domain' => 'domain',
            'name' => 'name',
            'password' => 'password',
            'DurationSeconds' => 4000
        ],
    ]
]
```

|  属性 | 含义  | 说明  |
| ------------ | ------------ |------------ |
|  type |  存储服务类型   | 必须
|  endPoint |  接入点   | 必须,支持内外网配置
|  accessId |  帐号id   | 必须
|  accessKey |  帐号key   | 必须
|  bucket |  存储桶   | 必须,默认操作的bucket
|  allowBuckets |  存储桶   | 可选,允许操作非默认bucket时的白名单列表,详情请查看操作非默认bucket
|  autoChunk |  自动分片   | 可选,默认为true,为true时调用非分片上传接口时,若上传文件大小超过chunkSize大小将自动分片,为false时则不分片
|  chunkSize |  分片大小   | 可选,默认10M,使用自动分片或使用分片上传接口时文件分片大小。值范围为5M~5G,当设置值不在此范围内时,将自动根据具体的存储类型转换为支持的最大或最小值。
|  downloadDir |  默认下载目录   | 可选,默认为@runtime,该配置为调用下载接口时,未指定本地存储路径时默认下载目录
|  sts |  sts配置   | 可选,当使用sts接口来生成临时token时需要配置该项
|  sts.RegionId |  接入区域ID   | OSS时必须
|  sts.RoleArn |  角色资源名称   | 必须, OSS的可找运维要, minIO和招商COS的配置上述示例值即可,因为对于minIO和招商COS该值只是为了适配AWS的SDK
|  sts.RoleSessionName |  角色会话名称   | 可选, 临时身份的会话名称，用于区分不同的临时身份
|  sts.DurationSeconds |  凭证有效期   | 可选, 单位:秒,默认3600秒后STS凭证过期,需要重新授权
|  sts.Policy |  策略   | 可选,设置权限策略以进一步限制角色的权限,未设置则使用owner角色策略
|  sts.domain |  IAM用户所属帐号名   | 可选, 华为云上传使用的过渡方案（联系运维获取该参数）
|  sts.name |  IAM用户名   | 可选, 华为云上传使用的过渡方案（联系运维获取该参数）
|  sts.password |  IAM用户密码   | 可选, 华为云上传使用的过渡方案（联系运维获取该参数）


### endPoint同时支持内外网配置如下:
```php
'endPoint' => [
    'public' => 'https://oss-cn-shenzhen.aliyuncs.com',
    'internal' => 'https://oss-cn-shenzhen-internal.aliyuncs.com'
]

'endPoint' => [
    'public' => 'https://minio-test.mysre.cn',
    'internal' => 'http://10.5.1.120:9001'
]
```
当配置了内网时,除获取预签名URL外,组件内部均使用内网进行文件的上传下载,建议使用内网配置,减少公网带宽占用,提高上传下载速度。

### 使用自定义配置,如配置在数据库中
```php
'iFile' => [
    'class' => 'iFile\FileStorage',
    'config' => 'services\MyFileConfig'
],
```
config配置为自定义的配置实现类,实现iFile\interfaces\IFileConfigurationInterface接口


### 简单上传
------------
```php
// 上传本地文件/tmp/myfile.txt到存储服务并命名为myfile123.txt
$objectUrl = \yii::$app->iFile->createClient()->uploadFile('/tmp/myfile.txt', 'myfile123.txt', true);
echo $objectUrl;

// 上传本地文件/tmp/myfile.txt到存储服务目录a/b/myfile123.txt
$objectUrl = \yii::$app->iFile->createClient()->uploadFile('/tmp/myfile.txt', 'a/b/myfile123.txt', true);
echo $objectUrl;

// 省略第二个参数时则取上传文件名作为参数值
// 上传本地文件/tmp/myfile.txt到存储服务并命名为myfile.txt
$objectUrl = \yii::$app->iFile->createClient()->uploadFile('/tmp/myfile.txt');
echo $objectUrl;

// 上传UploadedFile到存储服务并命名为myfile.zip
$uploadedFile = \yii\web\UploadedFile::getInstanceByName('myfile');
$objectUrl = \yii::$app->iFile->createClient()->uploadFile($uploadedFile, 'myfile.zip');
echo $objectUrl

// 上传$_FILES
$objectUrl = \yii::$app->iFile->createClient()->uploadFile($_FILES['myfile']['tmp_name'], 'myfile.zip');
echo $objectUrl
```
输出:

oss

<span>https://</span>mybucket.oss-cn-shenzhen.aliyuncs.com/myfile123.txt

<span>https://</span>mybucket.oss-cn-shenzhen.aliyuncs.com/a/b/myfile123.txt

<span>https://</span>mybucket.oss-cn-shenzhen.aliyuncs.com/myfile.txt

<span>https://</span>mybucket.oss-cn-shenzhen.aliyuncs.com/myfile.zip

minio

<span>https://</span>minio-test.mysre.cn/mybucket/myfile123.txt

<span>https://</span>minio-test.mysre.cn/mybucket/a/b/myfile123.txt

<span>https://</span>minio-test.mysre.cn/mybucket/myfile.txt

<span>https://</span>minio-test.mysre.cn/mybucket/myfile.zip

huaweiobs

<span>https://</span>mybucket.obs.cn-south-1.myhuaweicloud.com/myfile123.txt

<span>https://</span>mybucket.obs.cn-south-1.myhuaweicloud.com/a/b/myfile123.txt

<span>https://</span>mybucket.obs.cn-south-1.myhuaweicloud.com/myfile.txt

<span>https://</span>mybucket.obs.cn-south-1.myhuaweicloud.com/myfile.zip


### 分片上传
------------
```php
// 上传本地文件/tmp/myfile.zip到存储服务并命名为myfile123.zip
$objectUrl = \yii::$app->iFile->createClient()->multiUploadFile('/tmp/myfile.zip', 'myfile123.zip', true);

// 上传本地文件/tmp/myfile.txt到存储服务目录a/b/myfile123.zip
$objectUrl = \yii::$app->iFile->createClient()->multiUploadFile('/tmp/myfile.txt', 'a/b/myfile123.zip', true);

// 上传本地文件/tmp/myfile.zip到存储服务并命名为myfile.zip
$objectUrl = \yii::$app->iFile->createClient()->multiUploadFile('/tmp/myfile.zip');

// 上传UploadedFile
$uploadedFile = \yii\web\UploadedFile::getInstanceByName('myfile');
$objectUrl = \yii::$app->iFile->createClient()->multiUploadFile($uploadedFile, 'jpg.zip');

// 上传$_FILES
$objectUrl = \yii::$app->iFile->createClient()->multiUploadFile($_FILES['myfile']['tmp_name'], 'jpg.zip');
```

### 上传内容
------------
```php
$content = file_get_contents('/tmp/hello.txt');
$objectUrl = \yii::$app->iFile->createClient()->uploadContent($content, 'hello.txt');
```

### 上传目录
------------
```php
$objectUrl = \yii::$app->iFile->createClient()->uploadDirectory('/tmp/oss', 'dir', true);
```

输出:

```json
{
    "succeededList": [
        "dir/a/3.txt",
        "dir/a/2.txt",
        "dir/a/1.txt",
        "dir/3.txt",
        "dir/2.txt",
        "dir/1.txt",
        "dir/b/4.txt"
    ],
    "failedList": []
}
```

### 删除文件
------------
```php
// 删除上传的myfile123.txt
\yii::$app->iFile->createClient()->deleteFile('myfile123.txt');

// 删除上传的a/b/myfile123.zip
\yii::$app->iFile->createClient()->deleteFile('a/b/myfile123.zip');

// 参数也可以使用完整的url,如:
\yii::$app->iFile->createClient()->deleteFile('https://mybucket.oss-cn-shenzhen.aliyuncs.com/myfile123.txt');

\yii::$app->iFile->createClient()->deleteFile('https://minio-test.mysre.cn/mybucket/a/b/myfile123.zip');
```

### 获取文件内容
------------
```php
$content = \yii::$app->iFile->createClient()->getFileContent('myfile123.txt');
echo $content;

// 参数也可以使用完整的url,如:
\yii::$app->iFile->createClient()->getFileContent('https://mybucket.oss-cn-shenzhen.aliyuncs.com/myfile123.txt');
```

### 下载文件
------------
```php
// 下载myfile.txt并保存到本地 /tmp/file123.txt, 若本地文件存在则覆盖
$localFile = \yii::$app->iFile->createClient()->downloadFile('myfile.txt', '/tmp/file123.txt');
echo $localFile;

// 下载myfile.txt并保存到配置中的下载目录@runtime/txt/file.txt
$localFile = \yii::$app->iFile->createClient()->downloadFile('myfile.txt', 'txt/file.txt');
echo $localFile;

// 下载a/b/c/myfile.txt并保存到下载目录@runtime/a/b/c/myfile.txt
$localFile = \yii::$app->iFile->createClient()->downloadFile('a/b/c/myfile.txt');
echo $localFile;

// 第一个参数也可以使用完整的url,如:
$localFile = \yii::$app->iFile->createClient()->downloadFile('https://mybucket.oss-cn-shenzhen.aliyuncs.com/myfile.txt', '/tmp/file123.txt');
echo $localFile;
```
输出:假设@runtime对应的物理目录为/var/webser/app/runtime

/tmp/file123.txt

/var/webser/app/txt/file.txt

/var/webser/app/a/b/c/myfile.txt

/tmp/file123.txt


### 操作非默认bucket
------------
不指定bucket时则操作配置中的默认bucket

```php
 \yii::$app->iFile->createClient('mybucket1')->uploadFile($file);
 // 等同于
 \yii::$app->iFile->createClient()->useBucket('mybucket1')->uploadFile($file);
```


### 预签名接口
------------
当bucket设置为私有读/写的时候,前端(浏览器)访问时需要使用签名后的URL进行文件的上传/下载(查看),故组件提供了预签名接口

```php
// 获取带有签名的myfile.txt下载(查看)地址
$signUrl = \yii::$app->iFile->createClient()->getFileUrl('myfile.txt', 'GET', 60);
echo $signUrl;

// 获取带有签名的myfile.txt上传地址
$signUrl = \yii::$app->iFile->createClient()->getFileUrl('myfile.txt', 'PUT', 60);
echo $signUrl;
```

### STS
------------
```php
 $data = \yii::$app->iFile->createClient()->createStsCredentials();
 \Yii::$app->response->data = $data;
 \Yii::$app->end();
```

输出:

```json
{
    "AccessKeyId": "A1K7BCN4NFNB9OGVLBFZ",
    "AccessKeySecret": "0ahkcko11UJ0ADAkGy5H+a+cIOkw9hZr4tfL7+sy",
    "Expiration": 1590571780,
    "SecurityToken": "eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJhY2Nlc3NLZXkiOiJBMUs3QkNONE5GTkI5T0dWTEJGWiIsImV4cCI6MzYwMDAwMDAwMDAwMCwicG9saWN5IjoicmVhZHdyaXRlIn0.ztMYH5LvVmVzyntjkZUKdKp_PJhtU4M4U7n9CUafY8LHQtTYWktDbxWlIno9rRB95Ad3aEkSsIJPOOFkbJqkqg"
}
```

### 表单签名
------------
```php
 $data = \yii::$app->iFile->createClient()->createFormSignature();
 \Yii::$app->response->data = $data;
 \Yii::$app->end();
```

输出:

```json
{
    "action": "https://minio-test.mysre.cn/chenxy",
    "AWSAccessKeyId": "cxyadmin",
    "policy": "eyJleHBpcmF0aW9uIjoiMTk3MC0wMS0wMVQwMDoxMDowMFoiLCJjb25kaXRpb25zIjpbWyJjb250ZW50LWxlbmd0aC1yYW5nZSIsMCwxMDczNzQxODI0XSxbInN0YXJ0cy13aXRoIiwiJGtleSIsImFiYyJdLHsiWC1BbXotRGF0ZSI6IjIwMjAwNTI3VDA4NDk1MVoifSx7IlgtQW16LUNyZWRlbnRpYWwiOiJjeHlhZG1pblwvMjAyMDA1MjdcL1wvczNcL2F3czRfcmVxdWVzdCJ9LHsiWC1BbXotQWxnb3JpdGhtIjoiQVdTNC1ITUFDLVNIQTI1NiJ9XX0=",
    "signature": "68826f2c388a8fe685f148ddff04ec0531112df52bb83230eb553e766bb7ccc0",
    "expire": 1590569391,
    "dir": "abc"
}
```

更多请查看Demo