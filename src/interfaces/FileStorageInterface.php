<?php

namespace iFile\interfaces;

use iFile\Options;

/**
 * 存储驱动接口
 * @author YashonLvan
 */
interface FileStorageInterface
{
    /**
     * 简单文件上传, 上传成功返回访问地址
     * @param string $file 本地文件路径
     * @param string $object 文件对象
     * @param bool $override 存在时是否覆盖
     * @return string
     */
    public function uploadFile($file, $object = '', $override = false);

    /**
     * 分片上传, 上传成功返回访问地址
     * @param string $file 本地文件路径
     * @param string $object 文件对象
     * @param bool $override 存在时是否覆盖
     * @return string
     */
    public function multiUploadFile($file, $object = '', $override = false);
    
    /**
     * 上传内容
     * @param string $content 文件内容
     * @param string $object 文件对象
     * @param bool $override 存在时是否覆盖
     * @param Options $options 上传选项
     * @return string
     */
    public function uploadContent($content, $object, $override = false, $options = null);
    
    /**
     * 获取文件访问地址
     * @param string $object 文件对象
     * @param string $method 对象操作命令
     * @param int $expiration 过期时间(单位:秒)
     * @return string
     */
    public function getFileUrl($object, $method = 'GET', $expiration = 60);

    /**
     * 删除文件
     * @param string $object 文件对象
     */
    public function deleteFile($object);

    /**
     * 下载文件到本地文件
     * @param string $object 文件对象
     * @param string $file 本地文件路径,未指定时则使用object的路径
     * @return string
     */
    public function downloadFile($object, $file = '');
    
    /**
     * 读取文件内容
     * @param string $object
     */
    public function getFileContent($object);
}
