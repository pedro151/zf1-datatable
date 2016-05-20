<?php


/**
 * Abstract class for extension
 */


class ZfC_View_Helper_DataTableButton extends ZfC_View_Helper_DataTableElement
{
    protected $_attribs;
    protected $_button = true;
    const BOOTBOX         = '/components/bootbox/bootbox.js';
    const BOOTBOX_DEFAULT = "bootbox.setDefaults({ locale: 'br'});";

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
    public function DataTableButton ()
    {
        $base = $this->view->baseUrl ();
        $this->jquery = $this->view->JQuery ();
        $this->jquery->enable ();
        $this->jquery->addJavascriptFile ( $base . self::BOOTBOX );
        $this->jquery->addOnLoad ( self::BOOTBOX_DEFAULT );

        $modal = '';
        if ( $this->hasOption ( 'modal' ) )
        {
            $modal = ' modal="' . $this->getOption ( 'modal' ) . '"';
        }

        $url = $this->hasOption ( "url" ) && is_array ( $this->getOption ( "url" ) )
            ? $this->getOption ( "url" ) : array ();

        $url = $this->view->url ( $url );
        $xhtml = '<th'
                 . ' id="' . $this->getId () . '"'
                 . ' url="' . $url . '"'
                 . $modal
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
        $classButtom = $this->hasOption ( 'className' )
            ? $this->getOption ( 'className' ) : 'btn-primary';
        $whidth = $this->hasOption ( 'width' )
            ? $this->getOption ( 'width' ) : '5%';
        $this->setOption ( 'width' , $whidth );

        $return = array ();
        if ( $this->hasOptions () )
        {
            foreach ( $this->getOptions () as $opcao => $value )
            {
                if ( $opcao === 'className' )
                {
                    continue;
                }
                $return[ $opcao ] = $value;
            }
        }

        $return += array (
            "name"           => $this->getId () ,
            "searchable"     => false ,
            "className"      => 'col-button' ,
            "data"           => null ,
            "defaultContent" => '<span class="btn '
                                . $this->getId ()
                                . ' '
                                . $classButtom
                                . '">' .
                                $this->getValue ()
                                . '</span>'
        );

        return $return;
    }

    /**
     * @return string|void
     */
    public function createJscript ()
    {
        $attribs = $this->getElementAttribs ();
        if ( ! isset( $attribs[ 'paramJs' ] ) )
        {
            return;
        }

        $json_param = json_encode ( $attribs[ 'paramJs' ] );
        $id = $attribs[ 'id' ];

        $_js = "%s('#{main} tbody').on( 'click', 'td.col-button > span.{$id}', function () {"
               . "var table = {main}.DataTable();"
               . "var _cell =  $(this).parent();"
               . "var rowIdx = table.cell(_cell ).index().row;"
               . "var columns={$json_param};"
               . "var columnIdx=[];"
               . "var header = table.column($(_cell).index()).header();"
               . "var \$header = $(header);"
               . "var url = \$header.attr('url');"
               . "$.each(columns, function(k,v){"
               . "columnIdx.push(table.column( v+':name' ).index());"
               . "});"
               . "var columnDataHeader = table.columns(columnIdx).header();"
               . "var columnData = table.cells( rowIdx,columnIdx).data();"
               . "for (i = 0; i < columnDataHeader.length; i++) {"
               . "id = $(columnDataHeader[i]).attr('id');"

               . "url += '/'+id+'/'+columnData[i];"
               . "}"
               . "if(\$header.attr('modal')){"
               . "bootbox.confirm(\$header.attr('modal'), function(result) {"
               . "if(result){"
               . "window.location.href =  url;"
               . "}"
               . "});"
               . "}else{"
               . " window.location.href = url;"
               . "}"
               . "} );";


        return sprintf (
            $_js ,
            ZendX_JQuery_View_Helper_JQuery::getJQueryHandler ()
        );
    }

    /**
     * insere o conteudo JavaScript de cada elemento na pagina
     *
     * @param ZfC_DataTable_Create[] $content
     */
    public function buttomJs ( $content )
    {
        if ( ! is_array ( $content ) )
        {
            return;
        }

        foreach ( $content as $key => $objCreate )
        {
            if ( $objCreate->hasJscript () )
            {
                $this->jquery->addOnLoad ( str_replace ( '{main}' , $this->_id , $objCreate->getJscript () ) );
            }
        }

    }
}
