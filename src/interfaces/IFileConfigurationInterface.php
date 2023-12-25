<?php

namespace iFile\interfaces;

/**
 * 存储配置接口
 * @author YashonLvan
 */
interface IFileConfigurationInterface
{
    /**
     * 获取存储类型
     * @return string
     */
    public function getType();
    
    /**
     * 获取存储配置信息
     * @return array
     */
    public function getConfig();
    
    /**
     * 设置存储配置信息
     * @param array $config
     */
    public function setConfig($config);
}
