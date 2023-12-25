<?php

namespace iFile\base;

/**
 * 存储公共配置
 *
 * @author YashonLvan
 */
class IFileConfiguration
{
    /**
     * 属性构造器
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }
    }
    
    /**
     * 简单上传是否自动分片,默认自动分片
     * @var bool 
     */
    public $autoChunk = true;

    /**
     * 分片上传块大小,允许值5M~5G,默认10M
     * @var int
     */
    public $chunkSize = 10485760;
    
    /**
     * 默认下载本地目录
     * @var string
     */
    public $downloadDir = '@runtime';
}
