<?php
/**
 * Singleton Trait
 *
 * 为类提供单例模式的通用实现。
 * v2.0.23 新增 create() 静态方法，支持向 init() 传递构造参数。
 *
 * @version 2.0.23
 * @author Saiita AI Content Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Saiita_AI_Toolkit_Singleton Trait
 *
 * 使用方式：
 *
 *   // 无参单例（向后兼容）
 *   MyClass::get_instance();
 *
 *   // 带参创建（仅首次调用生效）
 *   MyClass::create(array('loader' => $loader));
 *
 * @since 2.0.0
 * @since 2.0.23 新增 create() 支持构造参数
 */
trait Saiita_AI_Toolkit_Singleton
{
    /**
     * 所有使用此 Trait 的类的实例存储
     *
     * @var array<string, object>
     */
    protected static $instances = array();

    /**
     * create() 传递的构造参数缓存
     *
     * @var array<string, array>
     */
    protected static $create_args = array();

    /**
     * 获取类的单例实例
     *
     * 无参调用，向后兼容。若需传参请使用 create()。
     *
     * @return static
     */
    public static function get_instance()
    {
        $class = get_called_class();

        if (!isset(static::$instances[$class])) {
            static::$instances[$class] = new $class();
        }

        return static::$instances[$class];
    }

    /**
     * 创建单例实例并传递参数
     *
     * 仅在首次调用时生效，后续调用返回已缓存的实例。
     *
     * @param array $args 传递给 init() 的参数
     * @return static
     */
    public static function create($args = array())
    {
        $class = get_called_class();

        if (!isset(static::$instances[$class])) {
            static::$create_args[$class] = $args;
            static::$instances[$class] = new $class();
        }

        return static::$instances[$class];
    }

    /**
     * 构造函数
     *
     * 声明为 protected 以防止直接实例化。
     * 子类可以重写以实现自定义构造逻辑，也可以重写 init() 来处理参数。
     */
    protected function __construct()
    {
        $class = get_called_class();
        $args = static::$create_args[$class] ?? array();
        $this->init($args);
    }

    /**
     * 初始化方法
     *
     * 子类可重写此方法以执行初始化逻辑。
     * 若通过 create() 创建，$args 包含传入的参数。
     *
     * @param array $args 构造参数（由 create() 传入）
     * @return void
     */
    protected function init($args = array())
    {
    }

    /**
     * 重置实例缓存（主要用于测试）
     *
     * @return void
     */
    public static function reset_instance()
    {
        $class = get_called_class();
        unset(static::$instances[$class], static::$create_args[$class]);
    }

    /**
     * 防止克隆实例
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * 防止反序列化
     *
     * @return void
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }
}
