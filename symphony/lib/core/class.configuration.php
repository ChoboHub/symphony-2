<?php

/**
 * @package core
 */

 /**
  * The Configuration class acts as a property => value store for settings
  * used throughout Symphony. The result of this class is a string containing
  * a PHP representation of the properties (and their values) set by the Configuration.
  * Symphony's configuration file is saved at `CONFIG`. The initial
  * file is generated by the Symphony installer, and then subsequent use of Symphony
  * loads in this file for each page view. Like minded properties can be grouped.
  */
class Configuration
{
    /**
     * An associative array of the properties for this Configuration object
     * @var array
     */
    private $_properties = array();

    /**
     * Whether all properties and group keys will be forced to be lowercase.
     * By default this is false, which makes all properties case sensitive
     * @var boolean
     */
    private $_forceLowerCase = false;

    /**
     * The constructor for the Configuration class takes one parameter,
     * `$forceLowerCase` which will make all property and
     * group names lowercase or not. By default they are left to the case
     * the user provides
     *
     * @param boolean $forceLowerCase
     *  False by default, if true this will make all property and group names
     *  lowercase
     */
    public function __construct($forceLowerCase = false)
    {
        $this->_forceLowerCase = $forceLowerCase;
    }

    /**
     * Setter for the `$this->_properties`. The properties array
     * can be grouped to be an 'array' of an 'array' of properties. For instance
     * a 'region' key may be an array of 'properties' (that is name/value), or it
     * may be a 'value' itself.
     *
     * @param string $name
     *  The name of the property to set, eg 'timezone'
     * @param string $value
     *  The value for the property to set, eg. '+10:00'
     * @param string $group
     *  The group for this property, eg. 'region'
     */
    public function set($name, $value, $group = null)
    {
        if ($this->_forceLowerCase) {
            $name = strtolower($name);
            $group = strtolower($group);
        }

        if ($group) {
            $this->_properties[$group][$name] = $value;
        } else {
            $this->_properties[$name] = $value;
        }
    }

    /**
     * A quick way to set a large number of properties. Given an associative
     * array or a nested associative array (where the key will be the group),
     * this function will merge the `$array` with the existing configuration.
     * By default the given `$array` will overwrite any existing keys unless
     * the `$overwrite` parameter is passed as false.
     *
     * @since Symphony 2.3.2 The `$overwrite` parameter is available
     * @param array $array
     *  An associative array of properties, 'property' => 'value' or
     *  'group' => array('property' => 'value')
     * @param boolean $overwrite
     *  An optional boolean parameter to indicate if it is safe to use array_merge
     *  or if the provided array should be integrated using the 'set()' method
     *  to avoid possible change collision. Defaults to false.
     */
    public function setArray(array $array, $overwrite = false)
    {
        if ($overwrite) {
            $this->_properties = array_merge($this->_properties, $array);
        } else {
            foreach ($array as $set => $values) {
                foreach ($values as $key => $val) {
                    self::set($key, $val, $set);
                }
            }
        }
    }

    /**
     * Accessor function for the `$this->_properties`.
     *
     * @param string $name
     *  The name of the property to retrieve
     * @param string $group
     *  The group that this property will be in
     * @return array|string
     *  If `$name` or `$group` are not
     *  provided this function will return the full `$this->_properties`
     *  array.
     */
    public function get($name = null, $group = null)
    {

        // Return the whole array if no name or index is requested
        if (!$name && !$group) {
            return $this->_properties;
        }

        if ($this->_forceLowerCase) {
            $name = strtolower($name);
            $group = strtolower($group);
        }

        if ($group) {
            return (isset($this->_properties[$group][$name]) ? $this->_properties[$group][$name] : null);
        }

        return (isset($this->_properties[$name]) ? $this->_properties[$name] : null);
    }

    /**
     * The remove function will unset a property by `$name`.
     * It is possible to remove an entire 'group' by passing the group
     * name as the `$name`
     *
     * @param string $name
     *  The name of the property to unset. This can also be the group name
     * @param string $group
     *  The group of the property to unset
     */
    public function remove($name, $group = null)
    {
        if ($this->_forceLowerCase) {
            $name = strtolower($name);
            $group = strtolower($group);
        }

        if ($group && isset($this->_properties[$group][$name])) {
            unset($this->_properties[$group][$name]);
        } elseif ($this->_properties[$name]) {
            unset($this->_properties[$name]);
        }
    }

    /**
     * Empties all the Configuration values by setting `$this->_properties`
     * to an empty array
     */
    public function flush()
    {
        $this->_properties = array();
    }

    /**
     * This magic `__toString` function converts the internal `$this->_properties`
     * array into a string representation. Symphony generates the `MANIFEST/config.php`
     * file in this manner.
     * @see Configuration::serializeArray()
     * @return string
     *  A string that contains a array representation of `$this->_properties`.
     *  This is used by Symphony to write the `config.php` file.
     */
    public function __toString()
    {
        $string = 'array(';

        foreach ($this->_properties as $group => $data) {
            $string .= str_repeat(PHP_EOL, 3) . "\t\t###### ".strtoupper($group)." ######";
            $string .= PHP_EOL . "\t\t'$group' => ";

            $string .= $this->serializeArray($data, 3);

            $string .= ",";
            $string .= PHP_EOL . "\t\t########";
        }
        $string .= PHP_EOL . "\t)";

        return $string;
    }

    /**
     * The `serializeArray` function will properly format and indent multidimensional
     * arrays using recursivity.
     * 
     * `__toString()` call `serializeArray` to use the recursive condition.
     * The keys (int) in array won't have apostrophe.
     * Array without multidimensional array will be output with normal indentation.
     * @return string
     *  A string that contains a array representation of the '$data parameter'.
     * @param array $arr
     *  A array of properties to serialize.
     * @param integer $indentation
     *  The current level of indentation.
     */

    protected function serializeArray(array $arr, $indentation = 0)
    {
        $tabs = '';
        $closeTabs = '';
        for ($i = 0; $i < $indentation; $i++) {
            $tabs .= "\t";
            if ($i < $indentation - 1) {
                $closeTabs .= "\t";
            }
        }
        $string = 'array(';
        foreach ($arr as $key => $value) {
            $string .= (is_numeric($key) ? PHP_EOL . "$tabs $key => " : PHP_EOL . "$tabs'$key' => ");
            if (is_array($value)) {
                if (empty($value)) {
                    $string .= 'array()';
                } else {
                    $string .= $this->serializeArray($value, $indentation + 1);
                }
            } else {
                $string .= (General::strlen($value) > 0 ? var_export($value, true) : 'null');
            }
            $string .= ",";
        }
        $string .=  PHP_EOL . "$closeTabs)";
        return $string;
    }

    /**
     * Function will write the current Configuration object to
     * a specified `$file` with the given `$permissions`.
     *
     * @param string $file
     *  the path of the file to write.
     * @param integer|null $permissions (optional)
     *  the permissions as an octal number to set set on the resulting file.
     *  If this is not provided it will use the permissions defined in [`write_mode`][`file`]
     * @return boolean
     */
    public function write($file = null, $permissions = null)
    {
        if (is_null($permissions) && isset($this->_properties['file']['write_mode'])) {
            $permissions = $this->_properties['file']['write_mode'];
        }

        if (is_null($file)) {
            $file = CONFIG;
        }

        $string = "<?php\n\t\$settings = " . (string)$this . ";\n";

        return General::writeFile($file, $string, $permissions);
    }
}
