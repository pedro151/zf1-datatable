<?php

class ZfC_DataTable_Element_Hidden extends ZfC_DataTable_Element
{
    /**
     * Default form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'DataTableHidden';

    public function init ()
    {
        $this->addFilters ( array ( 'StripTags' , 'StringTrim' ) );
    }
}
