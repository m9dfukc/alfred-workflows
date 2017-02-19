<?php

namespace AlfredApp;

/**
 * Name:        Workflows
 * Description:    This PHP class object provides several useful functions for retrieving, parsing,
 *                and formatting data to be used with Alfred 2 Workflows.
 * Author:        David Ferguson (@jdfwarrior)
 */
class Workflows
{
    const PATH_CACHE = "/Library/Caches/com.runningwithcrayons.Alfred-%d/Workflow Data/";
    const PATH_DATA = "/Library/Application Support/Alfred %d/Workflow Data/";
    const INFO_PLIST = "info.plist";

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var string
     */
    private $dataPath;

    /**
     * @var string
     */
    private $bundleId;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $home;

    /**
     * @var array
     */
    private $results = [];

    /**
     * Description:
     * Class constructor function. Intializes all class variables. Accepts one optional parameter
     * of the workflow bundle id in the case that you want to specify a different bundle id. This
     * would adjust the output directories for storing data.
     *
     * @param string $bundleId - optional bundle id if not found automatically
     */
    public function __construct($bundleId = null)
    {
        $this->path = getcwd();
        $this->home = $_SERVER['HOME'];

        if (file_exists(self::INFO_PLIST)) {
            $this->bundleId = $this->get(self::INFO_PLIST, 'bundleid');
        }

        if (!is_null($bundleId)) {
            $this->bundleId = $bundleId;
        }

        $this->setupCachePath();
        $this->setupDataPath();
    }

    /**
     * Description:
     * Accepts no parameter and returns the value of the bundle id for the current workflow.
     * If no value is available, then false is returned.
     *
     * @return string|false if not available, bundle id value if available.
     */
    public function bundle()
    {
        return (is_null($this->bundleId) ? false : $this->bundleId);
    }

    /**
     * Description:
     * Accepts no parameter and returns the value of the path to the cache directory for your
     * workflow if it is available. Returns false if the value isn't available.
     *
     * @return string|false if not available, path to the cache directory for your workflow if available.
     */
    public function cache()
    {
        return $this->cachePath ?: false;
    }

    /**
     * Description:
     * Accepts no parameter and returns the value of the path to the storage directory for your
     * workflow if it is available. Returns false if the value isn't available.
     *
     * @return string|false if not available, path to the storage directory for your workflow if available.
     */
    public function data()
    {
        return $this->dataPath ?: false;
    }

    /**
     * Description:
     * Accepts no parameter and returns the value of the path to the current directory for your
     * workflow if it is available. Returns false if the value isn't available.
     *
     * @param none
     * @return string|false if not available, path to the current directory for your workflow if available.
     */
    public function path()
    {
        return $this->path ?: false;
    }

    /**
     * Description:
     * Accepts no parameter and returns the value of the home path for the current user
     * Returns false if the value isn't available.
     *
     * @return string|false if not available, home path for the current user if available.
     */
    public function home()
    {
        return $this->home ?: false;
    }

    /**
     * Description:
     * Returns an array of available result items
     *
     * @return array - list of result items
     */
    public function results()
    {
        return $this->results;
    }

    /**
     * Description:
     * Convert an associative array into XML format
     *
     * @param array $results - An associative array to convert
     * @param string $format - format of data being passed (json or array), defaults to array
     * @return string - XML string representation of the array
     */
    public function toXml($results = null, $format = 'array')
    {
        if ($format == 'json') {
            $results = json_decode($results, true);
        }

        if (is_null($results)) {
            $results = $this->results;
        }

        if (empty($results)) {
            return false;
        }

        $items = new \SimpleXMLElement("<items></items>");    // Create new XML element

        foreach ($results as $result) {                                // Loop through each object in the array
            $c = $items->addChild('item');                // Add a new 'item' element for each object
            $c_keys = array_keys($result);                        // Grab all the keys for that item
            foreach ($c_keys as $key) {                        // For each of those keys

                if ($key == 'uid') {
                    if ($result[$key] === NULL) {
                        continue;
                    } else {
                        $c->addAttribute('uid', $result[$key]);
                    }
                } elseif ($key == 'arg') {
                    if ($result[$key] === NULL) {
                        continue;
                    } else {
                        $c->addAttribute('arg', $result[$key]);
                        $c->$key = $result[$key];
                    }
                } elseif ($key == 'type') {
                    $c->addAttribute('type', $result[$key]);
                } elseif ($key == 'valid') {
                    if ($result[$key] == 'yes' || $result[$key] == 'no') {
                        $c->addAttribute('valid', $result[$key]);
                    }
                } elseif ($key == 'autocomplete') {
                    if ($result[$key] === null) {
                        continue;
                    } else {
                        $c->addAttribute('autocomplete', $result[$key]);
                    }
                } elseif ($key == 'icon') {
                    if (substr($result[$key], 0, 9) == 'fileicon:') {
                        $val = substr($result[$key], 9);
                        $c->$key = $val;
                        $c->$key->addAttribute('type', 'fileicon');
                    } elseif (substr($result[$key], 0, 9) == 'filetype:') {
                        $val = substr($result[$key], 9);
                        $c->$key = $val;
                        $c->$key->addAttribute('type', 'filetype');
                    } else {
                        $c->$key = $result[$key];
                    }
                } else {
                    $c->$key = $result[$key];
                }
            } // end foreach
        } // end foreach

        return $items->asXML();                                // Return XML string representation of the array

    }

