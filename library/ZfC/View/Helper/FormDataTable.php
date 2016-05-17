<?php

/** Zend_View_Helper_FormElement */
require_once 'Zend/View/Helper/FormElement.php';

/**
 * @see ZendX_JQuery
 */
require_once "ZendX/JQuery.php";

class ZfC_View_Helper_FormDataTable extends ZfC_View_Helper_Form
{


    public function FormDataTable ( $name , $attribs = null , $content = false , $elements = null, $datatable = null )
    {

        $js = sprintf (
            "%1\$s('button#btPesquisar').click( function(){"
            . "var table = %1\$s('table[id=%2\$s]').DataTable();"
            . "var form = $('form[id=%3\$s]');"
            . "form.find('input[type=text]').each(function(k,v){"
            . "var columnIdx=null;"
            . "var name = $(v).attr('name');"
            . "columnIdx= table.column( name+':name' ).index();"
            . "table.column(columnIdx).search($(v).val());"
            . "});"
            . "if(form.validate().valid()){"
            . "table.draw();"
            . "}"
            . "});" ,
            \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler () ,
            $datatable->getId () ,
            $attribs['id']
        );

        $this->jquery->addOnLoad ( $js );

              return parent::form ( $name , $attribs , $content , $elements  );
    }

}
