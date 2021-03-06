<?php

/** Zend_View_Helper_FormElement */
require_once 'Zend/View/Helper/FormElement.php';

class ZfC_View_Helper_DataTable extends Zend_View_Helper_HtmlElement
{
    const FILE_DATATABLE            = 'jquery.dataTables.min.js';
    const FOLDER_LOCAL              = '/components/datatables/media/js/';
    const FILE_DATATABLE_BOOTSTRAP  = '/components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.min.js';
    const FILE_DATATABLE_PIPELINE   = '/components/datatables-plugins/cache/pipelining.js';
    const FILE_DATATABLE_CSS        = '/components/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css';
    const DEFAULT_DATATABLE_VERSION = "1.10.11";
    const CDN_BASE_DATATABLE        = "http://cdn.datatables.net/";
    const CDN_SUBFOLDER_DATATABLE   = '/js/';

    protected $_version = self::DEFAULT_DATATABLE_VERSION;
    protected $_urlAjax;
    protected $_attribs;
    protected $_id;
    protected $cachePages;
    private   $_datatableLibraryPath;
    protected $_button  = false;
    protected $xhtml;
    private   $_type;

    /**
     * Render HTML form
     *
     * @param  string                 $name    Form name
     * @param  null|array             $attribs HTML form attributes
     * @param  ZfC_DataTable_Create[] $content content
     *
     * @return string
     */
    public function DataTable ( $name , $attribs = null , $content = false )
    {
        $info = $this->_getInfo ( $name , $attribs );
        extract ( $info );
        $this->_id = $id;
        $this->_attribs = $attribs;
        $this->createJscript ( $content );

        if ( ! empty( $id ) )
        {
            $id = ' id="' . $this->view->escape ( $id ) . '"';
        }

        if ( array_key_exists ( 'id' , $attribs ) && empty( $attribs[ 'id' ] ) )
        {
            unset( $attribs[ 'id' ] );
        }

        if ( ! empty( $name ) && ! ( $this->_isXhtml () && $this->_isStrictDoctype () ) )
        {
            $name = ' name="' . $this->view->escape ( $name ) . '"';
        }

        if ( array_key_exists ( 'name' , $attribs ) && empty( $attribs[ 'id' ] ) )
        {
            unset( $attribs[ 'id' ] );
        }

        $tfoot = '';
        if ( array_key_exists ( 'tfoot' , $attribs ) && $attribs[ 'tfoot' ] )
        {
            $tfoot = '<tfoot>'
                     . '<tr role="row">'
                     . $this->createContent ( $content )
                     . '</tr>'
                     . '</tfoot>';

        }

        $xhtml = '<table cellpadding="0" cellspacing="0" border="0" '
                 . $id
                 . $name
                 . $this->_htmlAttribs ( $attribs )
                 . '><thead>'
                 . '<tr role="row">'
                 . $this->createContent ( $content )
                 . '</tr></thead>'
                 . $tfoot
                 . '</table>';


        return array ( 'xhtml' => $xhtml );
    }

    public function createContent ( $content )
    {
        $xhtml = '';
        if ( false !== $content )
        {
            foreach ( $content as $key => $contentCreate )
            {
                if ( is_array ( $contentCreate )
                     && array_key_exists ( 'xhtml' , $contentCreate )
                )
                {
                    $xhtml .= $contentCreate[ 'xhtml' ];
                }
            }
        }

        return $xhtml;
    }

    /**
     * Set the version of the DataTable library used.
     *
     * @param string $version
     *
     * @return ZendX_JQuery_View_Helper_JQuery_Container
     */
    public function setVersion ( $version )
    {
        $this->_version = $version;

        return $this;
    }

    /**
     * Get the version used with the DataTable library
     *
     * @return string
     */
    public function getVersion ()
    {
        return $this->_version;
    }

    /**
     * Set path to local jQuery library
     *
     * @param  string $path
     *
     * @return ZendX_JQuery_View_Helper_JQuery_Container
     */
    public function setLocalPath ( $path )
    {
        $this->_datatableLibraryPath = (string) $path;

        return $this;
    }

    /**
     * Internal function that constructs the include path of the DataTable library.
     *
     * @return string
     */
    protected function _getDataTableLibraryPath ()
    {
        if ( $this->_datatableLibraryPath != null )
        {
            $source = $this->_datatableLibraryPath;
        } else
        {
            $source = self::CDN_BASE_DATATABLE .
                      $this->getVersion () .
                      self::CDN_SUBFOLDER_DATATABLE .
                      self::FILE_DATATABLE;
        }

        return $source;
    }