    /**
     * Description:
     * Save a single value to a specified plist.
     *
     * @param string $filename - the full path to the plist to write to
     * @param string $key - the key of the setting
     * @param mixed $value - the value of the setting
     * @return string - execution output
     */
    public function set(
        string $filename,
        string $key,
        $value )
    {
        return $this->writeToPList( $filename, $key, $value );
    }

    /**
     * Description:
     * Save multiple key-value pairs to a specified plist.
     *
     * @param string $filename - the full path to the plist to write to
     * @param array $values
     */
    public function setmulti(
        string $filename,
        array $values )
    {
        foreach( $values as $k => &$v ) {
            $this->set( $filename, $k, $v );
        }
    }

    /**
     * Description:
     * Read a value from the specified plist
     *
     * @param string $filename - plist filename to read the value from
     * @param string $key - the name of the value to read
     * @throws \Exception If the value could not be read.
     * @return string The stored value.
     */
    public function get(
        string $filename,
        string $key )
    {
        // We will redirect any errors to /dev/null to discard them,
        // otherwise they would be passed through to the output by PHP.
        $shellCmd = sprintf(
            'defaults read %s %s 2>/dev/null',
            escapeshellarg( $filename ),
            escapeshellarg( $key ) );

        // Attempt to read the plist value.
        exec( $shellCmd, $results );
        if( empty( $results ) ) {
            throw new \Exception( sprintf( 'Unable to read key "%s" from plist "%s"', $key, $filename ) );
        }

        // Return the first line of the system call.
        // Note that exec() never includes trailing whitespace on any lines!
        return $results[0];
    }

    /**
     * Description:
     * Read data from a remote file/url, essentially a shortcut for curl
     *
     * @param string $url - URL to request
     * @param array $options - Array of curl options
     * @return string result from curl_exec
     * @deprecated Look into using Client class
     */
    public function request($url = null, array $options = null)
    {
        if (is_null($url)) {
            return false;
        }

        $defaults = array(                                    // Create a list of default curl options
            CURLOPT_RETURNTRANSFER => true,                    // Returns the result as a string
            CURLOPT_URL => $url,                            // Sets the url to request
            CURLOPT_FRESH_CONNECT => true
        );

        if ($options) {
            foreach ($options as $k => $v) {
                $defaults[$k] = $v;
            }
        }

        array_filter($defaults,                            // Filter out empty options from the array
            array($this, 'emptyFilter'));

        $ch = curl_init();                                    // Init new curl object
        curl_setopt_array($ch, $defaults);                // Set curl options
        $out = curl_exec($ch);                            // Request remote data
        $err = curl_error($ch);
        curl_close($ch);                                    // End curl request

        if ($err) {
            return $err;
        } else {
            return $out;
        }
    }

    /**
     * Description:
     * Allows searching the local hard drive using mdfind
     *
     * @param string $query - search parameters
     * @param bool $simpleQuery - if TRUE, $query is auto-escaped and searched for as a general search,
     *                            but if FALSE, you can pass any argument to mdfind (but then it's YOUR
     *                            job to properly escape those command line parameters!).
     * @return array - array of search results
     */
    public function mdfind(
        string $query,
        $simpleQuery = TRUE )
    {
        // We will redirect any errors to /dev/null to discard them,
        // otherwise they would be passed through to the output by PHP.
        $shellCmd = sprintf(
            'mdfind %s 2>/dev/null',
            ( $simpleQuery
              ? escapeshellarg( $query )
              : $query ) );

        // Return all lines of the system call.
        // Note that exec() never includes trailing whitespace on any lines!
        exec( $shellCmd, $results );
        return $results;
    }

    /**
     * Description:
     * Accepts data and a string file name to store data to local file as cache
     *
     * @param string|array $filename - filename to write the cache data to
     * @param array $data - data to save to file
     * @return boolean
     */
    public function write($filename, $data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        return file_put_contents( $filename, $data );
    }

    /**
     * Description:
     * Safely writes new contents to a file using an atomic two-step process.
     * If the script is killed before the write is complete, only the temporary
     * trash file will be corrupted. That's very important, since Alfred might
     * kill a script earlier than its completion. This function also supports
     * writing arrays to disk, in which case it serializes it to JSON first.
     *
     * @param string $filename - filename to write the data to
     * @param mixed $data - data to write to file; if it is an array, it will
     *                      be automatically JSON-encoded before writing to disk
     * @param string $atomicSuffix - lets you optionally provide a different
     *                               suffix for the temporary file
     * @return mixed - number of bytes written on success, otherwise FALSE
     */
    public function atomicwrite(
        string $filename,
        $data,
        string $atomicSuffix = 'atomictmp' )
    {
        // Handle JSON serialization if necessary.
        if( is_array( $data ) ) {
            $data = json_encode( $data );
        }

        // Perform an exclusive (locked) overwrite to a temporary file.
        $filenameTmp = sprintf( '%s.%s', $filename, $atomicSuffix );
        $writeResult = file_put_contents( $filenameTmp, $data, LOCK_EX );
        if( $writeResult !== FALSE ) {
            // Now move the file to its real destination (replaced if exists).
            $moveResult = rename( $filenameTmp, $filename );
            if( $moveResult === TRUE ) {
                // Successful write and move. Return number of bytes written.
                return $writeResult;
            }
        }
        return FALSE; // Failed.
    }

