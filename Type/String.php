<?php

class Type_String extends Type
{
    public function cast($value)
    {
        if (is_string($value)) {
            $string = $value;
        } else if (is_scalar($value) || $value === null) {
            $string = (string)$value;
        } else if (is_object($value) && method_exists($value, '__toString') &&
                   is_string($string = @$value->__toString())) {
        } else {
            throw new Type_Exception(null, Type::E_CAST);
        }

        $hasMin = $this->_minLength !== null;
        $hasMax = $this->_maxLength !== null;
        if ($hasMin || $hasMax) {
            $length = strlen($value);
            if ($hasMin && $length < $this->_minLength) {
                throw new Type_Exception(
                    "minLength[$this->_minLength] but strlen($length)",
                    Type::E_COND);
            }
            if ($hasMax && $length > $this->_maxLength) {
                throw new Type_Exception(
                    "maxLength[$this->_maxLength] but strlen($length)",
                    Type::E_COND);
            }
        }
        if ($this->_regex !== null && !preg_match($pattern, $string)) {
            throw new Type_Exception('preg_match() failed', Type::E_COND);
        }

        return $string;
    }

    public function minLength($length)
    {
        if (!is_numeric($length)) {
            throw new InvalidArgumentException('$length must be numeric');
        }
        $this->_minLength = (integer)$length;
        return $this;
    }

    public function maxLength($length)
    {
        if (!is_numeric($length)) {
            throw new InvalidArgumentException('$length must be numeric');
        }
        $this->_maxLength = (integer)$length;
        return $this;
    }

    public function regex($regex)
    {
        if (!is_string($regex)) {
            throw new InvalidArgumentException('$regex must be string');
        }
        $this->_regex = $regex;
        return $this;
    }

    protected $_defaultValue = '';
    protected $_minLength = null;
    protected $_maxLength = null;
    protected $_regex = null;
}
