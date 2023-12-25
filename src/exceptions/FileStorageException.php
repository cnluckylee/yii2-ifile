<?php

namespace iFile\exceptions;

/**
 * 存储异常类
 *
 * @author YashonLvan
 */
class FileStorageException extends \Exception
{
    public function __construct($message, $previous = null)
    {
        parent::__construct($message, $previous ? $previous->getCode() : 500, $previous);
    }
}
