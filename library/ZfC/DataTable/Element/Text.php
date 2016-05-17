<?php

class ZfC_DataTable_Element_Text extends ZfC_DataTable_Element
{
    /**
     * Default form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'DataTableText';

    public function init ()
    {
        $this->addFilters ( array ( 'StripTags' , 'StringTrim' ) );
    }
}
