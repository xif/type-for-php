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
     */
    // Not implemented
    public static function Bool() {}
    public static function Int() {}
    public static function Float() {}
    public static function Binary() {}
    public static function Unicode() {}
    public static function Index() {}
    public static function Object() {}
    public static function Resource() {}
    public static function Nil() {}
    public static function Callback() {}
    public static function Void() {}
    public static function Mixed() {}
    public static function Number() {}
    public static function String() {}
    public static function Buffer() {}

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
     * Get the value of T_* by a name of the type
     *
     * @param string $typeName
     * @return int Type::T_*
     * @throws InvalidArgumentException
     */
    public static function constant($typeName)
    {
        $string = self::_getStringValue($typeName);
        if ($string === false) {
            throw new InvalidArgumentException('$typeName must be string');
        }
        $string = strtolower($string);
        if (!isset(self::$_typeNames[$string])) {
            // gettype() might return 'unknown type'
            throw new InvalidArgumentException('$typeName not found');
        }
        return self::$_typeNames[$string];
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

    private static $_typeNames = array(
        'boolean'  => self::T_BOOL,
        'integer'  => self::T_INT,
        'double'   => self::T_FLOAT,
        'string'   => self::T_STRING,
        'array'    => self::T_ARRAY,
        'object'   => self::T_OBJECT,
        'resource' => self::T_RESOURCE,
        'NULL'     => self::T_NULL);

    private static function _getStringValue($value)
    {
        if (!is_object($value)) {
            return (string)$value;
        }
        if (!method_exists('__toString')) {
            return false;
        }
        $string = @$value->__toString();
        return is_string($string) ? $string : false;
    }
    // - PRIVATE STATIC }}}
    // - PRIVATE }}}
}
