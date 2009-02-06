<?php

class Type_Int
{
    public function cast($value)
    {
        if (!is_numeric($value)) {
            throw new Type_Exception(null, Type::E_CAST);
        }
        $int = (integer)$value;
        if ($this->_min !== null && $int < $this->_min) {
            throw new Type_Exception("min($this->_min) but int($int)",
                                     Type::E_COND);
        }
        if ($this->_max !== null && $int > $this->_max) {
            throw new Type_Exception("max($this->_max) but int($int)",
                                     Type::E_COND);
        }
        return $int;
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
