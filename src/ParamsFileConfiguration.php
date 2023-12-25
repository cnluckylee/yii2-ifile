<?php

namespace iFile;


/**
 * 使用参数文件配置
 * 
 * @author YashonLvan
 */
class ParamsFileConfiguration implements interfaces\IFileConfigurationInterface
{
    /**
     * 存储类型
     * @var string
     */
    protected $type;
    
    /**
     * 存储配置
     * @var array
     */
    protected $config;

    /**
     * 默认从参数配置文件中读取
     * @param string $type
     * @param array $config
     */
    public function __construct($type = '', $config = [])
    {
        if (empty($type)) {
            $params = $this->getParamValue();
            $type = $params['type'];
        }
        $this->type = $type;
        
        if (empty($config)) {
            $params = $this->getParamValue();
            $config = $params['config'];
        }
        $this->config = $config;
    }
    
    /**
     * 获取服务类型
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * 获取配置信息
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * 设置配置
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }


    protected function getParamValue()
    {
        return \yii::$app->params['iFile'];
    }
}
