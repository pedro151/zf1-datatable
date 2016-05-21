<?php

/**
 * ZfC_DataTable
 *
 * @category   ZfC
 * @package    ZfC_DataTable
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
class ZfC_DataTable implements Iterator, Countable
{
    /**#@+
     * Plugin loader type constants
     */
    const DECORATOR = 'DECORATOR';
    const ELEMENT = 'ELEMENT';

    const METHOD_GET    = 'get';
    const METHOD_POST   = 'post';

    /**
     * Decorators for rendering
     *
     * @var array
     */
    protected $_decorators = array();

    /**
     * Should we disable loading the default decorators?
     *
     * @var bool
     */
    protected $_disableLoadDefaultDecorators = false;

    /**
     * Form metadata and attributes
     *
     * @var array
     */
    protected $_attribs = array();

    /**
     * Form description
     *
     * @var string
     */
    protected $_description;

    /**
     * Global decorators to apply to all elements
     *
     * @var null|array
     */
    protected $_elementDecorators;

    /**
     * Prefix paths to use when creating elements
     *
     * @var array
     */
    protected $_elementPrefixPaths = array();

    /**
     * Form elements
     *
     * @var array
     */
    protected $_elements = array();

    /**
     * Form order
     *
     * @var int|null
     */
    protected $_columnOrder;

    /**
     * Whether or not form elements are members of an array
     *
     * @var bool
     */
    protected $_isArray = false;

    /**
     * Form legend
     *
     * @var string
     */
    protected $_legend;

    /**
     * Plugin loaders
     *
     * @var array
     */
    protected $_loaders = array();

    /**
     * Allowed form methods
     *
     * @var array
     */
    protected $_methods = array('delete', 'get', 'post', 'put');

    /**
     * Order in which to display and iterate elements
     *
     * @var array
     */
    protected $_order = array();

    /**
     * Whether internal order has been updated or not
     *
     * @var bool
     */
    protected $_orderUpdated = false;

    /**
     * @var Zend_Translate
     */
    protected $_translator;

    /**
     * Global default translation adapter
     *
     * @var Zend_Translate
     */
    protected static $_translatorDefault;

    /**
     * is the translator disabled?
     *
     * @var bool
     */
    protected $_translatorDisabled = false;

    /**
     * @var Zend_View_Interface
     */
    protected $_view;

    /**
     * @var bool
     */
    protected $_isRendered = false;

    /**
     * @var string
     */
    protected $_urlajax = null;

    /**
     * Constructor
     *
     * Registers form view helper as decorator
     *
     * @param mixed $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif ($options instanceof Zend_Config) {
            $this->setConfig($options);
        } else {
            $front = Zend_Controller_Front::getInstance();
            $bootstrap = $front->getParam('bootstrap');

            if (!is_null($bootstrap) && $bootstrap->hasOption('datatable')) {
                $this->setOptions($bootstrap->getOption('datatable'));
            }
        }

        // Extensions...
        $this->init();

        $this->loadDefaultDecorators();

        $this->getView()->addHelperPath('ZfC/View/Helper', 'ZfC_View_Helper');

    }

    /**
     * Clone form object and all children
     *
     * @return void
     */
    public function __clone()
    {
        $elements = array();
        foreach ($this->getElements() as $name => $element) {
            $elements[] = clone $element;
        }
        $this->setElements($elements);

    }

    /**
     * Initialize form (used by extending classes)
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Set form state from options array
     *
     * @param  array $options
     *
     * @return ZfC_DataTable
     */
    public function setOptions(array $options)
    {
        if (isset($options['prefixPath'])) {
            $this->addPrefixPaths($options['prefixPath']);
            unset($options['prefixPath']);
        }

        if (isset($options['elementPrefixPath'])) {
            $this->addElementPrefixPaths($options['elementPrefixPath']);
            unset($options['elementPrefixPath']);
        }


        if (isset($options['elements'])) {
            $this->setElements($options['elements']);
            unset($options['elements']);
        }


        if (isset($options['attribs'])) {
            $this->addAttribs($options['attribs']);
            unset($options['attribs']);
        }

        if (isset($options['ajax'])) {
            $this->setAjax($options['ajax']);
            unset($options['ajax']);
        }

        $forbidden = array(
            'Options', 'Config', 'PluginLoader', 'Translator',
            'Attrib', 'Default', 'Ajax',
        );

        foreach ($options as $key => $value) {
            $normalized = ucfirst($key);
            if (in_array($normalized, $forbidden)) {
                continue;
            }

            $method = 'set' . $normalized;
            if (method_exists($this, $method)) {
                if ($normalized == 'View'
                    && !($value instanceof Zend_View_Interface)
                ) {
                    continue;
                }
                $this->$method($value);
            } else {
                $this->setAttrib($key, $value);
            }
        }

        return $this;
    }

    /**
     * Set form state from config object
     *
     * @param  Zend_Config $config
     *
     * @return ZfC_DataTable
     */
    public function setConfig(Zend_Config $config)
    {
        return $this->setOptions($config->toArray());
    }


    // Loaders

    /**
     * Set plugin loaders for use with decorators and elements
     *
     * @param  Zend_Loader_PluginLoader_Interface $loader
     * @param  string $type 'decorator' or 'element'
     *
     * @return ZfC_DataTable
     * @throws ZfC_DataTable_Exception on invalid type
     */
    public function setPluginLoader(Zend_Loader_PluginLoader_Interface $loader, $type = null)
    {
        $type = strtoupper($type);
        switch ($type) {
            case self::DECORATOR:
            case self::ELEMENT:
                $this->_loaders[$type] = $loader;

                return $this;
            default:
                require_once 'ZfC/DataTable/Exception.php';
                throw new ZfC_DataTable_Exception(
                    sprintf('Invalid type "%s" provided to setPluginLoader()', $type)
                );
        }
    }

    /**
     * Retrieve plugin loader for given type
     *
     * $type may be one of:
     * - decorator
     * - element
     *
     * If a plugin loader does not exist for the given type, defaults are
     * created.
     *
     * @param  string $type
     *
     * @return Zend_Loader_PluginLoader_Interface
     * @throws ZfC_DataTable_Exception
     */
    public function getPluginLoader($type = null)
    {
        $type = strtoupper($type);
        if (!isset($this->_loaders[$type])) {
            switch ($type) {
                case self::DECORATOR:
                    $prefixSegment = 'DataTable_Decorator';
                    $pathSegment = 'DataTable/Decorator';
                    break;
                case self::ELEMENT:
                    $prefixSegment = 'DataTable_Element';
                    $pathSegment = 'DataTable/Element';
                    break;
                default:
                    require_once 'ZfC/DataTable/Exception.php';
                    throw new ZfC_DataTable_Exception(
                        sprintf('Invalid type "%s" provided to getPluginLoader()', $type)
                    );
            }

            require_once 'Zend/Loader/PluginLoader.php';
            $this->_loaders[$type] = new Zend_Loader_PluginLoader(
                array('ZfC_' . $prefixSegment . '_' => 'ZfC/' . $pathSegment . '/')
            );
        }

        return $this->_loaders[$type];
    }

    /**
     * Add prefix path for plugin loader
     *
     * If no $type specified, assumes it is a base path for both filters and
     * validators, and sets each according to the following rules:
     * - decorators: $prefix = $prefix . '_Decorator'
     * - elements: $prefix = $prefix . '_Element'
     *
     * Otherwise, the path prefix is set on the appropriate plugin loader.
     *
     * If $type is 'decorator', sets the path in the decorator plugin loader
     * for all elements. Additionally, if no $type is provided,
     * the prefix and path is added to both decorator and element
     * plugin loader with following settings:
     * $prefix . '_Decorator', $path . '/Decorator/'
     * $prefix . '_Element', $path . '/Element/'
     *
     * @param  string $prefix
     * @param  string $path
     * @param  string $type
     *
     * @return ZfC_DataTable
     * @throws ZfC_DataTable_Exception for invalid type
     */
    public function addPrefixPath($prefix, $path, $type = null)
    {
        $type = strtoupper($type);
        switch ($type) {
            case self::DECORATOR:
            case self::ELEMENT:
                $loader = $this->getPluginLoader($type);
                $loader->addPrefixPath($prefix, $path);

                return $this;
            case null:
                $nsSeparator = (false !== strpos($prefix, '\\')) ? '\\' : '_';
                $prefix = rtrim($prefix, $nsSeparator);
                $path = rtrim($path, DIRECTORY_SEPARATOR);
                foreach (array(self::DECORATOR, self::ELEMENT) as $type) {
                    $cType = ucfirst(strtolower($type));
                    $pluginPath = $path . DIRECTORY_SEPARATOR . $cType
                        . DIRECTORY_SEPARATOR;
                    $pluginPrefix = $prefix . $nsSeparator . $cType;
                    $loader = $this->getPluginLoader($type);
                    $loader->addPrefixPath($pluginPrefix, $pluginPath);
                }

                return $this;
            default:
                require_once 'ZfC/DataTable/Exception.php';
                throw new ZfC_DataTable_Exception(
                    sprintf('Invalid type "%s" provided to getPluginLoader()', $type)
                );
        }
    }

    /**
     * Add many prefix paths at once
     *
     * @param  array $spec
     *
     * @return ZfC_DataTable
     */
    public function addPrefixPaths(array $spec)
    {
        if (isset($spec['prefix']) && isset($spec['path'])) {
            return $this->addPrefixPath($spec['prefix'], $spec['path']);
        }
        foreach ($spec as $type => $paths) {
            if (is_numeric($type) && is_array($paths)) {
                $type = null;
                if (isset($paths['prefix']) && isset($paths['path'])) {
                    if (isset($paths['type'])) {
                        $type = $paths['type'];
                    }
                    $this->addPrefixPath($paths['prefix'], $paths['path'], $type);
                }
            } elseif (!is_numeric($type)) {
                if (!isset($paths['prefix']) || !isset($paths['path'])) {
                    continue;
                }
                $this->addPrefixPath($paths['prefix'], $paths['path'], $type);
            }
        }

        return $this;
    }

    /**
     * Add prefix path for all elements
     *
     * @param  string $prefix
     * @param  string $path
     * @param  string $type
     *
     * @return ZfC_DataTable
     */
    public function addElementPrefixPath($prefix, $path, $type = null)
    {
        $this->_elementPrefixPaths[] = array(
            'prefix' => $prefix,
            'path' => $path,
            'type' => $type,
        );

        /** @var ZfC_DataTable_Element $element */
        foreach ($this->getElements() as $element) {
            $element->addPrefixPath($prefix, $path, $type);
        }


        return $this;
    }

    /**
     * Add prefix paths for all elements
     *
     * @param  array $spec
     *
     * @return ZfC_DataTable
     */
    public function addElementPrefixPaths(array $spec)
    {
        $this->_elementPrefixPaths = $this->_elementPrefixPaths + $spec;

        /** @var ZfC_DataTable_Element $element */
        foreach ($this->getElements() as $element) {
            $element->addPrefixPaths($spec);
        }

        return $this;
    }

    // Form metadata:

    /**
     * Set form attribute
     *
     * @param  string $key
     * @param  mixed $value
     *
     * @return ZfC_DataTable
     */
    public function setAttrib($key, $value)
    {
        $key = (string)$key;
        $this->_attribs[$key] = $value;

        return $this;
    }

    /**
     * Add multiple form attributes at once
     *
     * @param  array $attribs
     *
     * @return ZfC_DataTable
     */
    public function addAttribs(array $attribs)
    {
        foreach ($attribs as $key => $value) {
            $this->setAttrib($key, $value);
        }

        return $this;
    }

    /**
     * Set multiple form attributes at once
     *
     * Overwrites any previously set attributes.
     *
     * @param  array $attribs
     *
     * @return ZfC_DataTable
     */
    public function setAttribs(array $attribs)
    {
        $this->clearAttribs();

        return $this->addAttribs($attribs);
    }

    /**
     * Retrieve a single form attribute
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function getAttrib($key)
    {
        $key = (string)$key;
        if (!isset($this->_attribs[$key])) {
            return null;
        }

        return $this->_attribs[$key];
    }

    /**
     * Retrieve all form attributes/metadata
     *
     * @return array
     */
    public function getAttribs()
    {
        return $this->_attribs;
    }

    /**
     * Remove attribute
     *
     * @param  string $key
     *
     * @return bool
     */
    public function removeAttrib($key)
    {
        if (isset($this->_attribs[$key])) {
            unset($this->_attribs[$key]);

            return true;
        }

        return false;
    }

    /**
     * Clear all form attributes
     *
     * @return ZfC_DataTable
     */
    public function clearAttribs()
    {
        $this->_attribs = array();

        return $this;
    }

    /**
     * Filter a name to only allow valid variable characters
     *
     * @param  string $value
     * @param  bool $allowBrackets
     *
     * @return string
     */
    public function filterName($value, $allowBrackets = false)
    {
        $charset = '^a-zA-Z0-9_\x7f-\xff';
        if ($allowBrackets) {
            $charset .= '\[\]';
        }

        return preg_replace('/[' . $charset . ']/', '', (string)$value);
    }

    /**
     * Set form name
     *
     * @param  string $name
     *
     * @return ZfC_DataTable
     * @throws ZfC_DataTable_Exception
     */
    public function setName($name)
    {
        $name = $this->filterName($name);
        if ('' === (string)$name) {
            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception(
                'Invalid name provided; must contain only valid variable characters and be non-empty'
            );
        }

        return $this->setAttrib('name', $name);
    }

    /**
     * Get name attribute
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->getAttrib('name');
    }

    /**
     * Get fully qualified name
     *
     * Places name as subitem of array and/or appends brackets.
     *
     * @return string
     */
    public function getFullyQualifiedName()
    {
        return $this->getName();
    }

    /**
     * Get element id
     *
     * @return string
     */
    public function getId()
    {
        if (null !== ($id = $this->getAttrib('id'))) {
            return $id;
        }

        $id = $this->getFullyQualifiedName();

        // Bail early if no array notation detected
        if (!strstr($id, '[')) {
            return $id;
        }

        // Strip array notation
        if ('[]' == substr($id, -2)) {
            $id = substr($id, 0, strlen($id) - 2);
        }
        $id = str_replace('][', '-', $id);
        $id = str_replace(array(']', '['), '-', $id);
        $id = trim($id, '-');

        return $id;
    }

    /**
     * Set column order
     *
     * @param  int $index
     *
     * @return ZfC_DataTable
     */
    public function setOrder($index)
    {
        $this->_columnOrder = (int)$index;

        return $this;
    }

    /**
     * Get column order
     *
     * @return int|null
     */
    public function getOrder()
    {
        return $this->_columnOrder;
    }

    /**
     * When calling renderFormElements or render this method
     * is used to set $_isRendered member to prevent repeatedly
     * merging belongsTo setting
     */
    protected function _setIsRendered()
    {
        $this->_isRendered = true;

        return $this;
    }

    /**
     * Get the value of $_isRendered member
     */
    protected function _getIsRendered()
    {
        return (bool)$this->_isRendered;
    }

    // Element interaction:

    /**
     * Add a new element
     *
     * $element may be either a string element type, or an object of type
     * ZfC_DataTable_Element. If a string element type is provided, $name must be
     * provided, and $options may be optionally provided for configuring the
     * element.
     *
     * If a ZfC_DataTable_Element is provided, $name may be optionally provided,
     * and any provided $options will be ignored.
     *
     * @param  string|ZfC_DataTable_Element $element
     * @param  string $name
     * @param  array|Zend_Config $options
     *
     * @throws ZfC_DataTable_Exception on invalid element
     * @return ZfC_DataTable
     */
    public function addElement($element, $name = null, $options = null)
    {
        if (is_string($element)) {
            if (null === $name) {
                require_once 'ZfC/DataTable/Exception.php';
                throw new ZfC_DataTable_Exception(
                    'Elements specified by string must have an accompanying name'
                );
            }

            $this->_elements[$name] = $this->createElement($element, $name, $options);
        } elseif ($element instanceof ZfC_DataTable_Element) {
            $prefixPaths = array();
            if (!empty($this->_elementPrefixPaths)) {
                $prefixPaths = array_merge($prefixPaths, $this->_elementPrefixPaths);
            }


            if (null === $name) {
                $name = $element->getName();
            }

            $this->_elements[$name] = $element;
            $this->_elements[$name]->addPrefixPaths($prefixPaths);
        } else {
            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception(
                'Element must be specified by string or ZfC_DataTable_Element instance'
            );
        }

        $this->_order[$name] = $this->_elements[$name]->getOrder();
        $this->_orderUpdated = true;

        return $this;
    }

    /**
     * Create an element
     *
     * Acts as a factory for creating elements. Elements created with this
     * method will not be attached to the form, but will contain element
     * settings as specified in the form object (including plugin loader
     * prefix paths, default decorators, etc.).
     *
     * @param  string $type
     * @param  string $name
     * @param  array|Zend_Config $options
     *
     * @throws ZfC_DataTable_Exception
     * @return ZfC_DataTable_Element
     */
    public function createElement($type, $name, $options = null)
    {
        if (!is_string($type)) {
            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception('Element type must be a string indicating type');
        }

        if (!is_string($name)) {
            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception('Element name must be a string');
        }

        $prefixPaths = array();
        $prefixPaths['decorator'] = $this->getPluginLoader(self::DECORATOR)
            ->getPaths();
        if (!empty($this->_elementPrefixPaths)) {
            $prefixPaths = array_merge($prefixPaths, $this->_elementPrefixPaths);
        }

        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if ((null === $options) || !is_array($options)) {
            $options = array('prefixPath' => $prefixPaths);

            if (is_array($this->_elementDecorators)) {
                $options['decorators'] = $this->_elementDecorators;
            }
        } elseif (is_array($options)) {
            if (array_key_exists('prefixPath', $options)) {
                $options['prefixPath'] = array_merge($prefixPaths, $options['prefixPath']);
            } else {
                $options['prefixPath'] = $prefixPaths;
            }

            if (is_array($this->_elementDecorators)
                && !array_key_exists('decorators', $options)
            ) {
                $options['decorators'] = $this->_elementDecorators;
            }
        }

        $class = $this->getPluginLoader(self::ELEMENT)->load($type);
        $element = new $class($name, $options);

        return $element;
    }

    /**
     * Add multiple elements at once
     *
     * @param  array $elements
     *
     * @return ZfC_DataTable
     */
    public function addElements(array $elements)
    {
        foreach ($elements as $key => $spec) {
            $name = null;
            if (!is_numeric($key)) {
                $name = $key;
            }

            if (is_string($spec) || ($spec instanceof ZfC_DataTable_Element)) {
                $this->addElement($spec, $name);
                continue;
            }

            if (is_array($spec)) {
                $argc = count($spec);
                $options = array();
                if (isset($spec['type'])) {
                    $type = $spec['type'];
                    if (isset($spec['name'])) {
                        $name = $spec['name'];
                    }
                    if (isset($spec['options'])) {
                        $options = $spec['options'];
                    }
                    $this->addElement($type, $name, $options);
                } else {
                    switch ($argc) {
                        case 0:
                            continue;
                        case (1 <= $argc):
                            $type = array_shift($spec);
                        case (2 <= $argc):
                            if (null === $name) {
                                $name = array_shift($spec);
                            } else {
                                $options = array_shift($spec);
                            }
                        case (3 <= $argc):
                            if (empty($options)) {
                                $options = array_shift($spec);
                            }
                        default:
                            $this->addElement($type, $name, $options);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Set form elements (overwrites existing elements)
     *
     * @param  array $elements
     *
     * @return ZfC_DataTable
     */
    public function setElements(array $elements)
    {
        $this->clearElements();

        return $this->addElements($elements);
    }

    /**
     * Retrieve a single element
     *
     * @param  string $name
     *
     * @return ZfC_DataTable_Element|null
     */
    public function getElement($name)
    {
        if (array_key_exists($name, $this->_elements)) {
            return $this->_elements[$name];
        }

        return null;
    }

    /**
     * Retrieve all elements
     *
     * @return array
     */
    public function getElements()
    {
        return $this->_elements;
    }

    /**
     * Remove element
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function removeElement($name)
    {
        $name = (string)$name;
        if (isset($this->_elements[$name])) {
            unset($this->_elements[$name]);
            if (array_key_exists($name, $this->_order)) {
                unset($this->_order[$name]);
                $this->_orderUpdated = true;
            } else {
                /** @var ZfC_DataTable_DisplayGroup $group */
                foreach ($this->_displayGroups as $group) {
                    if (null !== $group->getElement($name)) {
                        $group->removeElement($name);
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Remove all form elements
     *
     * @return ZfC_DataTable
     */
    public function clearElements()
    {
        foreach (array_keys($this->_elements) as $key) {
            if (array_key_exists($key, $this->_order)) {
                unset($this->_order[$key]);
            }
        }
        $this->_elements = array();
        $this->_orderUpdated = true;

        return $this;
    }


    /**
     * Retrieve value for single element
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function getValue($name)
    {
        if ($element = $this->getElement($name)) {
            return $element->getValue();
        }

        return null;
    }

    /**
     * Retrieve all form element values
     *
     * @param  bool $suppressArrayNotation
     *
     * @return array
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = array();
        $eBelongTo = null;

        /** @var ZfC_DataTable_Element $element */
        foreach ($this->getElements() as $key => $element) {
            if (!$element->getIgnore()) {
                $merge = array();

                $merge = $this->_attachToArray($element->getValue(), $key);
                $values = $this->_array_replace_recursive($values, $merge);
            }
        }


        if (!$suppressArrayNotation && $this->isArray()
            &&
            !$this->_getIsRendered()
        ) {
            $values = $this->_attachToArray($values, $this->getElementsBelongTo());
        }

        return $values;
    }

    /**
     * Get unfiltered element value
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function getUnfilteredValue($name)
    {
        if ($element = $this->getElement($name)) {
            return $element->getUnfilteredValue();
        }

        return null;
    }

    /**
     * Retrieve all unfiltered element values
     *
     * @return array
     */
    public function getUnfilteredValues()
    {
        $values = array();
        /** @var ZfC_DataTable_Element $element */
        foreach ($this->getElements() as $key => $element) {
            $values[$key] = $element->getUnfilteredValue();
        }

        return $values;
    }

    /**
     * Set all elements' filters
     *
     * @param  array $filters
     *
     * @return ZfC_DataTable
     */
    public function setElementFilters(array $filters)
    {
        /** @var ZfC_DataTable_Element $element */
        foreach ($this->getElements() as $element) {
            $element->setFilters($filters);
        }

        return $this;
    }

    /**
     * Set flag indicating elements belong to array
     *
     * @param  bool $flag Value of flag
     *
     * @return ZfC_DataTable
     */
    public function setIsArray($flag)
    {
        $this->_isArray = (bool)$flag;

        return $this;
    }

    /**
     * Get flag indicating if elements belong to an array
     *
     * @return bool
     */
    public function isArray()
    {
        return $this->_isArray;
    }


    // Processing

    /**
     * Determine array key name from given value
     *
     * Given a value such as foo[bar][baz], returns the last element (in this case, 'baz').
     *
     * @param  string $value
     *
     * @return string
     */
    protected function _getArrayName($value)
    {
        if (!is_string($value) || '' === $value) {
            return $value;
        }

        if (!strstr($value, '[')) {
            return $value;
        }

        $endPos = strlen($value) - 1;
        if (']' != $value[$endPos]) {
            return $value;
        }

        $start = strrpos($value, '[') + 1;
        $name = substr($value, $start, $endPos - $start);

        return $name;
    }

    /**
     * Extract the value by walking the array using given array path.
     *
     * Given an array path such as foo[bar][baz], returns the value of the last
     * element (in this case, 'baz').
     *
     * @param  array $value Array to walk
     * @param  string $arrayPath Array notation path of the part to extract
     *
     * @return string
     */
    protected function _dissolveArrayValue($value, $arrayPath)
    {
        // As long as we have more levels
        while ($arrayPos = strpos($arrayPath, '[')) {
            // Get the next key in the path
            $arrayKey = trim(substr($arrayPath, 0, $arrayPos), ']');

            // Set the potentially final value or the next search point in the array
            if (isset($value[$arrayKey])) {
                $value = $value[$arrayKey];
            }

            // Set the next search point in the path
            $arrayPath = trim(substr($arrayPath, $arrayPos + 1), ']');
        }

        if (isset($value[$arrayPath])) {
            $value = $value[$arrayPath];
        }

        return $value;
    }

    /**
     * Given an array, an optional arrayPath and a key this method
     * dissolves the arrayPath and unsets the key within the array
     * if it exists.
     *
     * @param array $array
     * @param string|null $arrayPath
     * @param string $key
     *
     * @return array
     */
    protected function _dissolveArrayUnsetKey($array, $arrayPath, $key)
    {
        $unset =& $array;
        $path = trim(strtr((string)$arrayPath, array(
            '[' => '/', ']' => ''
        )), '/');
        $segs = ('' !== $path) ? explode('/', $path) : array();

        foreach ($segs as $seg) {
            if (!array_key_exists($seg, (array)$unset)) {
                return $array;
            }
            $unset =& $unset[$seg];
        }
        if (array_key_exists($key, (array)$unset)) {
            unset($unset[$key]);
        }

        return $array;
    }

    /**
     * Converts given arrayPath to an array and attaches given value at the end of it.
     *
     * @param  mixed $value The value to attach
     * @param  string $arrayPath Given array path to convert and attach to.
     *
     * @return array
     */
    protected function _attachToArray($value, $arrayPath)
    {
        // As long as we have more levels
        while ($arrayPos = strrpos($arrayPath, '[')) {
            // Get the next key in the path
            $arrayKey = trim(substr($arrayPath, $arrayPos + 1), ']');

            // Attach
            $value = array($arrayKey => $value);

            // Set the next search point in the path
            $arrayPath = trim(substr($arrayPath, 0, $arrayPos), ']');
        }

        $value = array($arrayPath => $value);

        return $value;
    }

    /**
     * Returns a one dimensional numerical indexed array with the
     * Elements, SubForms and Elements from DisplayGroups as Values.
     *
     * Subitems are inserted based on their order Setting if set,
     * otherwise they are appended, the resulting numerical index
     * may differ from the order value.
     *
     * @access protected
     * @return array
     */
    public function getElementsAndSubFormsOrdered()
    {
        $ordered = array();
        foreach ($this->_order as $name => $order) {
            $order = isset($order) ? $order : count($ordered);
            if ($this->$name instanceof ZfC_DataTable_Element
                || $this->$name instanceof ZfC_DateTable
            ) {
                array_splice($ordered, $order, 0, array($this->$name));
            }
        }

        return $ordered;
    }

    /**
     * This is a helper function until php 5.3 is widespreaded
     *
     * @param array $into
     *
     * @return array
     */
    protected function _array_replace_recursive(array $into)
    {
        $fromArrays = array_slice(func_get_args(), 1);

        foreach ($fromArrays as $from) {
            foreach ($from as $key => $value) {
                if (is_array($value)) {
                    if (!isset($into[$key])) {
                        $into[$key] = array();
                    }
                    $into[$key] = $this->_array_replace_recursive($into[$key], $from[$key]);
                } else {
                    $into[$key] = $value;
                }
            }
        }

        return $into;
    }

    public function persistData()
    {
    }

    // Rendering

    /**
     * Set view object
     *
     * @param  Zend_View_Interface $view
     *
     * @return ZfC_DataTable
     */
    public function setView(Zend_View_Interface $view = null)
    {
        $this->_view = $view;

        return $this;
    }

    /**
     * Retrieve view object
     *
     * If none registered, attempts to pull from ViewRenderer.
     *
     * @return Zend_View_Interface|null
     */
    public function getView()
    {
        if (null === $this->_view) {
            require_once 'Zend/Controller/Action/HelperBroker.php';
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $this->setView($viewRenderer->view);
        }

        return $this->_view;
    }

    /**
     * Instantiate a decorator based on class name or class name fragment
     *
     * @param  string $name
     * @param  null|array $options
     *
     * @return ZfC_DataTable_Decorator_Abstract
     */
    protected function _getDecorator($name, $options)
    {
        $class = $this->getPluginLoader(self::DECORATOR)->load($name);
        if (null === $options) {
            $decorator = new $class;
        } else {
            $decorator = new $class($options);
        }

        return $decorator;
    }

    /**
     * Add a decorator for rendering the element
     *
     * @param  string|ZfC_DataTable_Decorator_Abstract $decorator
     * @param  array|Zend_Config $options Options with which to initialize decorator
     *
     * @throws ZfC_DataTable_Exception
     * @return ZfC_DataTable
     */
    public function addDecorator($decorator, $options = null)
    {
        if ($decorator instanceof ZfC_DataTable_Decorator_Abstract) {
            $name = get_class($decorator);
        } elseif (is_string($decorator)) {
            $name = $decorator;
            $decorator = array(
                'decorator' => $name,
                'options' => $options,
            );
        } elseif (is_array($decorator)) {
            foreach ($decorator as $name => $spec) {
                break;
            }
            if (is_numeric($name)) {
                require_once 'ZfC/DataTable/Exception.php';
                throw new ZfC_DataTable_Exception(
                    'Invalid alias provided to addDecorator; must be alphanumeric string'
                );
            }
            if (is_string($spec)) {
                $decorator = array(
                    'decorator' => $spec,
                    'options' => $options,
                );
            } elseif ($spec instanceof ZfC_DataTable_Decorator_Abstract) {
                $decorator = $spec;
            }
        } else {
            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception(
                'Invalid decorator provided to addDecorator; must be string or ZfC_DataTable_Decorator_Abstract'
            );
        }

        $this->_decorators[$name] = $decorator;

        return $this;
    }

    /**
     * Add many decorators at once
     *
     * @param  array $decorators
     *
     * @throws ZfC_DataTable_Exception
     * @return ZfC_DataTable
     */
    public function addDecorators(array $decorators)
    {
        foreach ($decorators as $decoratorName => $decoratorInfo) {
            if (is_string($decoratorInfo)
                || $decoratorInfo instanceof ZfC_DataTable_Decorator_Abstract
            ) {
                if (!is_numeric($decoratorName)) {
                    $this->addDecorator(array($decoratorName => $decoratorInfo));
                } else {
                    $this->addDecorator($decoratorInfo);
                }
            } elseif (is_array($decoratorInfo)) {
                $argc = count($decoratorInfo);
                $options = array();
                if (isset($decoratorInfo['decorator'])) {
                    $decorator = $decoratorInfo['decorator'];
                    if (isset($decoratorInfo['options'])) {
                        $options = $decoratorInfo['options'];
                    }
                    $this->addDecorator($decorator, $options);
                } else {
                    switch (true) {
                        case (0 == $argc):
                            break;
                        case (1 <= $argc):
                            $decorator = array_shift($decoratorInfo);
                        case (2 <= $argc):
                            $options = array_shift($decoratorInfo);
                        default:
                            $this->addDecorator($decorator, $options);
                            break;
                    }
                }
            } else {
                require_once 'ZfC/DataTable/Exception.php';
                throw new ZfC_DataTable_Exception('Invalid decorator passed to addDecorators()');
            }
        }

        return $this;
    }

    /**
     * Overwrite all decorators
     *
     * @param  array $decorators
     *
     * @return ZfC_DataTable
     */
    public function setDecorators(array $decorators)
    {
        $this->clearDecorators();

        return $this->addDecorators($decorators);
    }

    /**
     * Retrieve a registered decorator
     *
     * @param  string $name
     *
     * @return false|ZfC_DataTable_Decorator_Abstract
     */
    public function getDecorator($name)
    {
        if (!isset($this->_decorators[$name])) {
            $len = strlen($name);
            foreach ($this->_decorators as $localName => $decorator) {
                if ($len > strlen($localName)) {
                    continue;
                }

                if (0 === substr_compare($localName, $name, -$len, $len, true)) {
                    if (is_array($decorator)) {
                        return $this->_loadDecorator($decorator, $localName);
                    }

                    return $decorator;
                }
            }

            return false;
        }

        if (is_array($this->_decorators[$name])) {
            return $this->_loadDecorator($this->_decorators[$name], $name);
        }

        return $this->_decorators[$name];
    }

    /**
     * Retrieve all decorators
     *
     * @return array
     */
    public function getDecorators()
    {
        foreach ($this->_decorators as $key => $value) {
            if (is_array($value)) {
                $this->_loadDecorator($value, $key);
            }
        }

        return $this->_decorators;
    }

    /**
     * Remove a single decorator
     *
     * @param  string $name
     *
     * @return bool
     */
    public function removeDecorator($name)
    {
        $decorator = $this->getDecorator($name);
        if ($decorator) {
            if (array_key_exists($name, $this->_decorators)) {
                unset($this->_decorators[$name]);
            } else {
                $class = get_class($decorator);
                if (!array_key_exists($class, $this->_decorators)) {
                    return false;
                }
                unset($this->_decorators[$class]);
            }

            return true;
        }

        return false;
    }

    /**
     * Clear all decorators
     *
     * @return ZfC_DataTable
     */
    public function clearDecorators()
    {
        $this->_decorators = array();

        return $this;
    }

    /**
     * Set all element decorators as specified
     *
     * @param  array $decorators
     * @param  array|null $elements Specific elements to decorate or exclude from decoration
     * @param  bool $include Whether $elements is an inclusion or exclusion list
     *
     * @return ZfC_DataTable
     */
    public function setElementDecorators(array $decorators, array $elements = null, $include = true)
    {
        if (is_array($elements)) {
            if ($include) {
                $elementObjs = array();
                foreach ($elements as $name) {
                    if (null !== ($element = $this->getElement($name))) {
                        $elementObjs[] = $element;
                    }
                }
            } else {
                $elementObjs = $this->getElements();
                foreach ($elements as $name) {
                    if (array_key_exists($name, $elementObjs)) {
                        unset($elementObjs[$name]);
                    }
                }
            }
        } else {
            $elementObjs = $this->getElements();
        }

        /** @var ZfC_DataTable_Element $element */
        foreach ($elementObjs as $element) {
            $element->setDecorators($decorators);
        }

        $this->_elementDecorators = $decorators;

        return $this;
    }

    /**
     * Render form
     *
     * @param  Zend_View_Interface $view
     *
     * @return string
     */
    public function render(Zend_View_Interface $view = null)
    {
        if (null !== $view) {
            $this->setView($view);
        }

        $content = array('xhtml' => '', 'paramJs' => array());
        /** @var ZfC_DataTable_Decorator_Abstract $decorator */
        foreach ($this->getDecorators() as $key => $decorator) {
            $decorator->setElement($this);

            $content = $decorator->render($content) + $content;
        }
        $this->_setIsRendered();
        return $content['xhtml'];
    }

    /**
     * Serialize as string
     *
     * Proxies to {@link render()}.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            $message = "Exception caught by form: " . $e->getMessage()
                . "\nStack Trace:\n" . $e->getTraceAsString();
            trigger_error($message, E_USER_WARNING);

            return '';
        }
    }


    // Localization:

    /**
     * Set translator object
     *
     * @param  Zend_Translate|Zend_Translate_Adapter|null $translator
     *
     * @throws ZfC_DataTable_Exception
     * @return ZfC_DataTable
     */
    public function setTranslator($translator = null)
    {
        if (null === $translator) {
            $this->_translator = null;
        } elseif ($translator instanceof Zend_Translate_Adapter) {
            $this->_translator = $translator;
        } elseif ($translator instanceof Zend_Translate) {
            $this->_translator = $translator->getAdapter();
        } else {
            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception('Invalid translator specified');
        }

        return $this;
    }

    /**
     * Set global default translator object
     *
     * @param  Zend_Translate|Zend_Translate_Adapter|null $translator
     *
     * @throws ZfC_DataTable_Exception
     * @return void
     */
    public static function setDefaultTranslator($translator = null)
    {
        if (null === $translator) {
            self::$_translatorDefault = null;
        } elseif ($translator instanceof Zend_Translate_Adapter) {
            self::$_translatorDefault = $translator;
        } elseif ($translator instanceof Zend_Translate) {
            self::$_translatorDefault = $translator->getAdapter();
        } else {
            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception('Invalid translator specified');
        }
    }

    /**
     * Retrieve translator object
     *
     * @return Zend_Translate|null
     */
    public function getTranslator()
    {
        if ($this->translatorIsDisabled()) {
            return null;
        }

        if (null === $this->_translator) {
            return self::getDefaultTranslator();
        }

        return $this->_translator;
    }

    /**
     * Does this form have its own specific translator?
     *
     * @return bool
     */
    public function hasTranslator()
    {
        return (bool)$this->_translator;
    }

    /**
     * Get global default translator object
     *
     * @return null|Zend_Translate
     */
    public static function getDefaultTranslator()
    {
        if (null === self::$_translatorDefault) {
            require_once 'Zend/Registry.php';
            if (Zend_Registry::isRegistered('Zend_Translate')) {
                $translator = Zend_Registry::get('Zend_Translate');
                if ($translator instanceof Zend_Translate_Adapter) {
                    return $translator;
                } elseif ($translator instanceof Zend_Translate) {
                    return $translator->getAdapter();
                }
            }
        }

        return self::$_translatorDefault;
    }

    /**
     * Is there a default translation object set?
     *
     * @return boolean
     */
    public static function hasDefaultTranslator()
    {
        return (bool)self::$_translatorDefault;
    }

    /**
     * Indicate whether or not translation should be disabled
     *
     * @param  bool $flag
     *
     * @return ZfC_DataTable
     */
    public function setDisableTranslator($flag)
    {
        $this->_translatorDisabled = (bool)$flag;

        return $this;
    }

    /**
     * Is translation disabled?
     *
     * @return bool
     */
    public function translatorIsDisabled()
    {
        return $this->_translatorDisabled;
    }

    /**
     * Overloading: access to elements, form groups, and display groups
     *
     * @param  string $name
     *
     * @return ZfC_DataTable_Element|ZfC_DataTable|null
     */
    public function __get($name)
    {
        if (isset($this->_elements[$name])) {
            return $this->_elements[$name];
        }

        return null;
    }

    /**
     * Overloading: access to elements, form groups, and display groups
     *
     * @param  string $name
     * @param  ZfC_DataTable_Element $value
     *
     * @return void
     * @throws ZfC_DataTable_Exception for invalid $value
     */
    public function __set($name, $value)
    {
        if ($value instanceof ZfC_DataTable_Element) {
            $this->addElement($value, $name);

            return;
        }

        require_once 'ZfC/DataTable/Exception.php';
        if (is_object($value)) {
            $type = get_class($value);
        } else {
            $type = gettype($value);
        }
        throw new ZfC_DataTable_Exception(
            'Only form elements and groups may be overloaded; variable of type "' . $type
            . '" provided'
        );
    }

    /**
     * Overloading: access to elements, form groups, and display groups
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        if (isset($this->_elements[$name])
            || isset($this->_displayGroups[$name])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Overloading: access to elements, form groups, and display groups
     *
     * @param  string $name
     *
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->_elements[$name])) {
            unset($this->_elements[$name]);
        }
    }

    /**
     * Overloading: allow rendering specific decorators
     *
     * Call renderDecoratorName() to render a specific decorator.
     *
     * @param  string $method
     * @param  array $args
     *
     * @return string
     * @throws ZfC_DataTable_Exception for invalid decorator or invalid method call
     */
    public function __call($method, $args)
    {
        if ('render' == substr($method, 0, 6)) {
            $decoratorName = substr($method, 6);
            if (false !== ($decorator = $this->getDecorator($decoratorName))) {
                $decorator->setElement($this);
                $seed = '';
                if (0 < count($args)) {
                    $seed = array_shift($args);
                }
                if ($decoratorName === 'DataTableElements') {
                    $this->_setIsRendered();
                }

                return $decorator->render($seed);
            }

            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception(sprintf('Decorator by name %s does not exist', $decoratorName));
        }

        require_once 'ZfC/DataTable/Exception.php';
        throw new ZfC_DataTable_Exception(sprintf('Method %s does not exist', $method));
    }

    // Interfaces: Iterator, Countable

    /**
     * Current element/subform/display group
     *
     * @throws ZfC_DataTable_Exception
     * @return ZfC_DataTable_Element|ZfC_DataTable_DisplayGroup|ZfC_DataTable
     */
    public function current()
    {
        $this->_sort();
        current($this->_order);
        $key = key($this->_order);

        if (isset($this->_elements[$key])) {
            return $this->getElement($key);
        } else {
            require_once 'ZfC/DataTable/Exception.php';
            throw new ZfC_DataTable_Exception(
                sprintf('Corruption detected in form; invalid key ("%s") found in internal iterator', (string)$key)
            );
        }
    }

    /**
     * Current element/subform/display group name
     *
     * @return string
     */
    public function key()
    {
        $this->_sort();

        return key($this->_order);
    }

    /**
     * Move pointer to next element/subform/display group
     *
     * @return void
     */
    public function next()
    {
        $this->_sort();
        next($this->_order);
    }

    /**
     * Move pointer to beginning of element/subform/display group loop
     *
     * @return void
     */
    public function rewind()
    {
        $this->_sort();
        reset($this->_order);
    }

    /**
     * Determine if current element/subform/display group is valid
     *
     * @return bool
     */
    public function valid()
    {
        $this->_sort();

        return (current($this->_order) !== false);
    }

    /**
     * Count of elements/subforms that are iterable
     *
     * @return int
     */
    public function count()
    {
        return count($this->_order);
    }

    /**
     * Set flag to disable loading default decorators
     *
     * @param  bool $flag
     *
     * @return ZfC_DataTable
     */
    public function setDisableLoadDefaultDecorators($flag)
    {
        $this->_disableLoadDefaultDecorators = (bool)$flag;

        return $this;
    }

    /**
     * Should we load the default decorators?
     *
     * @return bool
     */
    public function loadDefaultDecoratorsIsDisabled()
    {
        return $this->_disableLoadDefaultDecorators;
    }

    /**
     * Load the default decorators
     *
     * @return ZfC_DataTable
     */
    public function loadDefaultDecorators()
    {

        if (!$this->getAttrib('class')) {
            $this->setAttrib('class', "datatable table table-striped table-bordered");
        }

        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('DataTableElements')
                ->addDecorator('DataTable');
        }

        return $this;
    }

    /**
     * Remove an element from iteration
     *
     * @param  string $name Element/group/form name
     *
     * @return void
     */
    public function removeFromIteration($name)
    {
        if (array_key_exists($name, $this->_order)) {
            unset($this->_order[$name]);
            $this->_orderUpdated = true;
        }
    }

    /**
     * Sort items according to their order
     *
     * @throws ZfC_DataTable_Exception
     * @return void
     */
    protected function _sort()
    {
        if ($this->_orderUpdated) {
            $items = array();
            $index = 0;
            foreach ($this->_order as $key => $order) {
                if (null === $order) {
                    if (null === ($order = $this->{$key}->getOrder())) {
                        while (array_search($index, $this->_order, true)) {
                            ++$index;
                        }
                        $items[$index] = $key;
                        ++$index;
                    } else {
                        $items[$order] = $key;
                    }
                } elseif (isset($items[$order]) && $items[$order] !== $key) {
                    throw new ZfC_DataTable_Exception(
                        'datatable elements ' .
                        $items[$order] . ' and ' . $key .
                        ' have the same order (' .
                        $order . ') - ' .
                        'this would result in only the last added element to be rendered'
                    );
                } else {
                    $items[$order] = $key;
                }
            }

            $items = array_flip($items);
            asort($items);
            $this->_order = $items;
            $this->_orderUpdated = false;
        }
    }

    /**
     * Lazy-load a decorator
     *
     * @param  array $decorator Decorator type and options
     * @param  mixed $name Decorator name or alias
     *
     * @return Zend_Form_Decorator_Interface
     */
    protected function _loadDecorator(array $decorator, $name)
    {
        $sameName = false;
        if ($name == $decorator['decorator']) {
            $sameName = true;
        }

        $instance = $this->_getDecorator($decorator['decorator'], $decorator['options']);
        if ($sameName) {
            $newName = get_class($instance);
            $decoratorNames = array_keys($this->_decorators);
            $order = array_flip($decoratorNames);
            $order[$newName] = $order[$name];
            $decoratorsExchange = array();
            unset($order[$name]);
            asort($order);
            foreach ($order as $key => $index) {
                if ($key == $newName) {
                    $decoratorsExchange[$key] = $instance;
                    continue;
                }
                $decoratorsExchange[$key] = $this->_decorators[$key];
            }
            $this->_decorators = $decoratorsExchange;
        } else {
            $this->_decorators[$name] = $instance;
        }

        return $instance;
    }

    /**
     * @param string $urlAjax
     */
    public function setAjax($urlAjax)
    {
        $this->_urlajax = $urlAjax;

        return $this;
    }

    public function getAjax()
    {
        return $this->_urlajax;
    }

    /**
     * Encode Json that may include javascript expressions.
     *
     * Take care of using the Zend_Json_Encoder to alleviate problems with the json_encode
     * magic key mechanism as of now.
     *
     * @see Zend_Json::encode
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    public static function encodeJson($value, $draw = 1, $total = 0)
    {
        if (!is_array($value)) {
            $value = array();
        }

        if (!class_exists('Zend_Json')) {
            /**
             * @see Zend_Json
             */
            require_once "Zend/Json.php";
        }

        $arrValue = array(
            "draw" => $draw,
            "recordsTotal" => $total == 0 ? count($value) : $total,
            "recordsFiltered" => $total == 0 ? count($value) : $total,
            "data" => $value
        );

        return Zend_Json::encode($arrValue, false, array('enableJsonExprFinder' => true));
    }

    /**
     * Set form method
     *
     * Only values in {@link $_methods()} allowed
     *
     * @param  string $method
     *
     * @return ZfComplement_DataTable
     * @throws ZfComplement_DataTable_Exception
     */
    public function setMethod ( $method )
    {
        $method = strtolower ( $method );
        if ( ! in_array ( $method , $this->_methods ) )
        {
            require_once 'ZfComplement/DataTable/Exception.php';
            throw new ZfComplement_DataTable_Exception( sprintf ( '"%s" is an invalid form method' , $method ) );
        }
        $this->setAttrib ( 'method' , $method );

        return $this;
    }

    /**
     * Retrieve form method
     *
     * @return string
     */
    public function getMethod ()
    {
        if ( null === ( $method = $this->getAttrib ( 'method' ) ) )
        {
            $method = self::METHOD_POST;
            $this->setAttrib ( 'method' , $method );
        }

        return strtolower ( $method );
    }
}
