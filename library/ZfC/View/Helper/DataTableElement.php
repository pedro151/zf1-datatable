<?php

/**
 * Base helper for form elements.  Extend this, don't use it on its own.
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class ZfC_View_Helper_DataTableElement extends Zend_View_Helper_HtmlElement
{
    /**
     * @var Zend_Translate_Adapter|null
     */
    protected $_translator;

    protected $_options;

    protected $_name;

    protected $_id;

    protected $_value;

    private $_element;

    /**
     * Get translator
     *
     * @return Zend_Translate_Adapter|null
     */
    public function getTranslator ()
    {
        return $this->_translator;
    }

    /**
     * Set translator
     *
     * @param  Zend_Translate|Zend_Translate_Adapter|null $translator
     *
     * @return Zend_View_Helper_FormElement
     */
    public function setTranslator ( $translator = null )
    {
        if ( null === $translator )
        {
            $this->_translator = null;
        } elseif ( $translator instanceof Zend_Translate_Adapter )
        {
            $this->_translator = $translator;
        } elseif ( $translator instanceof Zend_Translate )
        {
            $this->_translator = $translator->getAdapter ();
        } else
        {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception( 'Invalid translator specified' );
            $e->setView ( $this->view );
            throw $e;
        }

        return $this;
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
        $name , $value = null , $attribs = null ,
        $options = null
    ){
        // the baseline info.  note that $name serves a dual purpose;
        // if an array, it's an element info array that will override
        // these baseline values.  as such, ignore it for the 'name'
        // if it's an array.
        $info = array (
            'name'    => is_array ( $name ) ? '' : $name ,
            'id'      => is_array ( $name ) ? '' : $name ,
            'value'   => $value ,
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
                $info[ 'id' ] = trim ( strtr ( $info[ 'name' ] ,
                    array ( '[' => '-' , ']' => '' ) ) , '-' );
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

        // Determine escaping from attributes
        if ( array_key_exists ( 'escape' , $attribs ) )
        {
            $info[ 'escape' ] = (bool) $attribs[ 'escape' ];
        }

        // Determine listsetp from attributes
        if ( array_key_exists ( 'listsep' , $attribs ) )
        {
            $info[ 'listsep' ] = (string) $attribs[ 'listsep' ];
        }

        // Override name in info if specified in attribs
        if ( array_key_exists ( 'visible' , $attribs ) )
        {
            $this->setVisible ( $attribs[ 'visible' ] );
            unset( $attribs[ 'visible' ] );
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

    public function setOptions ( $options )
    {
        if ( is_null ( $options ) )
        {
            return $this;
        }
        if ( array_key_exists ( 'width' , $options )
             && ! is_null ( $options[ 'width' ] )
        )
        {
            $this->_options[ 'width' ] = $options[ 'width' ];
            unset( $options[ 'width' ] );
        }
        if ( array_key_exists ( 'class' , $options )
             && ! is_null ( $options[ 'class' ] )
        )
        {
            $this->_options[ 'className' ] = $options[ 'class' ];
            unset( $options[ 'class' ] );
        }
        if ( array_key_exists ( 'type' , $options ) && ! is_null ( $options[ 'type' ] ) )
        {
            $this->_options[ 'type' ] = $options[ 'type' ];
            unset( $options[ 'type' ] );
        }

        return $this;
    }

    public function getOptions ()
    {
        return $this->_options;
    }

    public function getOption ( $name )
    {
        if ( isset( $this->_options[ $name ] ) )
        {
            return $this->_options[ $name ];
        }
    }

    public function setOption ( $name , $value )
    {
        $this->_options[ $name ] = $value;

        return $this;
    }

    public function hasOptions ()
    {
        return (bool) $this->_options;
    }

    public function hasOption ( $option )
    {
        return isset( $this->_options[ $option ] );
    }

    public function attrJS ()
    {
        return array();
    }

    public function getElement ()
    {
        return $this->_element;
    }

    public function getElementAttribs ()
    {
        if ( null === ( $element = $this->getElement () ) )
        {
            return null;
        }

        $attribs = $element->getAttribs ();
        if ( isset( $attribs[ 'helper' ] ) )
        {
            unset( $attribs[ 'helper' ] );
        }

        if ( method_exists ( $element , 'getSeparator' ) )
        {
            if ( null !== ( $listsep = $element->getSeparator () ) )
            {
                $attribs[ 'listsep' ] = $listsep;
            }
        }

        if ( isset( $attribs[ 'id' ] ) )
        {
            return $attribs;
        }

        $id = $element->getName ();

        $element->setAttrib ( 'id' , $id );
        $attribs[ 'id' ] = $id;

        return $attribs;
    }

    public function populate ( $helper , ZfC_DataTable_Element $element )
    {
        $this->_helper = $helper;
        $this->_element = $element;
        $this->setOptions($element->options);
    }

    public function getName ()
    {
        return $this->_element->getFullyQualifiedName ();
    }

    public function getId ()
    {
        return $this->view->escape ( $this->_element->getId () );
    }

    public function getValue ()
    {
        if ( ! $this->_element instanceof ZfC_DataTable_Element )
        {
            $this->_value = null;
        }

        return $this->view->escape ( $this->_element->getValue () );
    }

    public function getOrder ()
    {
        return $this->_element->getOrder ();
    }

    public function getLabel ()
    {
        return $this->_element->getLabel ();
    }
}
