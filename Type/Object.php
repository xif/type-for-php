<?php

class Type_Object extends Type
{
    protected function cast($value)
    {
        if ($this->_className !== null) {
            if (!($value instanceof $this->_className)) {
               throw new Type_Exception('Invalid instance', Type::E_COND);
            }
        } else if (!is_object($value)) {
            throw new Type_Exception(null, Type::E_CAST);
        }

        return $value;
    }

    public function className($className)
    {
        if (!is_string($className) || $className === '') {
            throw new InvalidArgumentException('$className must be string');
        }
        $this->_className = $className;
        return $this;
    }

    protected $_className = null;
}
