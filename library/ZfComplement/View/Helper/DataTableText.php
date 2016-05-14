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
class ZfComplement_View_Helper_DataTableText extends ZfComplement_View_Helper_DataTable
{
    /**
     * Generates a 'text' element.
     *
     * @access public
     *
     * @param string|array $name    If a string, the element name.  If an
     *                              array, all other parameters are ignored, and the array elements
     *                              are used in place of added parameters.
     *
     * @param mixed        $value   The element value.
     *
     * @param array        $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function DataTableText ( $name , $label , $value = null , $attribs = null , $order=null )
    {
        $info = $this->_getInfo ( $name , $value , $attribs );
        extract ( $info ); // name, value, attribs, options, disable

        $xhtml = '<th'
            . ' id="' . $this->view->escape ( $id ) . '"'
            . $this->_htmlAttribs ( $attribs )
            . ' name="' . $this->view->escape ( $name ) . '" >'
            . $this->view->escape ( $label )
            . '</th>';

        $create = new ZfComplement_DataTable_Create();
        $create->setContent ( $xhtml )
               ->setDesabled ( $disable )
               ->setId ( $this->view->escape ( $id ) )
               ->setValue ( $this->view->escape ( $value ) )
               ->setOptions ($options);

        return $create;
    }
}
