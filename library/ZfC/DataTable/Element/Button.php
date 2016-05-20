<?php

class ZfC_DataTable_Element_Button extends ZfC_DataTable_Element
{

    /**
     * Default form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'DataTableButton';

    public function __construct ( $spec , $options = null, $url='' )
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
     * @return $this
     */
    public function addParam ( $name )
    {
        if ( !empty( $name ) )
        {
            if ( !empty( $this->paramJs ) )
            {
                $arrName = json_decode ( $this->paramJs );
                $name    = array_merge ( (array) $name , $arrName );
            }
            $this->paramJs = json_encode ( (array) $name );
        }

        return $this;
    }

    /**
     * adiciona na Url varios parametros que contenha no DataTable
     *
     * @param array $arrName
     * @return $this
     */
    public function addParams ( $arrName )
    {
        if ( is_array ( $arrName ) )
        {
            foreach ( $arrName as $name )
            {
                $this->addParam ( $name );
            }
        }

        return $this;
    }

    public function setUrl ( $url )
    {
        $this->url  = $url;

        return $this;
    }

    public function setModal ( $message )
    {
        $this->modal = $message;

        return $this;
    }
}
