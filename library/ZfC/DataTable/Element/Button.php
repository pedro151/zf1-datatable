<?php

class ZfC_DataTable_Element_Button extends ZfC_DataTable_Element
{

    /**
     * Default form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'DataTableButton';

    public function __construct ( $spec , $options = null , $url = '' )
    {
        parent::__construct ( $spec , $options );
        $this->url = $url;
    }

    public function init ()
    {
        $this->addFilters ( array ( 'StripTags' , 'StringTrim' ) );
    }

    /**
     * adiciona na Url um parametro que contenha no DataTable
     *
     * @param string $name
     *
     * @return $this
     */
    public function addParam ( $name )
    {
        if ( is_string ( $name ) )
        {
            $arrName = $this->getAttrib ( 'paramJs' );
            $arrName[] = $name;
            $this->setAttrib ( 'paramJs' , $arrName );
        }

        return $this;
    }

    /**
     * adiciona na Url varios parametros que contenha no DataTable
     *
     * @param array $arrName
     *
     * @return $this
     */
    public function addParams ( $arrName )
    {
        if ( is_array ( $arrName ) )
        {
            $arrName = $this->getAttrib ( 'paramJs' );
            foreach ( $arrName as $name )
            {
                $arrName[] = $name;
            }
            $this->setAttrib ( 'paramJs' , $arrName );
        }

        return $this;
    }

    public function setUrl ( array $urlOptions = array () , $name = null , $reset = false , $encode = true )
    {
        $urlOptions[] = '';
        $url = $this->getView ()->url ( $urlOptions , $name , $reset , $encode );
        $url = substr_replace ( $url , "" , strlen ( $url ) - 3 );
        $this->setAttrib ( 'url' , $url );

        return $this;
    }

    public function setModal ( $message )
    {
        $this->modal = $message;

        return $this;
    }
}
