<?php

/**
 * User: pedro
 * Date: 21/07/15
 * Time: 10:55
 */
class ZfComplement_DataTable_Create
{

    private $_id;
    private $_value;
    private $_content;
    private $_desabled;
    private $_isButtom = false;
    private $_Options = array();
    private $_js = null;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->_value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->_content = $content;

        return $this;
    }

    /**
     * @return mixed
     */
    public function isDesabled()
    {
        return $this->_desabled;
    }

    /**
     * @param mixed $desabled
     */
    public function setDesabled($desabled)
    {
        $this->_desabled = (bool)$desabled;

        return $this;
    }

    public function setButtom($Buttom)
    {
        $this->_isButtom = $Buttom;

        return $this;
    }

    public function isButtom()
    {
        return $this->_isButtom;
    }

    public function setOptions($Options)
    {

        if (array_key_exists('name', $Options) && !is_null($Options['name'])) {
            $this->_Options['name'] = $Options['name'];
            unset($Options['name']);
        }
        if (array_key_exists('width', $Options) && !is_null($Options['width'])) {
            $this->_Options['width'] = $Options['width'];
            unset($Options['width']);
        }
        if (array_key_exists('class', $Options) && !is_null($Options['class'])) {
            $this->_Options['className'] = $Options['class'];
            unset($Options['class']);
        }
        if (array_key_exists('type', $Options) && !is_null($Options['type'])) {
            $this->_Options['type'] = $Options['type'];
            unset($Options['type']);
        }
        if (array_key_exists('disable', $Options) && !is_null($Options['disable'])) {
            $this->setDesabled($Options['disable']);
            unset($Options['disable']);
        }

        return $this;
    }

    public function getOptions()
    {
        return $this->_Options;
    }

    public function getOption($name)
    {
        if (isset($this->_Options[$name])) {
            return $this->_Options[$name];
        }
    }

    public function setOption($name, $value)
    {
        $this->_Options[$name] = $value;
        return $this;
    }

    public function hasOptions()
    {
        return (bool)$this->_Options;
    }

    public function hasOption($option)
    {
        return isset($this->_Options[$option]);
    }

    public function setJscript($js)
    {
        $this->_js = $js;
    }

    public function getJscript()
    {
        return $this->_js;
    }

    public function hasJscript()
    {
        return (bool)$this->_js;
    }
}