    /**
     * Description:
     * Reads data from a file, and decodes it to an array/object if it's JSON. We look for
     * the characters { or [ as the first character in the data, to automatically determine
     * whether we should attempt to decode JSON. If you write non-JSON data starting with
     * those characters, then you'll trigger a JSON decode attempt, but if it fails (isn't
     * valid JSON) then you'll still get your raw text instead, exactly as you intended.
     *
     * @param string $filename filename to read the data from
     * @param bool $getJsonAsArray optionally set set this to FALSE to return an object
     *                             instead of an array, whenever JSON-data is decoded.
     * @return FALSE if the file cannot be found, otherwise file data if found. If the file
     *            format was json encoded, then a json array (or object) is returned if the
     *            data was successfully decoded. otherwise the raw file data is returned.
     */
    public function read(
        $filename,
        $getJsonAsArray = TRUE )
    {
        $contents = file_get_contents( $filename );
        if( $contents !== FALSE ) {
            $firstChar = $contents[0];
            if( $firstChar == '{' || $firstChar == '[' ) {
                // Possible JSON data. Attempt decode.
                $decoded = @json_decode( $contents, $getJsonAsArray );
                if( $decoded !== NULL ) {
                    // Successful JSON decoding!
                    return $decoded;
                } else {
                    // Isn't valid JSON data. Return the raw contents instead.
                    return $contents;
                }
            } else {
                // Contents are DEFINITELY not JSON. Return the raw contents.
                return $contents;
            }
        } else {
            // Failed to read file.
            return FALSE;
        }
    }

    /**
     * Description:
     * Helper function that just makes it easier to pass values into a function
     * and create an array result to be passed back to Alfred
     *
     * @param string $uid - the uid of the result, should be unique
     * @param string $arg - the argument that will be passed on
     * @param string $title - The title of the result item
     * @param string $sub - The subtitle text for the result item
     * @param string $icon - the icon to use for the result item
     * @param boolean $valid - sets whether the result item can be actioned
     * @param string $auto - the autocomplete value for the result item
     * @param null $type
     * @return array - array item to be passed back to Alfred
     */
    public function result($uid, $arg, $title, $sub, $icon, $valid = true, $auto = null, $type = null)
    {
        $temp = array(
            'uid' => $uid,
            'arg' => $arg,
            'title' => $title,
            'subtitle' => $sub,
            'icon' => $icon,
            'valid' => ($valid ? 'yes' : 'no'),
            'autocomplete' => $auto
        );

        if (!is_null($type)) {
            $temp['type'] = $type;
        };

        array_push($this->results, $temp);

        return $temp;
    }

    /**
     * Description:
     * Remove all items from an associative array that do not have a value
     *
     * @param string|null $a - Associative array
     * @return boolean
     */
    protected function emptyFilter($a)
    {
        if ($a == '' || $a == null) {                        // if $a is empty or null
            return false;                                    // return false, else, return true
        } else {
            return true;
        }
    }

    /**
     * @return boolean
     */
    private function setupCachePath()
    {
        if ($this->bundleId) {
            $version = $this->getAlfredVersion();
            $this->cachePath = sprintf($this->home . self::PATH_CACHE . $this->bundleId, $version);
            if (!file_exists($this->cachePath)) {
                return mkdir($this->cachePath);
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    private function setupDataPath()
    {
        if ($this->bundleId) {
            $version = $this->getAlfredVersion();
            $this->dataPath = sprintf($this->home . self::PATH_DATA . $this->bundleId, $version);
            if (!file_exists($this->dataPath)) {
                return mkdir($this->dataPath);
            }
        }
        return false;
    }

    /**
     * @return integer
     * @throws \Exception
     */
    protected function getAlfredVersion()
    {
        $applicationFolder = '/Applications';
        if (file_exists($applicationFolder . '/Alfred 2.app')) {
            return 2;
        } elseif (file_exists($applicationFolder . '/Alfred 3.app')) {
            return 3;
        }
        throw new \Exception("Unable to determine which Alfred version you are using");
    }

    /**
     * @param $filename string
     * @param $key string
     * @param $value mixed
     */
    protected function writeToPList($filename, $key, $value)
    {
        // We will redirect any errors to /dev/null to discard them,
        // otherwise they would be passed through to the output by PHP.
        exec( sprintf( 'defaults write %s %s %s 2>/dev/null',
            escapeshellarg( $filename ),
            escapeshellarg( $key ),
            escapeshellarg( $value ) ) );
    }
}