    /**
     * insere toda a estrutura e arquivos Javascript na pagina
     *
     * @param                                     $id
     * @param  ZfC_View_Helper_DataTableElement[] $content
     */
    public function createJscript ( $content )
    {
        $base = $this->view->baseUrl ();
        $this->jquery = $this->view->JQuery ();
        $this->jquery->enable ();
        $this->jquery->addJavascriptFile ( $this->_getDataTableLibraryPath () );
        $this->jquery->addJavascriptFile ( $base . self::FILE_DATATABLE_BOOTSTRAP );
        $this->jquery->addStylesheet ( $base . self::FILE_DATATABLE_CSS );

        $paramsJson = $this->_createDataTableParam ( $content );

        $js = sprintf (
            'var %s = %s("%s").dataTable(%s);' ,
            $this->_id ,
            ZendX_JQuery_View_Helper_JQuery::getJQueryHandler () ,
            'table#' . $this->_id ,
            json_encode ( $paramsJson )
        );

        $initComplete = '';
        if ( array_key_exists ( 'tfoot' , $this->_attribs ) && $this->_attribs[ 'tfoot' ] )
        {
            $initComplete = '"initComplete" : function () {'
                            . 'this.api().columns().every( function () {'
                            . 'var column = this;'
                            . 'if($(column.footer()).text()==""){ return; }'
                            . 'var select = $(\'<select><option value=\"\"></option></select>\')'
                            . '.appendTo( $(column.footer()).empty() )'
                            . '.on( \'change\', function () {'
                            . 'var val = $.fn.dataTable.util.escapeRegex('
                            . '$(this).val()'
                            . ');'
                            . 'column.search( val ? \'^\'+val+\'$\' : \'\', true, false ).draw();'
                            . '} );'
                            . 'column.data().unique().sort().each( function ( d, j ) {'
                            . 'if(typeof(d)!="object"){'
                            . 'select.append( \'<option value=\"\'+d+\'\">\'+d+\'</option>\' )'
                            . "}"
                            . '} );'
                            . '} );'
                            . '}';

        }

        if ( $this->isCached () )
        {
            $this->jquery->addJavascriptFile ( $base . self::FILE_DATATABLE_PIPELINE );

            $paramsAjax = array (
                "pages"  => $this->getPages () , // number of pages to cache
                "url"    => $this->getAjax () ,// script url
                "method" => $this->getType () ,// Ajax HTTP method
            );

            $js = sprintf (
                'var %s = %s("%s").dataTable({%s,"%s" : %2$s.fn.dataTable.pipeline(%s),'
                . $initComplete . '});' ,
                $this->_id ,
                ZendX_JQuery_View_Helper_JQuery::getJQueryHandler () ,
                'table#' . $this->_id ,
                rtrim ( ltrim ( Zend_Json::encode ( $paramsJson ) , "{" ) , "}" ) ,
                'ajax' ,
                Zend_Json::encode ( $paramsAjax )
            );
        }

        $this->jquery->addOnLoad ( $js );
        $this->insertMainIdJs ( $content );
    }

    /**
     * insere o conteudo JavaScript de cada elemento na pagina
     *
     * @param ZfComplement_DataTable_Create[] $content
     */
    public function insertMainIdJs ( $content )
    {
        foreach ( $content as $index => $item )
        {
            if ( empty( $item[ 'JS' ] ) )
            {
                continue;
            }
            $this->jquery->addOnLoad ( str_replace ( '{main}' , $this->_id , $item[ "JS" ] ) );
        }
    }

    /**
     * cria a estrutura de parametros do DataTable
     *
     * @param ZfC_View_Helper_DataTableElement[] $content
     */
    public function _createDataTableParam ( $content )
    {
        $paramsJs = array (
            "columnDefs" => array () ,
            "ordering"   => false
        );

        $paramsJs = array_merge ( $paramsJs , $this->_attribs );

        if ( $this->hasAjax () )
        {
            $paramsJs[ "processing" ] = true;
            $paramsJs[ "serverSide" ] = true;
            if ( ! $this->isCached () )
            {
                $paramsJs[ 'ajax' ] = array (
                    "url"  => $this->getAjax () ,
                    "type" => $this->getType ()
                );
            }
        }

        if ( ! is_array ( $content ) )
        {
            return json_encode ( $paramsJs );
        }

        foreach ( $content as $key => $objCreate )
        {
            if ( is_array ( $objCreate ) && array_key_exists ( 'paramJs' , $objCreate ) )
            {
                $paramsJs[ "columns" ][ $key ] = $objCreate[ "paramJs" ];
            }

        }

        return $paramsJs;
    }

