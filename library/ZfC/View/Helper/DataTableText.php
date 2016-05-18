<?php


/**
 * Abstract class for extension
 */


/**
 * Helper to generate a "text" element
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ZfC_View_Helper_DataTableText extends ZfC_View_Helper_DataTableElement
{

    /**
     * Generates a 'text' element.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     *                              array, all other parameters are ignored, and the array elements
     *                              are used in place of added parameters.
     *
     * @param mixed $value The element value.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function DataTableText()
    {

        $xhtml = '<th'
            . ' id="' . $this->getId() . '"'
           // . $this->_htmlAttribs($attribs)
            . ' name="' . $this->getName() . '" >'
            . $this->getLabel()
            . '</th>';
        $this->setContent($xhtml);
        return $this;
    }
}
