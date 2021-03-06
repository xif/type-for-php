<?php

class Type_Int extends Type
{
    public function cast($value)
    {
        if (!is_numeric($value)) {
            throw new Type_Exception(null, Type::E_CAST);
        }
        $v = (integer)$value;
        if ($this->_min !== null && $v < $this->_min) {
            throw new Type_Exception("min[$this->_min] but int($v)",
                                     Type::E_COND);
        }
        if ($this->_max !== null && $v > $this->_max) {
            throw new Type_Exception("max[$this->_max] but int($v)",
                                     Type::E_COND);
        }
        return $v;
    }

    public function min($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('$value must be numeric');
        }
        $this->_min = (int)$value;
        return $this;
    }

    public function max($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('$value must be numeric');
        }
        $this->_max = (int)$value;
        return $this;
    }

    protected $_defaultValue = 0;
    protected $_min = null;
    protected $_max = null;
}
