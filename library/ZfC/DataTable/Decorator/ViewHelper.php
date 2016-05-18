<?php

class ZfC_DataTable_Decorator_ViewHelper extends ZfC_DataTable_Decorator_Abstract
{
    /**
     * View helper to use when rendering
     * @var string
     */
    protected $_helper;

    /**
     * Set view helper to use when rendering
     *
     * @param  string $helper
     * @return ZfC_DataTable_Decorator_Element_ViewHelper
     */
    public function setHelper($helper)
    {
        $this->_helper = (string) $helper;
        return $this;
    }

    /**
     * Retrieve view helper for rendering element
     *
     * @return string
     */
    public function getHelper()
    {
        if (null === $this->_helper) {
            $options = $this->getOptions();
            if (isset($options['helper'])) {
                $this->setHelper($options['helper']);
                $this->removeOption('helper');
            } else {
                $element = $this->getElement();
                if (null !== $element) {
                    if (null !== ($helper = $element->getAttrib('helper'))) {
                        $this->setHelper($helper);
                    } else {
                        $type = $element->getType();
                        if ($pos = strrpos($type, '_')) {
                            $type = substr($type, $pos + 1);
                        }
                        $this->setHelper('datatable' . ucfirst($type));
                    }
                }
            }
        }

        return $this->_helper;
    }

    /**
     * Get name
     *
     * If element is a ZfC_DataTable_Element, will attempt to namespace it if the
     * element belongs to an array.
     *
     * @return string
     */
    public function getName()
    {
        if (null === ($element = $this->getElement())) {
            return '';
        }

        $name = $element->getName();

        if (!$element instanceof ZfC_DataTable_Element) {
            return $name;
        }

       
        if ($element->isArray()) {
            $name .= '[]';
        }

        return $name;
    }

    /**
     * Retrieve element attributes
     *
     * Set id to element name and/or array item.
     *
     * @return array
     */
    public function getElementAttribs()
    {
        if (null === ($element = $this->getElement())) {
            return null;
        }

        $attribs = $element->getAttribs();
        if (isset($attribs['helper'])) {
            unset($attribs['helper']);
        }

        if (method_exists($element, 'getSeparator')) {
            if (null !== ($listsep = $element->getSeparator())) {
                $attribs['listsep'] = $listsep;
            }
        }

        if (isset($attribs['id'])) {
            return $attribs;
        }

        $id = $element->getName();

        $element->setAttrib('id', $id);
        $attribs['id'] = $id;

        return $attribs;
    }

    /**
     * Get value
     *
     * If element type is one of the button types, returns the label.
     *
     * @param  ZfC_DataTable_Element $element
     * @return string|null
     */
    public function getValue($element)
    {
        if (!$element instanceof ZfC_DataTable_Element) {
            return null;
        }

        return $element->getValue();
    }

    /**
     * Render an element using a view helper
     *
     * Determine view helper from 'viewHelper' option, or, if none set, from
     * the element type. Then call as
     * helper($element->getName(), $element->getValue(), $element->getAttribs())
     *
     * @param  string $content
     * @return string
     * @throws ZfC_DataTable_Decorator_Exception if element or view are not registered
     */
    public function render($content)
    {
        $element = $this->getElement();

        $view = $element->getView();
        if (null === $view) {
            require_once 'ZfC/DataTable/Decorator/Exception.php';
            throw new ZfC_DataTable_Decorator_Exception('ViewHelper decorator cannot render without a registered view object');
        }

        $helper        = $this->getHelper();

        $helperObject  = $view->getHelper($helper);
        if (method_exists($helperObject, 'setTranslator')) {
            $helperObject->setTranslator($element->getTranslator());
        }

        $helperObject->populate($helper, $element);
        return $view->$helper();

    }
}
