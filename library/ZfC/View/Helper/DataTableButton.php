<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Abstract class for extension
 */
require_once 'Zend/View/Helper/FormElement.php';

/**
 * Helper to generate a "text" element
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ZfC_View_Helper_DataTableButton extends ZfC_View_Helper_DataTableElement
{
    protected $_attribs;
    protected $_button=true;
    const BOOTBOX = '/components/bootbox/bootbox.js';

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
    public function DataTableButton ( $name , $label , $value = null , $attribs = null , $order = null )
    {
        $base = $this->view->baseUrl ();
        $this->jquery = $this->view->JQuery ();
        $this->jquery->enable ();
        $this->jquery->addJavascriptFile ( $base . self::BOOTBOX );

        $info = $this->_getInfo ( $name , $value , $attribs );
        extract ( $info ); // name, value, attribs, options, disable

        $this->setId($id)
            ->setValue($value);
        $this->_attribs = $attribs;
        $modal = '';

        if ( isset( $options[ 'modal' ] ) )
        {
            $modal = ' modal="' . $options[ 'modal' ] . '"';
        }

        $url = isset( $options [ "url" ] ) && is_array ( $options [ "url" ] )
            ? $options [ "url" ] : array ();
        $url = $this->view->url ( $url );
        $this->xhtml = '<th'
                 . ' id="' . $this->view->escape ( $id ) . '"'
                 . ' url="' . $url . '"'
                 . $modal
                 . ' name="' . $this->view->escape ( $name ) . '" >'
                 . $label
                 . '</th>';


        return $this;
    }

    /**
     * @param                                  $id
     * @param  ZfC_DataTable_Create[] $content
     */
    public function createJscript ( $attribs )
    {
        if ( ! isset( $attribs[ 'paramJs' ] ) )
        {
            return;
        }
        $json_param = json_encode ( $attribs[ 'paramJs' ] );
        $id = $attribs[ 'id' ];

        $_js = "bootbox.setDefaults({ locale: 'br'});"
               . "%s('#{main} tbody').on( 'click', 'td.col-button > span.{$id}', function () {"
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
}
