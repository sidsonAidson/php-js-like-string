<?php

/**
 * Created by PhpStorm.
 * User: mac
 * Date: 12/06/2017
 * Time: 10:33
 */
//implements \Iterator,\ArrayAccess,\Serializable

class JsString implements \ArrayAccess, \Iterator, Countable
{


    /**
     * @var array
     */
    private $internalArrayRepresentation;

    /**
     * @var int
     */
    private $internalCursor = 0;

    /**
     * @var bool
     */
    private $useStrict = true;

    /**
     * JsString constructor.
     * @param mixed $data
     * @throws InvalidArgumentException
     */
    function __construct($data = '')
    {
        if(!extension_loaded('mbstring'))
        {
            throw new RuntimeException("mbstring module not enabled");
        }

        $string = $this->checkAndGetVarString($data);
        $this->internalArrayRepresentation = $this->strToArray($string);
    }


    /**
     * @param $string
     * @return array
     */
    private function strToArray($string)
    {
        return preg_split('//u', $string, null,PREG_SPLIT_NO_EMPTY);
    }



    public function __get($name)
    {
        if($name === 'length')
        {
            return mb_strlen($this->toString());
        }

        return null;
    }

    /**
     * @return string
     */
    public  function __toString ()
    {
        return implode("",$this->internalArrayRepresentation);
    }

    /**
     * @return JsString
     */
    public function __clone()
    {
        return new JsString($this);
    }

    /**
     * @return JsString
     */
    public function copy()
    {
        return new JsString($this->__toString());
    }

    /**
     * @param mixed
     * @return JsString
     * @throws InvalidArgumentException
     */
    public function add($data)
    {
        $willBeAdd = $this->checkAndGetVarString($data);

        $strArray = $this->strToArray($willBeAdd);
        $this->arrayMerge($this->internalArrayRepresentation, $strArray);

        return $this;
    }

    /**
     * @param array $into
     * @param array $source
     */
    private function arrayMerge(array &$into, $source)
    {
        for($i = 0; $i < count($source); $i++)
        {
            $into[] = $source[$i];
        }
    }

    /**
     * @return int
     */
    public function length()
    {
        return mb_strlen($this->toString());
    }

    /**
     * @param $offset
     * @return string
     */
    public function get($offset)
    {
        $this->checkOffset($offset);
        return mb_substr($this->toString(), $offset, 1);
    }

    /**
     * @param $offset
     * @param mixed
     */
    public function set($offset, $value)
    {
        $this->checkOffset($offset);
        $string = $this->checkAndGetVarString($value);
        $char = mb_substr($string, 0, 1);

        $this->internalArrayRepresentation[$offset] = $char;

        //TODO
    }

    private function checkOffset($offset)
    {
        $valid = $this->offsetExists($offset);
        if(!$valid)
        {
            if($this->useStrict)
            {
                throw new OutOfBoundsException("Given offset not exist size = {$this->length()} , index = {$offset}");
            }
        }
    }

    /**
     * @return bool
     */
    public function isUseStrict()
    {
        return $this->useStrict;
    }

    /**
     * @param bool $useStrict
     */
    public function setUseStrict($useStrict)
    {
        $this->useStrict = boolval($useStrict);
    }



    /**
     * @return string
     * @param $data
     * @throws InvalidArgumentException
     */
    private function checkAndGetVarString($data)
    {
        if(!is_string($data))
        {
            if(is_object($data))
            {
                $rc = new ReflectionClass($data);
                if(!$rc->hasMethod('__toString'))
                {
                    goto error;
                }
                else{
                    return call_user_func([$data, '__toString']);
                }
            }
            else{
                error:

                if($this->useStrict)
                {
                    throw new InvalidArgumentException("Expected string|null ".gettype($data)." given");
                }
                else{
                    return '';
                }
            }

        }
        else{
            return $data;
        }
    }

    /**
     * @return string
     */
    public  function toString ()
    {
        return $this->__toString();
    }

    /********************** \Iterator,\ArrayAccess *****************/


    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $offset >= 0 && $this->length() > $offset;
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        //
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this[$this->internalCursor];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->internalCursor++;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->internalCursor;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->offsetExists($this->internalCursor);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->internalCursor = 0;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->length();
    }
}