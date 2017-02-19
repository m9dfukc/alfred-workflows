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
     * of the workflow bundle ID in the case that you want to specify a different bundle ID. This
     * would adjust the output directories for storing data.
     *
     * @param string $bundleId - optional bundle ID override; if omitted, uses ID from info.plist.
     */
    public function __construct(
        $bundleId = NULL )
    {
        $this->path = getcwd();
        $this->home = $_SERVER['HOME'];

        // Determine which bundle ID to use for data/cache storage.
        if( ! empty( $bundleId ) ) {
            $this->bundleId = $bundleId; // User-provided override.
        } else {
            $infoPlistPath = $this->filepath( 'path', self::INFO_PLIST );
            if( is_file( $infoPlistPath ) ) {
                $this->bundleId = $this->get( $infoPlistPath, 'bundleid' ); // Info.plist.
            }
        }

        // Fallback in case we couldn't determine the bundle ID.
        if( empty( $this->bundleId ) ) {
            $this->bundleId = 'com.alfredworkflow.unknownbundleid';
        }

        $this->setupCachePath();
        $this->setupDataPath();
    }

    /**
     * Description:
     * Gets the bundle ID for the current workflow.
     *
     * @return mixed The bundle ID string if available, otherwise FALSE.
     */
    public function bundle()
    {
        return (is_null($this->bundleId) ? FALSE : $this->bundleId);
    }

    /**
     * Description:
     * Gets the path to the cache directory for your workflow.
     *
     * @return mixed Path to the cache directory for your workflow if available,
     * otherwise FALSE.
     */
    public function cache()
    {
        return $this->cachePath ?: FALSE;
    }

    /**
     * Description:
     * Gets the path to the storage directory for your workflow.
     *
     * @return mixed Path to the storage directory for your workflow if
     * available, otherwise FALSE.
     */
    public function data()
    {
        return $this->dataPath ?: FALSE;
    }

    /**
     * Description:
     * Gets the script's working directory (from moment of class instantiation).
     * The working directory may have changed later, if you told PHP to do so,
     * and in that case it WON'T be reflected in this value!
     *
     * @return mixed Path to the initial working directory for your workflow if
     * available, otherwise FALSE.
     */
    public function path()
    {
        return $this->path ?: FALSE;
    }

    /**
     * Description:
     * Gets the home folder path for the current user.
     *
     * @return mixed Path to the current user's home folder if available,
     * otherwise FALSE.
     */
    public function home()
    {
        return $this->home ?: FALSE;
    }

    /**
     * Description:
     * Constructs a full file path to a file within any of the available basic
     * folders for your workflow. The file doesn't have to exist yet, and will
     * not be created by this function. So you can construct paths for files
     * that you only intend to create later.
     *
     * @param string $baseFolder Any of 'cache' (used for temporary caching),
     *                           'data' (used for permanent settings and data
     *                           storage), 'path' (the current working path
     *                           of the script; Alfred sets this to the
     *                           installed workflow's top level folder),
     *                           'home' (the user's home folder).
     * @param string $baseName The filename to append to the folder path. Can
     *                         contain slashes for subfolders if you want to.
     * @throws \Exception If the path couldn't be discovered or your filename
     *                    value is empty.
     * @return string The complete path to the file.
     */
    public function filepath(
        $baseFolder,
        $baseName )
    {
        // Determine which path they wanted.
        $discoveredPath = NULL;
        switch( $baseFolder ) {
        case 'cache':
        case 'data':
        case 'path':
        case 'home':
            // Executes cache(), data(), path() or home().
            $discoveredPath = $this->{$baseFolder}();
            break;
        }

        // Abort if we didn't find any valid path or no baseName provided.
        if( empty( $discoveredPath ) ) {
            throw new \Exception( sprintf( 'Unable to determine folder path for %s', $discoveredPath ) );
        }
        if( $baseName === NULL || $baseName === '' ) {
            throw new \Exception( 'You are not allowed to provide an empty filename' );
        }

        // Now just construct the complete filename path.
        return $discoveredPath . DIRECTORY_SEPARATOR . $baseName;
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
     * @param array $results - An optional associative array to convert instead of the internal array
     * @param string $format - format of data being passed (json or array), defaults to array
     * @return string - XML string representation of the array
     */
    public function toXml($results = NULL, $format = 'array')
    {
        if ($format == 'json') {
            $results = json_decode($results, TRUE);
        }

        if (is_null($results)) {
            $results = $this->results;
        }

        if (empty($results)) {
            return FALSE;
        }

        $items = new \SimpleXMLElement("<items></items>"); // Create new XML element

        foreach ($results as $result) {    // Loop through each object in the array
            $c = $items->addChild('item'); // Add a new 'item' element for each object
            $c_keys = array_keys($result); // Grab all the keys for that item
            foreach ($c_keys as $key) {    // For each of those keys

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
                    if ($result[$key] === NULL) {
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

        // Return XML string representation of the array
        return $items->asXML();
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
        $filename,
        $key,
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
        $filename,
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
        $filename,
        $key )
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
    public function request($url = NULL, array $options = NULL)
    {
        if (is_null($url)) {
            return FALSE;
        }

        $defaults = array(                                    // Create a list of default curl options
            CURLOPT_RETURNTRANSFER => TRUE,                    // Returns the result as a string
            CURLOPT_URL => $url,                            // Sets the url to request
            CURLOPT_FRESH_CONNECT => TRUE
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
        $query,
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
     * Accepts data and a string file name to store data in local file. Beware
     * that this function is DANGEROUS and that you almost always want to
     * use atomicwrite() instead, for protection against file corruption.
     *
     * @param string $filename - filename to write the data to
     * @param mixed $data - data to write to file; if it is an array, it will
     *                      be automatically JSON-encoded before writing to disk
     * @return mixed - number of bytes written on success, otherwise FALSE
     */
    public function write(
        $filename,
        $data )
    {
        // Handle JSON serialization if necessary.
        if( is_array( $data ) ) {
            $data = json_encode( $data );
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
        $filename,
        $data,
        $atomicSuffix = 'atomictmp' )
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
     * @param string $uid - a permanent Unique ID of the result, should be
     *                      unique; use NULL to omit the UID.
     * @param string $arg - the argument that will be passed; use NULL to omit.
     * @param string $title - The title of the result item
     * @param string $sub - The subtitle text for the result item
     * @param string $icon - the icon to use for the result item
     * @param bool $valid - sets whether the result item can be actioned
     * @param string $auto - autocompletion for the item; use NULL to omit.
     * @param string $type - special Alfred result types; use NULL to omit.
     * @return array - array item that will be passed back to Alfred
     */
    public function result($uid, $arg, $title, $sub, $icon, $valid = TRUE, $auto = NULL, $type = NULL)
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
     * Array filter function used for removing all items that do not have a value
     *
     * @param mixed $value - an array value
     * @return bool
     */
    protected function emptyFilter(
        $value )
    {
        if( $value === '' || $value === NULL ) {
            return FALSE; // Delete.
        } else {
            return TRUE; // Keep.
        }
    }

    /**
     * @return bool
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
        return FALSE;
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
        return FALSE;
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
