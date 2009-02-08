<?php
/**
 * Type system for PHP.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the BSD license (2 Clause) that is
 * bundled with this package in the file LICENSE.
 *
 * @category   PHP
 * @package    Type
 * @version    0
 * @since      0
 * @author     double-o <@xif.jp>
 * @copyright  2009 double-o
 * @license    http://www.freebsd.org/copyright/freebsd-license.html
 *             BSD License (2 Clause)
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR
             . 'Type' . DIRECTORY_SEPARATOR . 'Exception.php';

/**
 * Base type class.
 */
abstract class Type
{
    // {{{ CONSTANT

    // primitive types
    const T_BOOL     = 0x0001;
    const T_INT      = 0x0002;
    const T_FLOAT    = 0x0004;
    const T_BINARY   = 0x0008;
    const T_UNICODE  = 0x0010; // PHP 6
    const T_INDEX    = 0x0020; // array (PHP keyword)
    const T_OBJECT   = 0x0040;
    const T_RESOURCE = 0x0080;
    const T_NULL     = 0x0100;

    // pseudo types
    const T_CALLBACK = 0x0200;
    const T_VOID     = 0x1000;
    const T_MIXED    = 0x03FF; // T_* except T_VOID
    const T_NUMBER   = 0x0006; // T_INT | T_FLOAT
    const T_STRING   = 0x0018; // T_BINARY | T_UNICODE
    const T_BUFFER   = 0x0018; // T_BINARY | T_UNICODE
                               // Is is_buffer() an alias of is_string() ?

    // other types
    const T_UNKNOWN  = 0x0000; // @reserved but not used
                               // gettype() might return 'unknown type'

    // errors
    const E_UNDEFINED = 0;
    const E_CAST      = 1;
    const E_COND      = 2;

    // - CONSTANT }}}

    // {{{ PUBLIC
    // {{{ PUBLIC STATIC

    /**
     * Shorthand
     *
     * Singleton methods.
     * Type::Bool() returns an instance of Type_Bool.
     * Type::Bool(true) returns a cloned instance.
     *
     * @see Type::_shorthand()
     * @param bool $clone returns a cloned instance if true.
     * @returns Type
     * @throws DomainException
     */
    public static function Bool($clone = false)
    {
        return self::_shorthand(self::T_BOOL, $clone);
    }

    public static function Int($clone = false)
    {
        return self::_shorthand(self::T_INT, $clone);
    }

    public static function Float($clone = false)
    {
        return self::_shorthand(self::T_FLOAT, $clone);
    }

    public static function Binary($clone = false)
    {
        return self::_shorthand(self::T_BINARY, $clone);
    }

    public static function Unicode($clone = false)
    {
        return self::_shorthand(self::T_UNICODE, $clone);
    }

    public static function Index($clone = false)
    {
        return self::_shorthand(self::T_INDEX, $clone);
    }

    public static function Object($clone = false)
    {
        return self::_shorthand(self::T_OBJECT, $clone);
    }

    public static function Resource($clone = false)
    {
        return self::_shorthand(self::T_RESOURCE, $clone);
    }

    public static function Null($clone = false)
    {
        return self::_shorthand(self::T_NULL, $clone);
    }

    public static function Callback($clone = false)
    {
        return self::_shorthand(self::T_CALLBACK, $clone);
    }

    public static function Void($clone = false)
    {
        return self::_shorthand(self::T_VOID, $clone);
    }

    public static function Mixed($clone = false)
    {
        return self::_shorthand(self::T_MIXED, $clone);
    }

    public static function Number($clone = false)
    {
        return self::_shorthand(self::T_NUMBER, $clone);
    }

    public static function String($clone = false)
    {
        return self::_shorthand(self::T_STRING, $clone);
    }

    public static function Buffer($clone = false)
    {
        return self::_shorthand(self::T_BUFFER, $clone);
    }

    public static function Unknown()
    {
        throw new DomainException();
    }

    /**
     * Register a specific instance of Type.
     *
     * <code>
     * <?php
     *
     * class ExtType extends Type {}
     * Type::bind('MyType', new ExtType())->defaultValue(1);
     * // ...
     * echo get_class(Type::of('MyType')); // ExtType
     * echo get_class(Type::of('ExtType')); // InvalidArgumentException thrown.
     * ?>
     * </code>
     *
     * @see Type::of()
     * @param string $typeName
     * @param Type $instance
     * @param bool $override
     * @return Type returns $instance for method-chain.
     * @throws InvalidArgumentException if $typeName is not string.
     * @throws BadMethodCallException if $typeName is already bound.
     */
    public static function bind($typeName, Type $instance, $override = false)
    {
        if (!is_string($typeName) &&
            (!is_object($typeName) ||
             !method_exists($typeName, '__toString') ||
             !is_string($typeName = @$typeName->__toString()))) {
            throw new InvalidArgumentException('$typeName must be string');
        }

        if (!$override && isset(self::$_binds[$typeName])) {
            throw new BadMethodCallException('$typeName already bound');
        }

        self::$_binds[$typeName] = $instance;
        return $instance;
    }

