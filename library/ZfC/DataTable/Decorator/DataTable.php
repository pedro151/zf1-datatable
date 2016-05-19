<?php


class ZfC_DataTable_Decorator_DataTable extends ZfC_DataTable_Decorator_Abstract
{
    /**
     * Default view helper
     *
     * @var string
     */
    protected $_helper = 'DataTable';

    /**
     * Set view helper for rendering form
     *
     * @param  string $helper
     * @return Zend_Form_Decorator_Form
     */
    public function setHelper($helper)
    {
        $this->_helper = (string)$helper;

        return $this;
    }

    /**
     * Get view helper for rendering form
     *
     * @return string
     */
    public function getHelper()
    {
        if (null !== ($helper = $this->getOption('helper'))) {
            $this->setHelper($helper);
            $this->removeOption('helper');
        }

        return $this->_helper;
    }


    /**
     * Render a form
     *
     * Replaces $content entirely from currently set element.
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $datatable = $this->getElement();
        $view = $datatable->getView();
        if (null === $view) {
            return $content;
        }


        $helper = $this->_helper;
        $attribs = $this->getOptions();
        $name = $datatable->getFullyQualifiedName();
        $attribs['id'] = $datatable->getId();
        $attribs['ajax'] = $datatable->getAjax();

        return $view->$helper($name, $attribs, $content);
    }

    /**
     * @var Zend_Form_Element|Zend_Form
     */
    protected $_element;

    /**
     * Decorator options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Separator between new content and old
     *
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
     * @return Zend_Form_Decorator_Abstract
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * Retrieve decorator options
     *
     * @return array
     */
    public function getOptions()
    {
        if (null !== ($element = $this->getElement())) {
            if ($element instanceof ZfC_DataTable) {
                foreach ($element->getAttribs() as $key => $value) {
                    $this->setOption($key, $value);
                }
            }
        }

        if (isset($this->_options['method'])) {
            $this->_options['method'] = strtolower($this->_options['method']);
        }

        return $this->_options;
    }

    /**
     * Set options from config object
     *
     * @param  Zend_Config $config
     * @return Zend_Form_Decorator_Abstract
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
     * @return Zend_Form_Decorator_Abstract
     */
    public function setOption($key, $value)
    {
        $this->_options[(string)$key] = $value;

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
        $key = (string)$key;
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }

        return null;
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
     * @return Zend_Form_Decorator_Abstract
     */
    public function clearOptions()
    {
        $this->_options = array();

        return $this;
    }

    /**
     * Set current form element
     *
     * @param  Zend_Form_Element|Zend_Form $element
     * @return Zend_Form_Decorator_Abstract
     * @throws Zend_Form_Decorator_Exception on invalid element type
     */
    public function setElement($element)
    {
        if ((!$element instanceof ZfC_DataTable_Element)
            && (!$element instanceof ZfC_DataTable)
        ) {
            require_once 'Zend/Form/Decorator/Exception.php';
            throw new Zend_Form_Decorator_Exception('Invalid element type passed to decorator');
        }

        $this->_element = $element;

        return $this;
    }

    /**
     * Retrieve current element
     *
     * @return Zend_Form_Element|Zend_Form
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
            $separator = $this->_separator = (string)$separatorOpt;
            $this->removeOption('separator');
        }

        return $separator;
    }

}
