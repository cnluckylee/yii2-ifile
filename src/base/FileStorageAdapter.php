<?php

namespace iFile\base;

use iFile\exceptions\FileStorageException;
use iFile\Options;

/**
 * 抽象存储设备适配器
 *
 * @author ray
 */
abstract class FileStorageAdapter implements \iFile\interfaces\FileStorageInterface
{    
    /**
     * 存储配置
     * @var IFileConfiguration
     */
    public $config;
    
    /**
     * 实例化
     * @param IFileConfiguration $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * 简单文件上传, 上传成功返回访问地址
     * @param string $file 本地文件路径
     * @param string $object 文件对象,为空时取本地文件名
     * @param bool $override 存在时是否覆盖
     * @return string
     */
    public function uploadFile($file, $object = '', $override = false)
    {
        $beginTs = microtime(true);
        $this->useUploadfile($file, $object);
        $this->checkFileExists($file);
        $object = $object ?: $this->getFileName($file);
        $msgArgs = ['type' => $this->getType(), 'file' => $file, 'object' => $object, 'filesize' => filesize($file)];
        try {
            $this->infoMsg('开始上传文件', $msgArgs);
            $result = $this->internalUploadFile($file, $object, $override);
            $this->infoMsg('上传文件成功', $msgArgs, $beginTs);
            return $result;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('上传文件出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }
    }
    
    /**
     * 分片上传, 上传成功返回访问地址
     * @param string $file 本地文件路径
     * @param string $object 文件对象
     * @param bool $override 存在时是否覆盖
     * @return string
     */
    public function multiUploadFile($file, $object = '', $override = false)
    {
        $beginTs = microtime(true);
        $this->useUploadfile($file, $object);
        $this->checkFileExists($file);
        $object = $object ?: $this->getFileName($file);
        $msgArgs = ['type' => $this->getType(), 'file' => $file, 'object' => $object, 'filesize' => filesize($file)];
        try {
            $this->infoMsg('开始分片上传文件', $msgArgs);
            $result = $this->internalMultiUploadFile($file, $object, $override);
            $this->infoMsg('分片上传文件成功', $msgArgs, $beginTs);
            return $result;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('分片上传文件出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }   
    }
    
    /**
     * 上传内容
     * @param string $content 文件内容
     * @param string $object 文件对象
     * @param bool $override 存在时是否覆盖
     * @param Options $options 上传选项
     * @return string
     */
    public function uploadContent($content, $object, $override = false, $options = null)
    {
        try {
            $beginTs = microtime(true);
            $object = $this->parseObjectFormat($object);
            $msgArgs = ['type' => $this->getType(), 'object' => $object, 'options' => $options];
            $this->infoMsg('开始上传文件内容', $msgArgs);
            $options = is_null($options) ? [] : $options->toArray($this->getType());
            $result = $this->internalUploadContent($content, $object, $override, $options);
            $this->infoMsg('上传文件内容成功', $msgArgs, $beginTs);
            return $result;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('上传文件内容出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }
    }
    
    /**
     * 上传目录
     * @param string $localDirectory 本地目录路径
     * @param string $prefix 前缀
     * @param bool $recursive 是否包括子目录文件,默认不包括
     * @param string $exclude 要排除的目录
     * @return array array("succeededList" => array("object"), "failedList" => array("object"=>"errorMessage"))
     * @throws FileStorageException
     */
    public function uploadDirectory($localDirectory, $prefix = '', $recursive = false, $exclude = '.|..|.svn|.git')
    {
        try {
            $beginTs = microtime(true);
            $msgArgs = ['type' => $this->getType(), 'localDirectory' => $localDirectory, 'prefix' => $prefix, 'exclude' => $exclude, 'recursive' => $recursive];
            $this->infoMsg('开始上传目录文件', $msgArgs);
            $result = $this->internalUploadDirectory($localDirectory, $prefix, $recursive, $exclude);
            $this->infoMsg('上传目录成功', $msgArgs, $beginTs);
            return $result;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('上传目录出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }
    }

    /**
     * 获取文件访问地址
     * @param string $object 文件对象或对象url
     * @param string $method 对象操作命令
     * @param int $expiration 过期时间(单位:秒)
     * @return string
     */
    public function getFileUrl($object, $method = 'GET', $expiration = 60)
    {
        $method = strtoupper($method);
        if ($method != 'GET') {
            // 屏蔽掉预签名下载文件
            throw new \iFile\exceptions\FileStorageException("不支持的method参数值");
        }

        try {
            $beginTs = microtime(true);
            $object = $this->parseObjectFormat($object);
            $msgArgs = ['type' => $this->getType(), 'method' => $method, 'object' => $object];
            $url = $this->internalGetFileUrl($object, $method, $expiration);
            return $url;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('获取文件地址出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }
    }
    
    /**
     * 删除文件,不存在由返回false,删除失败则异常
     * @param string $object 文件对象或对象url
     */
    public function deleteFile($object)
    {
        try {
            $beginTs = microtime(true);
            $object = $this->parseObjectFormat($object);
            $msgArgs = ['type' => $this->getType(), 'object' => $object];
            $this->infoMsg('开始删除文件', $msgArgs);
            $result = $this->internalDeleteFile($object);
            $this->infoMsg('删除文件成功', $msgArgs, $beginTs);
            if ($result === false) {
                return false;
            }
        } catch (\Exception $ex) {
            $msg = $this->errMsg('删除文件出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }   
    }
    
    /**
     * 下载文件到本地文件
     * @param string $object 文件对象或对象url
     * @param string $file 本地文件路径,使用相对路径或文件名时则保存到配置downloadDir指定的目录下
     * @return string
     */
    public function downloadFile($object, $file = '')
    {
        try {
            $beginTs = microtime(true);
            $object = $this->parseObjectFormat($object);
            $file = $file ?: $object;
            $file = $this->createDownloadDir($file);
            $msgArgs = ['type' => $this->getType(), 'object' => $object, 'file' => $file];
            $this->infoMsg('开始下载文件', $msgArgs);
            $result = $this->internalDownloadFile($object, $file);
            $this->infoMsg('下载文件成功', $msgArgs, $beginTs);
            return $result;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('下载文件出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }
    }
    
    /**
     * 获取文件内容
     * @param string $object 文件对象或对象url
     * @return string
     */
    public function getFileContent($object)
    {
        try {
            $beginTs = microtime(true);
            $object = $this->parseObjectFormat($object);
            $msgArgs = ['type' => $this->getType(), 'object' => $object];
            $this->infoMsg('开始获取文件内容', $msgArgs);
            $result = $this->internalGetFileContent($object);
            $this->infoMsg('获取文件内容成功', $msgArgs, $beginTs);
            return $result;
        } catch (\Exception $ex) {
            $msg = $this->errMsg('获取文件内容出错', $msgArgs, $beginTs, $ex);
            throw new FileStorageException($msg, $ex);
        }
    }
    
    
    /**
     * 解析文件对象名,兼容多种参数传入
     * @param string $object
     * @return string
     */
    protected function parseObjectFormat($object)
    {
        return $object;
    }
    
    /**
     * 校验本地文件是否存在
     * @param string $file 本地文件路径
     * @throws FileStorageException
     */
    protected function checkFileExists($file)
    {
        if (!file_exists($file)) {
            throw new FileStorageException("本地文件不存在{$file}");
        }
    }
    
    /**
     * 获取文件名
     * @param string $file 本地文件路径
     * @param bool $extension 是否带扩展名
     * @return string
     */
    protected function getFileName($file, $extension = true)
    {
        $filename = basename($file);
        if ($extension) {
            return $filename;
        }
        
        $pos = strrpos($filename, '.');
        return $pos === false ? $filename : substr($filename, 0, $pos);
    }
    
    /**
     * 获取分片大小
     * @return int
     */
    protected function getChunkSize()
    {
        if ($this->config->chunkSize > $this->getMaxChunkSize()) {
            return $this->getMaxChunkSize();
        }
        
        if ($this->config->chunkSize < $this->getMinChunkSize()) {
            return $this->getMinChunkSize();
        }
        
        return $this->config->chunkSize;
    }
    
    /**
     * 自动创建下载目录,返回完整的路径名
     * @param string $file 要保存的文件路径
     * @return string
     */
    protected function createDownloadDir($file)
    {   
        $dir = dirname($file);
        $dir = strpos($file, '/') === 0 ? $dir : sprintf('%s%s', $this->config->downloadDir, $dir == '.' ? '' : "/{$dir}");
        $dir = \Yii::getAlias($dir);
        if (!file_exists($dir)) {
            \yii\helpers\FileHelper::createDirectory($dir, 0755, true);
        }
        
        return $dir . '/' . basename($file);
    }
    
    /**
     * 使用UploadedFile实例上传
     * @param string $file
     * @param string $object
     */
    protected function useUploadfile(&$file, &$object)
    {
        if ($file instanceof \yii\web\UploadedFile) {
            $object = $object ?: $file->name;
            $file = $file->tempName;
        }
    }
    
    /**
     * 写入info日志
     * @param string $msg
     * @param array $options
     * @param float $beginTs
     * @return string
     */
    protected function infoMsg($msg, $options = [], $beginTs = '')
    {
        $message = $this->buildLogMsg($msg, $options, $beginTs);
        \yii::info($message, 'iFile');
        return $message;
    }
    
    /**
     * 写入error日志
     * @param string $msg
     * @param array $options
     * @param float $beginTs
     * @param \Exception $exception
     * @return string
     */
    protected function errMsg($msg, $options = [], $beginTs = '', $exception = null)
    {
        $message = $this->buildLogMsg($msg, $options, $beginTs);
        if ($exception) {
            $message = $message . ',详情信息:' . $exception->getMessage();
        }
        
        \yii::error($message, 'iFile');
        return $message;
    }
    
    /**
     * 构建日志消息格式
     * @param string $msg
     * @param array $options
     * @param float $beginTs
     * @return string
     */
    private function buildLogMsg($msg, $options, $beginTs)
    {
        $durationMsg = $beginTs ? sprintf(',耗时%.3f秒', microtime(true) - $beginTs) : '';
        $optionsMsg = '';
        if ($options) {
            $optionsMsg = implode(' ', array_map(function($k, $v) {
                $v = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
                return "$k:$v";
            }, array_keys($options), array_values($options)));
        }
        
        $optionsMsg = $optionsMsg ? "({$optionsMsg})" : '';
        return $msg . $optionsMsg . $durationMsg;
    }
    
    /**
     * 获取存储驱动类型
     */
    abstract public function getType();
    
    /**
     * 最大允许的分片大小
     */
    abstract protected function getMaxChunkSize();
    
    /**
     * 最小允许的分片大小
     */
    abstract protected function getMinChunkSize();
    
    /**
     * 文件上传
     */
    abstract protected function internalUploadFile($file, $object, $override);
    
    /**
     * 分片上传
     */
    abstract protected function internalMultiUploadFile($file, $object, $override);

    /**
     * 获取预签名url
     */
    abstract protected function internalGetFileUrl($object, $method, $expiration);
        
    /**
     * 删除文件
     */
    abstract protected function internalDeleteFile($object);
    
    /**
     * 下载文件
     */
    abstract protected function internalDownloadFile($object, $file);
    
    /**
     * 获取文件内容
     */
    abstract protected function internalGetFileContent($object);
    
    /**
     * 上传文件内容
     */
    abstract protected function internalUploadContent($content, $object, $override, $options);
    
    /**
     * 上传目录
     */
    abstract protected function internalUploadDirectory($localDirectory, $prefix, $recursive, $exclude);
}
