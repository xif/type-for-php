<?php

class Type_Array extends Type
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

    public function values(Type $type)
    {
        $this->_values = $type;
        return $this;
    }

    protected $_min = null;
    protected $_max = null;
    protected $_values = null;

    protected function _assign(&$to, $value)
    {
        if (!is_array($value)) {
            throw new Type_Exception(null, Type::E_CAST, $this);
        }

        $hasMin = $this->_min !== null;
        $hasMax = $this->_max !== null;
        if ($hasMin || $hasMax) {
            $count = count($value);
            if ($hasMin && $count < $this->_min) {
                throw new Type_Exception(
                    "min($this->_min) but count($count)",
                    Type::E_CONDITION, $this, array('condition' => 'min'));
            }
            if ($hasMax && $count > $this->_min) {
                throw new Type_Exception(
                    "max($this->_max) but count($count)",
                    Type::E_CONDITION, $this, array('condition' => 'max'));
            }
        }

        if (empty($value)) {
            $to = $value;
            return $this;
        }

        $invalidKey = false;
        if ($this->_values !== null) {
            $before = -1;
            $type = $this->_values;
            $key = null;
            try {
                foreach ($value as $key => $element) {
                    if (!is_int($key) || $before + 1 != $key) {
                        $invalidKey = true;
                        break;
                    }
                    $before = $key;
                    $type->assign($tmp, $element);
                }
            } catch (Type_Exception $tex) {
                throw new Type_Exception("Invalid value of array",
                                         Type::E_CONDITION, $this,
                                         array('potision' => $key,
                                               'condition' => 'values'));
            }
        } else {
            $before = -1;
            foreach (array_keys($value) as $key) {
                if (!is_int($key) || $before + 1 != $key) {
                    $invalidKey = true;
                    break;
                }
                $before = $key;
            }
        }

        if ($invalidKey) {
            throw new Type_Exception("Invalid key of array", Type::E_CONDITION,
                                     $this);
        }

        $to = $value;
        return $this;
    }
}
