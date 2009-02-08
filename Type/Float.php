<?php

class Type_Float extends Type
{
    public function cast($value)
    {
        if (!is_numeric($value)) {
            throw new Type_Exception(null, Type::E_CAST);
        }
        $v = (float)$value;
        if ($this->_min !== null && $v < $this->_min) {
            throw new Type_Exception("min[$this->_min] but float($v)",
                                     Type::E_COND);
        }
        if ($this->_max !== null && $v > $this->_max) {
            throw new Type_Exception("max[$this->_max] but float($v)",
                                     Type::E_COND);
        }
        return $v;
    }

    public function min($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('$value must be numeric');
        }
        $this->_min = (float)$value;
        return $this;
    }

    public function max($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('$value must be numeric');
        }
        $this->_max = (float)$value;
        return $this;
    }

    protected $_defaultValue = 0.0;
    protected $_min = null;
    protected $_max = null;
}
