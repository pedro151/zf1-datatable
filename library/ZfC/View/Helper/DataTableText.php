<?php


class ZfC_View_Helper_DataTableText extends ZfC_View_Helper_DataTableElement
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
    public function DataTableText ()
    {
        $xhtml = '<th'
                 . ' id="' . $this->getId () . '"'
                 // . $this->_htmlAttribs($attribs)
                 . ' name="' . $this->getName () . '" >'
                 . $this->getLabel ()
                 . '</th>';

        return array (
            'xhtml'   => $xhtml ,
            'paramJs' => $this->attrJS () ,
            'JS'      => $this->createJscript ()
        );
    }

    public function attrJS ()
    {
        $return = array ();
        if ( $this->hasOptions () )
        {
            $return = $this->getOptions ();
        }
        $return += array (
            "name" => $this->getId () ,
            "data" => $this->getId ()
        );

        return $return;
    }
}
