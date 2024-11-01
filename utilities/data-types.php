<?php
namespace Wovax\IDX\Utilities;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

use Exception;

abstract class DataType {
    private $default  = NULL;
    private $user_san = NULL;
    public function __construct($default, $user_sanitize = NULL) {
        if(!$this->checkValue($default)) {
            throw new Exception('Default value is not of the type '.$this->getName());
        }
        $this->setDefault($default);
        if(is_callable($user_sanitize)) {
            $this->user_san = $user_sanitize;
        }
    }
    public function sanitizeValue($value) {
        if(is_callable($this->user_san)) {
            $value = call_user_func($this->user_san, $value);
        }
        return $this->sanitize($value);
    }
    public function getDefault() {
        if(is_null($this->default)) {
            throw new Exception('Default never set for the DataType: '.$this->getName());
        }
        return $this->default;
    }
    protected function setDefault($value) {
        $this->default = $value;
    }
    protected static function isAssociativeArray($arr) {
        if(!is_array($arr) || count($arr) < 1) {
            return FALSE;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
	private function getName() {
        $name = (new \ReflectionClass($this))->getShortName();
        return $name;
    }
    abstract public function checkValue($value);
    abstract protected function sanitize($value);
}

class ArrayType extends DataType {
    private $type;
    private $numeric_keys = FALSE;
    public function __construct(DataType $type, $default = array(), $numeric_keys = TRUE) {
        $this->type = $type;
        $this->numeric_keys = $numeric_keys;
        parent::__construct($default);
    }
    public function checkValue($value) {
        if(!is_array($value)) {
            return FALSE;
        }
        if($this->numeric_keys && self::isAssociativeArray($value)) {
            return FALSE;
        }
        foreach($value as $val) {
            if(!$this->type->checkValue($val)) {
                return FALSE;
            }
        }
        return TRUE;
    }
    protected function sanitize($value) {
        if(!is_array($value)) {
            return $this->getDefault();
        }
        if($this->numeric_keys && self::isAssociativeArray($value)) {
            $value = array_values($value);
        }
        $sanitized = array();
        foreach($value as $index => $val) {
            $sanitized[$index] = $this->type->sanitizeValue($val);
        }
        return $sanitized;
    }
}

class BoolType extends DataType {
    public function __construct($default = FALSE, $user_sanitize = NULL) {
        parent::__construct($default, $user_sanitize);
    }
    public function checkValue($value) {
        return is_bool($value);
    }
    protected function sanitize($value) {
        if(is_string($value)) {
            $str = strtolower(trim($value));
            $value = FALSE;
            if(is_numeric($str)) {
                $value = (floatval($str) > 0);
            }
            if(in_array($str, array('true', 'yes', 'y', 'on'))) {
                $value = TRUE;
            }
        }
        if(is_int($value) || is_float($value)) {
            $value = ($value > 0);
        }
        if(!is_bool($value)) {
            return $this->getDefault();
        }
        return $value;
    }
}

class EnumType extends DataType {
    public function __construct(DataType $type, ...$enums) {
        foreach($enums as $value) {
            if( !$type->checkValue($value) ) {
                throw new Exception('Invalid Type passed to EnumType constructor enum list.');
            }
        }
        $this->enum = $enums;
        // Check types default
        if( !$this->checkValue($type->getDefault()) ) {
            throw new Exception('The default value for the Type passed to EnumType is not in the enum list.');
        }
        $this->type = $type;
        parent::__construct($type->getDefault());
    }
    public function checkValue($value) {
        return in_array($value, $this->enum, TRUE);
    }
    protected function sanitize($value) {
        $val = $this->type->sanitizeValue($value);
        if(!$this->checkValue($val)) {
            $val = $this->getDefault();
        }
        return $val;
    }
}

class IntType extends DataType {
    public function __construct($default = 0, $user_sanitize = NULL) {
        parent::__construct($default, $user_sanitize);
    }
    public function checkValue($value) {
        return is_int($value);
    }
    protected function sanitize($value) {
        if(!is_numeric($value)) {
            return $this->getDefault();
        }
        return intval($value);
    }
}

class RealType extends DataType {
    public function __construct($default = 0.0, $user_sanitize = NULL) {
        parent::__construct($default, $user_sanitize);
    }
    public function checkValue($value) {
        return is_float($value);
    }
    protected function sanitize($value) {
        if(!is_numeric($value)) {
            return $this->getDefault();
        }
        return floatval($value);
    }
}

class StringType extends DataType {
    public function __construct($default = '', $user_sanitize = NULL) {
        parent::__construct($default, $user_sanitize);
    }
    public function checkValue($value) {
        return is_string($value);
    }
    protected function sanitize($value) {
        if(is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        if(is_int($value) || is_float($value)) {
            $value = "$value";
        }
        if(!is_string($value)) {
            return $this->getDefault();
        }
        return strval($value);
    }
}

class StructType extends DataType {
    private $types = array();
    public function __construct(...$types) {
        foreach($types as $type) {
            if(!is_array($type) || count($type) != 2) {
                throw new Exception('Invalid Type passed to StructType constructor [Name, Datatype] only.');
            }
            $type = array_values($type);
            if(!is_string($type[0])) {
                throw new Exception('Invalid Type passed to StructType constructor each type needs a name.');
            }
			if(array_key_exists($type[0], $this->types)) {
                throw new Exception('Invalid Type passed to StructType constructor each type needs a unique name.');
            }
            if($type[1] instanceof DataType) {
                $this->types[$type[0]] = $type[1];
				continue;
            }
            throw new Exception('Invalid Type passed to StructType constructor a name requires a DataType.');
        }
        $default = array();
        foreach($this->types as $key => $type) {
            $default[$key] = $type->getDefault();
        }
        parent::__construct($default);
    }
    public function checkValue($value) {
        if(!is_array($value) || count($value) != count($this->types)) {
            return FALSE;
        }
        foreach($this->types as $key => $type) {
            if(!array_key_exists($key, $value)) {
                return FALSE;
            }
            if(!$type->checkValue($value[$key])) {
                return FALSE;
            }
        }
        return TRUE;
    }
    protected function sanitize($value) {
        if(!is_array($value)) {
            return $this->getDefault();
        }
        $sanitized = array();
        foreach($this->types as $key => $type) {
            if(array_key_exists($key, $value)) {
                $sanitized[$key] = $type->sanitizeValue($value[$key]);
            } else {
                $sanitized[$key] = $type->getDefault();

            }
        }
        return $sanitized;
    }
}

class N_TupleType extends DataType {
    private $types = array();
    public function __construct(...$types) {
        foreach($types as $type) {
            if($type instanceof DataType) {
                continue;
            }
            throw new Exception('Invalid Type passed to TupleType constructor.');
        }
        $this->types = $types;
        $default = array();
        foreach($this->types as $type) {
            $default[] = $type->getDefault();
        }
        parent::__construct($default);
    }
    public function checkValue($value) {
        if(
            !is_array($value) ||
            count($value) != count($this->types) ||
            self::isAssociativeArray($value)
        ) {
            return FALSE;
        }
        foreach($this->types as $index => $type) {
            if(!array_key_exists($index, $value)) {
                return FALSE;
            }
            if(!$type->checkValue($value[$index])) {
                return FALSE;
            }
        }
        return TRUE;
    }
    protected function sanitize($value) {
        if(!is_array($value)) {
            return $this->getDefault();
        }
        $sanitized = array();
        foreach($this->types as $index => $type) {
            if(array_key_exists($index, $value)) {
                $sanitized[] = $type->sanitizeValue($value[$index]);
            } else {
                $sanitized[] = $type->getDefault();
            }
        }
        return $sanitized;
    }
}