    /**
     * Converts parameter arguments to an element info array.
     *
     * E.g, formExample($name, $value, $attribs, $options, $listsep) is
     * the same thing as formExample(array('name' => ...)).
     *
     * Note that you cannot pass a 'disable' param; you need to pass
     * it as an 'attribs' key.
     *
     * @access protected
     *
     * @return array An element info array with keys for name, value,
     * attribs, options, listsep, disable, and escape.
     */
    protected function _getInfo (
        $name ,
        $attribs = null ,
        $options = null
    ){
        // the baseline info.  note that $name serves a dual purpose;
        // if an array, it's an element info array that will override
        // these baseline values.  as such, ignore it for the 'name'
        // if it's an array.
        $info = array (
            'name'    => is_array ( $name ) ? '' : $name ,
            'id'      => is_array ( $name ) ? '' : $name ,
            'attribs' => $attribs ,
            'options' => $options ,
            'escape'  => true ,
        );

        // override with named args
        if ( is_array ( $name ) )
        {
            // only set keys that are already in info
            foreach ( $info as $key => $val )
            {
                if ( isset( $name[ $key ] ) )
                {
                    $info[ $key ] = $name[ $key ];
                }
            }

            // If all helper options are passed as an array, attribs may have
            // been as well
            if ( null === $attribs )
            {
                $attribs = $info[ 'attribs' ];
            }
        }

        $attribs = (array) $attribs;


        // Set ID for element
        if ( array_key_exists ( 'id' , $attribs ) )
        {
            $info[ 'id' ] = (string) $attribs[ 'id' ];
        } else
        {
            if ( '' !== $info[ 'name' ] )
            {
                $info[ 'id' ] = trim (
                    strtr (
                        $info[ 'name' ] ,
                        array ( '[' => '-' , ']' => '' )
                    ) ,
                    '-'
                );
            }
        }

        // Remove NULL name attribute override
        if ( array_key_exists ( 'name' , $attribs ) && is_null ( $attribs[ 'name' ] ) )
        {
            unset( $attribs[ 'name' ] );
        }

        // Override name in info if specified in attribs
        if ( array_key_exists ( 'name' , $attribs )
             && $attribs[ 'name' ] != $info[ 'name' ]
        )
        {
            $info[ 'name' ] = $attribs[ 'name' ];
        }

        if ( array_key_exists ( 'ajax' , $attribs ) && is_null ( $attribs[ 'ajax' ] ) )
        {
            unset( $attribs[ 'ajax' ] );
        }

        // Override name in info if specified in attribs
        if ( array_key_exists ( 'ajax' , $attribs ) )
        {
            $this->setAjax ( $attribs[ 'ajax' ] );

        }

        if ( array_key_exists ( 'method' , $attribs )
             && array_key_exists ( 'ajax' , $attribs )
             && is_null ( $attribs[ 'ajax' ] )
        )
        {
            unset( $attribs[ 'method' ] );
            unset( $attribs[ 'ajax' ] );
        }


        // Override name in info if specified in attribs
        if ( array_key_exists ( 'method' , $attribs ) )
        {
            $this->setType ( $attribs[ 'method' ] );
            unset( $attribs[ 'method' ] );
        }

        // Override name in info if specified in attribs
        if ( array_key_exists ( 'cache' , $attribs )
             && array_key_exists ( 'pages' , $attribs[ 'cache' ] )
        )
        {
            $this->setPages ( $attribs[ 'cache' ][ 'pages' ] );
            unset( $attribs[ 'cache' ] );
        }


        // Determine escaping from attributes
        if ( array_key_exists ( 'escape' , $attribs ) )
        {
            $info[ 'escape' ] = (bool) $attribs[ 'escape' ];
        }


        // Remove attribs that might overwrite the other keys. We do this LAST
        // because we needed the other attribs values earlier.
        foreach ( $info as $key => $val )
        {
            if ( array_key_exists ( $key , $attribs ) )
            {
                unset( $attribs[ $key ] );
            }
        }
        $info[ 'attribs' ] = $attribs;

        // done!
        return $info;
    }

    public function setAjax ( $urlAjax )
    {
        $this->_urlAjax = $urlAjax;
    }

    public function getAjax ()
    {
        return $this->_urlAjax;
    }

    public function hasAjax ()
    {
        return (bool) $this->_urlAjax;
    }

    public function setPages ( $pages )
    {
        $this->cachePages = (int) $pages;
    }

    public function getPages ()
    {
        return (int) $this->cachePages;
    }

    public function isCached ()
    {
        return (bool) $this->cachePages;
    }

    public function getId ()
    {
        return $this->_id;
    }

    public function setType ( $type )
    {
        $this->_type = $type;
    }

    public function getType ()
    {
        return $this->_type ? : 'GET';
    }

}
