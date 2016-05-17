<?php

abstract class ZfC_DataTable_Decorator_Abstract
{

    /**
     * @var ZfC_DataTable_Element|ZfC_DataTable
     */
    protected $_element;

    /**
     * Decorator options
     * @var array
     */
    protected $_options = array();

    /**
     * Separator between new content and old
     * @var string
     */
    protected $_separator = PHP_EOL;

    /**
     * Constructor
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif ($options instanceof Zend_Config) {
            $this->setConfig($options);
        }
    }

    /**
     * Set options
     *
     * @param  array $options
     * @return ZfC_DataTable_Decorator_Abstract
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
        return $this;
    }

    /**
     * Set options from config object
     *
     * @param  Zend_Config $config
     * @return ZfC_DataTable_Decorator_Abstract
     */
    public function setConfig(Zend_Config $config)
    {
        return $this->setOptions($config->toArray());
    }

    /**
     * Set option
     *
     * @param  string $key
     * @param  mixed $value
     * @return ZfC_DataTable_Decorator_Abstract
     */
    public function setOption($key, $value)
    {
        $this->_options[(string) $key] = $value;
        return $this;
    }

    /**
     * Get option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        $key = (string) $key;
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }

        return null;
    }

    /**
     * Retrieve options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Remove single option
     *
     * @param mixed $key
     * @return void
     */
    public function removeOption($key)
    {
        if (null !== $this->getOption($key)) {
            unset($this->_options[$key]);
            return true;
        }

        return false;
    }

    /**
     * Clear all options
     *
     * @return ZfC_DataTable_Decorator_Abstract
     */
    public function clearOptions()
    {
        $this->_options = array();
        return $this;
    }

    /**
     * Set current form element
     *
     * @param  ZfC_DataTable_Element|ZfC_DataTable $element
     * @return ZfC_DataTable_Decorator_Abstract
     * @throws ZfC_DataTable_Decorator_Exception on invalid element type
     */
    public function setElement($element)
    {
        if ((!$element instanceof ZfC_DataTable_Element)
            && (!$element instanceof ZfC_DataTable))
        {
            require_once 'ZfC/DataTable/Decorator/Exception.php';
            throw new ZfC_DataTable_Decorator_Exception('Invalid element type passed to decorator');
        }

        $this->_element = $element;
        return $this;
    }

    /**
     * Retrieve current element
     *
     * @return ZfC_DataTable_Element|ZfC_DataTable
     */
    public function getElement()
    {
        return $this->_element;
    }


    /**
     * Retrieve separator to use between old and new content
     *
     * @return string
     */
    public function getSeparator()
    {
        $separator = $this->_separator;
        if (null !== ($separatorOpt = $this->getOption('separator'))) {
            $separator = $this->_separator = (string) $separatorOpt;
            $this->removeOption('separator');
        }
        return $separator;
    }

    /**
     * Decorate content and/or element
     *
     * @param  string $content
     * @return string
     * @throws ZfC_DataTable_Decorator_Exception when unimplemented
     */
    public function render($content)
    {
        require_once 'ZfC/DataTable/Decorator/Exception.php';
        throw new ZfC_DataTable_Decorator_Exception('render() not implemented');
    }
}