    /**
     * Returns the type of a variable.
     *
     * @param mixed $var
     * @return int Type::T_*
     */
    public static function detect($var)
    {
        return self::$_types[gettype($var)];
    }

    /**
     * Returns a specific instance of Type.
     *
     * @see Type::bind()
     * @param string $typeName
     * @param bool $clone returns a cloned instance if true.
     * @return Type
     * @throws InvalidArgumentException if $typeName is not string
     *                                            or is not bound.
     */
    public static function of($typeName, $clone = false)
    {
        if (!is_string($typeName) &&
            (!is_object($typeName) ||
             !method_exists($typeName, '__toString') ||
             !is_string($typeName = @$typeName->__toString()))) {
            throw new InvalidArgumentException('$typeName must be string');
        }

        $binds = self::$_binds;
        if (!isset($binds[$typeName])) {
            throw new InvalidArgumentException('$typeName not bound');
        }

        return $clone ? clone $binds[$typeName] : $binds[$typeName];
    }

    // - PUBLIC STATIC }}}
    // {{{ PUBLIC DYNAMIC

    /**
     * Assign a value to a variable.
     *
     * @param mixed &$to
     * @param mixed $from
     * @return bool
     */
    public function assign(&$from, $to)
    {
        $from = $this->_defaultValue;
        try {
            $from = $this->cast($to);
        } catch (Type_Exception $te) {
            return false;
        }
        return true;
    }

    /**
     * Cast a value.
     *
     * @param mixed $value
     * @return mixed
     * @throws Type_Exception
     */
    abstract public function cast($value);

    /**
     * Returns a duplication of this object.
     *
     * `clone` wrapper for method-chain.
     *
     * @return Type
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * Set the default value of this type.
     *
     * @param mixed $value
     * @return Type
     */
    public function defaultValue($value)
    {
        $this->_defaultValue = $value;
        return $this;
    }

    /**
     * Test if the variable is able to cast.
     *
     * @param mixed $var
     * @return bool
     */
    public function test($var)
    {
        try {
            $test = $this->cast($var);
        } catch (Type_Exception $tex) {
            return false;
        }
        return true;
    }

    // - PUBILC DYNAMIC }}}
    // - PUBLIC }}}

    // {{{ PROTECTED
    // {{{ PROTECTED DYNAMIC

    protected $_defaultValue = null;

    // - PROTECTED DYNAMIC }}}
    // - PROTECTED }}}

    // {{{ PRIVATE
    // {{{ PRIVATE STATIC

    /**
     * User-defined types.
     *
     * @var array
     */
    private static $_binds = array();

    /**
     * Type table.
     *
     * Key: The return value of gettype() excepts 'unknown type'
     * Value: Type::T_*
     *
     * @var array
     */
    private static $_types = array(
        'boolean'  => self::T_BOOL,
        'integer'  => self::T_INT,
        'double'   => self::T_FLOAT,
        'string'   => self::T_STRING,
        'array'    => self::T_INDEX,
        'object'   => self::T_OBJECT,
        'resource' => self::T_RESOURCE,
        'NULL'     => self::T_NULL,
        'unknown type' => self::T_UNKNOWN);

    /**
     * Class table.
     *
     * @var array
     */
    private static $_classes = array(
        self::T_BOOL     => 'Type_Bool',
        self::T_INT      => 'Type_Int',
        self::T_FLOAT    => 'Type_Float',
        self::T_STRING   => 'Type_String',
        self::T_INDEX    => 'Type_Index',
        self::T_OBJECT   => 'Type_Object',
        self::T_RESOURCE => 'Type_Resource',
        self::T_NULL     => 'Type_Null',
        self::T_CALLBACK => 'Type_Callback',
        self::T_VOID     => 'Type_Void',
        self::T_MIXED    => 'Type_Mixed',
        self::T_NUMBER   => 'Type_Number',
        self::T_STRING   => 'Type_String',
        self::T_BUFFER   => 'Type_Buffer');

    /**
     * Return value modification for the shorthand methods.
     *
     * @param int $type Type::T_*
     * @param bool $dup if true returns a cloned instance.
     * @return Type
     * @throws DomainException if the class is already exists.
     */
    private static function _shorthand($type, $clone)
    {
        static $dirName = null;

        $classes = self::$_classes;
        if (!is_string($classes[$type])) {
            return $clone ? clone $classes[$type] : $classes[$type];
        }

        $className = $classes[$type];
        if (!class_exists($className, false)) {
            if ($dirName === null) {
                $dirName = dirname(__FILE__) . DIRECTORY_SEPARATOR;
            }
            require $dirName . strtr($className, '_', DIRECTORY_SEPARATOR)
                    . '.php';
        }

        if (!class_exists($className, false) ||
            !is_subclass_of($className, __CLASS__)) {
            throw new DomainException("$className not found");
        }

        $instance = new $className();
        self::$_classes[$type] = $instance;
        return $clone ? clone $instance : $instance;
    }

    // - PRIVATE STATIC }}}
    // - PRIVATE }}}
}
