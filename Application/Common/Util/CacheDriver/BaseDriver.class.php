<?php

namespace Common\Util\CacheDriver;

/**
 * 缓存基类.
 *
 */
abstract class BaseDriver
{
    protected $cacheDir; // 缓存路径

    /**
     * 初始化时设置缓存路径.
     *
     * @param string $dir 路径信息
     */
    public function __construct($dir)
    {
        $this->cacheDir = $dir;
    }

    /**
     * 根据缓存名获取缓存内容.
     *
     * @param string $name 缓存名
     */
    abstract public function _get($name);

    /**
     * 根据缓存名 设置缓存值和超时时间.
     *
     * @param string      $name    缓存名
     * @param string|void $value   缓存值
     * @param int         $expires 超时时间
     *
     * @return boolean;
     */
    abstract public function _set($name, $value, $expires);
}
