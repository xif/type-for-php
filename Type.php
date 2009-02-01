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

    // cast level
    const L_BUILTIN = 0; // same as pure PHP
    const L_SAFE    = 1; // (string)(array()), (int)('1abc') fails
    const L_STRICT  = 2; // (string)(1) fails
    const L_USER    = 3; // user-specified

    // - CONSTANT }}}

    // {{{ PUBLIC
    // {{{ PUBLIC STATIC

    /**
     * Shorthand
     *
     * Singleton methods.
     * Type::Bool() returns an instance of Type_Bool.
     * Type::Bool(true) returns a cloned instance.
     */
    public static function Bool($bool = false)
    {
        return self::_shorthand(self::T_BOOL, $bool);
    }

    public static function Int($bool = false)
    {
        return self::_shorthand(self::T_INT, $bool);
    }

    public static function Float($bool = false)
    {
        return self::_shorthand(self::T_FLOAT, $bool);
    }

    public static function Binary($bool = false)
    {
        return self::_shorthand(self::T_BINARY, $bool);
    }

    public static function Unicode($bool = false)
    {
        return self::_shorthand(self::T_UNICODE, $bool);
    }

    public static function Index($bool = false)
    {
        return self::_shorthand(self::T_INDEX, $bool);
    }

    public static function Object($bool = false)
    {
        return self::_shorthand(self::T_OBJECT, $bool);
    }

    public static function Resource($bool = false)
    {
        return self::_shorthand(self::T_RESOURCE, $bool);
    }

    public static function Null($bool = false)
    {
        return self::_shorthand(self::T_NULL, $bool);
    }

    public static function Callback($bool = false)
    {
        return self::_shorthand(self::T_CALLBACK, $bool);
    }

    public static function Void($bool = false)
    {
        return self::_shorthand(self::T_VOID, $bool);
    }

    public static function Mixed($bool = false)
    {
        return self::_shorthand(self::T_MIXED, $bool);
    }

    public static function Number($bool = false)
    {
        return self::_shorthand(self::T_NUMBER, $bool);
    }

    public static function String($bool = false)
    {
        return self::_shorthand(self::T_STRING, $bool);
    }

    public static function Buffer($bool = false)
    {
        return self::_shorthand(self::T_BUFFER, $bool);
    }

    public static function Unknown()
    {
        throw new DomainException();
    }

    /**
     * Returns the value of T_* by a name of the type
     *
     * @param string $typeName
     * @return int Type::T_*
     * @throws InvalidArgumentException
     */
    public static function constant($typeName)
    {
        $string = self::_getStringValueOf($typeName);
        if ($string === false) {
            throw new InvalidArgumentException('$typeName must be string');
        }
        $string = strtolower($string);
        if (!isset(self::$_types[$string])) {
            // gettype() might return 'unknown type'
            throw new InvalidArgumentException('$typeName not found');
        }
        return self::$_types[$string];
    }

    /**
     * Detect the type of a variable.
     *
     * @param mixed $value
     * @return int Type::T_*
     */
    public static function detect($value)
    {
        return self::constant(gettype($value));
    }

    /**
     * Returns an instance of Type
     *
     * @param mixed $id Type::T_* or class path (eg. Type/String)
     * @return Type
     * @throws InvalidArgumentException
     */
    public static function singleton($id)
    {
        if (($classPath = self::_isClassId($id)) === false) {
            throw new InvaildArgumentExcpeion('Invalid $id');
        }

        $stock = self::$_singleton;
        return isset($stock[$classPath])
               ? $stock[$classPath]
               : $stock[$classPath] = new self::$_classes[$classPath]();
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
    public function cast($value)
    {
        return $value;
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
     * Returns a duplication of this object.
     *
     * `clone` wrapper for method-chain.
     *
     * @return Type
     */
    public function dup()
    {
        return clone $this;
    }

    // - PUBILC DYNAMIC }}}
    // - PUBLIC }}}

    // {{{ PROTECTED
    // {{{ PROTECTED DYNAMIC

    protected $_defaultValue = null;
    protected $_level = self::L_SAFE;

    // - PROTECTED DYNAMIC }}}
    // - PROTECTED }}}

    // {{{ PRIVATE
    // {{{ PRIVATE STATIC

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
        'NULL'     => self::T_NULL);

    /**
     * Class table.
     *
     * Key: Type::T_* or class path (eg. Type/MySQL/Varchar etc)
     * Value: Actual class names (eg. Type_MySQL_Varchar)
     *        excepts built-in classes. (eg. Type/String)
     *
     * Built-in case:
     * <code>
     * <?php
     * 
     * $id = (int)$someOuterValue;
     * if (!isset(self::$_classes[$id])) {
     *     exit('$id is not a valid Type::T_*');
     * }
     * $classPath = self::$_classes[$id]; // 'Type/*'
     * if (!isset(self::$_classes[$classPath])) {
     *     // class Type_* not loaded
     *     if (!self::_loadTypeSubclass($classPath)) {
     *         exit('Failed to load class');
     *     }
     *     // _loadTypeSubclass() sets $_classes[$classPath]
     * }
     * $className = self::$_classes[$classPath]; // 'Type_*'
     * $instance = new $className();
     *
     * ?>
     * </code>
     *
     * @see _isClassId()
     * @var array
     */
    private static $_classes = array(
        self::T_BOOL     => 'Type/Bool',
        self::T_INT      => 'Type/Int',
        self::T_FLOAT    => 'Type/Float',
        self::T_STRING   => 'Type/String',
        self::T_INDEX    => 'Type/Index',
        self::T_OBJECT   => 'Type/Object',
        self::T_RESOURCE => 'Type/Resource',
        self::T_NULL     => 'Type/Null',
        self::T_CALLBACK => 'Type/Callback',
        self::T_VOID     => 'Type/Void',
        self::T_MIXED    => 'Type/Mixed',
        self::T_NUMBER   => 'Type/Number',
        self::T_STRING   => 'Type/String',
        self::T_BUFFER   => 'Type/Buffer');

    /**
     * Singleton container.
     *
     * Key: Type::T_* or class path (eg. Type/String)
     * Value: Type
     *
     * @var array
     */
    private static $_singleton = array();

    /**
     * Returns the string value of a variable.
     *
     * Fails if a variable is:
     *    bool, null, array, resource, no valid __toString() object
     *
     * @returns string or false if failed
     */
    private static function _getStringValueOf($value)
    {
        if (is_scalar($value) || $value === null) {
            return (string)$value;
        }
        if (!is_object($value) || !method_exists($value, '__toString')) {
            return false;
        }
        $string = @$value->__toString();
        return is_string($string) ? $string : false;
    }

    /**
     * Find wheter the value of a variable is a class ID.
     *
     * @param mixed Type::T_* or class path (eg. Type/String)
     * @return string Class path
     */
    private static function _isClassId($id)
    {
        if (is_numeric($id)) {
            // treat as Type::T_*
            $int = (int)$id;
            if (!isset(self::$_classes[$int])) {
                return false;
            }
            $classPath = self::$_classes[$int];
            if (isset(self::$_classes[$classPath])) {
                return $classPath;
            }
            return self::_loadTypeSubclass($classPath);
        }

        if (($classPath = self::_getStringValueOf($id)) === false) {
            return false;
        }

        if (isset(self::$_classes[$classPath])) {
            return $classPath;
        }

        if (!preg_match('@^[a-z][a-z0-9]*(?:/[a-z][a-z0-9]*)*$@i',
                        $classPath)) {
            return false;
        }

        $className = strpos($classPath, '/') === false
                   ? $classPath : strtr($classPath, '/', '_');
        if (!class_exists($className) ||
            !is_subclass_of($className, __CLASS__)) {
            return false;
        }

        self::$_classes[$classPath] = $className;
        return $classPath;
    }

    /**
     * Load a built-in subclass.
     *
     * This method must be called once per class.
     * Test before calling, like isset(self::$_classes['Type/String']).
     *
     * @see $_classes
     * @param string $classPath eg. Type/String
     * @return string $classPath
     */
    private static function _loadTypeSubclass($classPath)
    {
        static $dirName = null;
        static $fixSep = false;

        if ($dirName === null) {
            $dirName = dirname(__FILE__) . DIRECTORY_SEPARATOR;
            $fixSep = DIRECTORY_SEPARATOR !== '/';
        }

        $className = strtr($classPath, '/', '_');

        if (interface_exists($className, false)) {
            return false;
        }

        if (class_exists($className, false)) {
            if (!is_subclass_of($className, __CLASS__)) {
                return false;
            }
        }

        require $dirName
                . ($fixSep ? strtr($classPath, '/', DIRECTORY_SEPARATOR)
                           : $classPath)
                . '.php';

        if (!class_exists($className, false) ||
            !is_subclass_of($className, __CLASS__)) {
            return false;
        }

        self::$_classes[$classPath] = $className;
        return $classPath;
    }

    /**
     * Return value modification for the shorthand methods.
     *
     * @param int $type Type::T_*
     * @param bool $dup if true returns a cloned instance.
     * @return Type
     */
    private static function _shorthand($type, $bool)
    {
        $instance = self::singleton($type);
        return $bool ? clone $instance : $instance;
    }

    // - PRIVATE STATIC }}}
    // - PRIVATE }}}
}
