<?php

class Type_Bytes extends Type
{
    public function min($length)
    {
        if (!is_numeric($length)) {
            throw new InvalidArgumentException('$length must be numeric');
        }
        $this->_min = (integer)$length;
        return $this;
    }

    public function max($length)
    {
        if (!is_numeric($length)) {
            throw new InvalidArgumentException('$length must be numeric');
        }
        $this->_max = (integer)$length;
        return $this;
    }

    public function regex($pattern)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('$pattern must be string');
        }
        $this->_regex = $pattern;
        return $this;
    }

    protected $_defaultValue = '';
    protected $_min = null;
    protected $_max = null;
    protected $_regex = null;

    protected function _assign(&$to, $value)
    {
        if (!is_scalar($value)) {
            throw new Type_Exception(null, Type::E_CAST, $this);
        }

        $string = (string)$value;
        $hasMin = $this->_min !== null;
        $hasMax = $this->_max !== null;
        if ($hasMin || $hasMax) {
            $length = strlen($value);
            if ($hasMin && $length < $this->_min) {
                throw new Type_Exception(
                    "min($this->_min) but strlen($length)",
                    Type::E_CONDITION, $this, array('condition' => 'min'));
            }
            if ($hasMax && $length > $this->_max) {
                throw new Type_Exception(
                    "max($this->_max) but strlen($length)",
                    Type::E_CONDITION, $this, array('condition' => 'max'));
            }
        }
        if ($this->_regex !== null && !preg_match($this->_regex, $string)) {
            throw new Type_Exception("Regex pattern unmatch",
                Type::E_CONDITION, $this, array('condition' => 'regex'));
        }

        $to = $string;
        return $this;
    }
}
