# Workflows Utility Class

*** Archives Steve Jobzniak's Alfred Workflows ***

Forked, extended and rewritten by @SteveJobzniak ([profile](https://github.com/SteveJobzniak)).

Several nice enhancements and fixes by @catharsisjelly.

Original, very buggy class by @jdfwarrior.


## Installation

```bash
composer clear-cache

composer init # only if you don't already have a composer.json in your project

composer config repositories.stevejobzniak-alfred-workflows vcs git@github.com:SteveJobzniak/AlfredWorkflows.git

composer require stevejobzniak/alfred-workflows:dev-master
```


## Usage

A PHP utility class for creating workflows with Alfred 2 and 3. This class provides functions for working with plist settings files, reading and writing data to files, generating Alfred feedback results, requesting remote data, and more.


### Initialization

To initialize the class object, use Composer's autoloading mechanism and instantiate a new object.

```php
require_once( __DIR__ . '/vendor/autoload.php' );
use AlfredApp\Workflows;
use AlfredApp\ResultIcons;
$wf = new Workflows();
```


### bundle()

Returns the current workflow bundle.

Example:

```php
$bundle = $wf->bundle();
```

Result:

```
com.exampleworkflow.examplename
```


### cache()

Returns the path to the cache folder for the current workflow.

Example:

```php
$cache = $wf->cache();
```

Result:

```
/Users/SteveJobzniak/Library/Caches/com.runningwithcrayons.Alfred-3/Workflow Data/com.exampleworkflow.examplename
```


### data()

Returns the path to the data folder for the current workflow.

Example:

```php
$data = $wf->data();
```

Result:

```
/Users/SteveJobzniak/Library/Application Support/Alfred 3/Workflow Data/com.exampleworkflow.examplename
```


### path()

Returns the path to the working directory of your workflow. Alfred sets that to the workflow's main folder.

Example:

```php
$path = $wf->path();
```

Result:

```
/Users/SteveJobzniak/Library/Application Support/Alfred 3/Alfred.alfredpreferences/workflows/user.workflow.931233C0-0266-42AF-87B5-8FF507F12C2D
```


### home()

Returns the path to the current user's home directory.

Example:

```php
$home = $wf->home();
```

Result:

```
/Users/SteveJobzniak
```


### filepath()

Constructs a full file path to a file within any of the available basic folders for your workflow. The file doesn't have to exist yet, and will not be created by this function. So you can construct paths for files that you only intend to create later. The available folder keywords are "cache", "data", "path" and "home". You _cannot_ use any other folder paths with this function! It is intended to shield you from making mistakes and accidentally writing something outside of those four folders.

Example:

```php
$settingsPath = $wf->filepath( 'data', 'settings.plist' );
// or...
$settingsPath = $wf->filepath( 'data', 'you/can/use/subfolders/settings.plist' );
```

Result:

```
/Users/SteveJobzniak/Library/Application Support/Alfred 3/Workflow Data/com.exampleworkflow.examplename/you/can/use/subfolders/settings.plist
```


### toXml()

Accepts a properly formatted array or JSON object and converts it to XML for creating Alfred feedback results. Both parameters are optional. If results have been created using the result() function, then passing no arguments will use the array of results created using the result() function. Arrays passed in must be an associative array with array key values for the following required values: uid, arg, title, subtitle and icon. You may also pass array key-value pairs for the following optional keys: valid and autocomplete.

Example with result() function:

```php
$wf->result( 'itemuid', 'itemarg', 'Some Item Title', 'Some Item Subtitle', 'icon.png', TRUE, 'autocomplete', 'file' );
echo $wf->toXml();
```

Example with array:

```php
$results = array();
$results[] = array(
    'uid' => 'itemuid',
    'arg' => 'itemarg',
    'title' => 'Some Item Title',
    'subtitle' => 'Some Item Subtitle',
    'icon' => 'icon.png',
    'valid' => 'yes',
    'autocomplete' => 'autocomplete',
    'type' => 'file'
);
echo $wf->toXml( $results );
```

Result:

```xml
<?xml version="1.0"?>
<items>
<item uid="itemuid" arg="itemarg" valid="yes" autocomplete="autocomplete" type="file">
<arg>itemarg</arg>
<title>Some Item Title</title>
<subtitle>Some Item Subtitle</subtitle>
<icon>icon.png</icon>
</item>
</items>
```


### set() and setmulti()

The set functions save values to a specified plist file, allowing you to store easily store data for your workflow.

There are two variants; set() which handles a single value, and setmulti() which handles multiple values at once.

Example:

```php
$settingsPlist = $wf->filepath( 'data', 'settings.plist' );
$wf->set( $settingsPlist, 'username', 'SteveJobzniak' );
$wf->setmulti( $settingsPlist, array( 'password' => 'example', 'website' => 'https://github.com/SteveJobzniak' ) );
```


### get()

Read a value from a plist file.

Example:

```php
$settingsPlist = $wf->filepath( 'data', 'settings.plist' );
$website = $wf->get( $settingsPlist, 'website' );
```

Result:

```
https://github.com/SteveJobzniak
```


### request()

Performs a cURL request on the specified URL. cURL options can be passed as an associative array in the $options argument. See [PHP.net](http://php.net/curl_setopt) for a list of available cURL options.

Example:

```php
$data = $wf->request( 'http://google.com' );
```


### mdfind()

Executes an mdfind command and returns results as an array of matching files.

If the second parameter is TRUE (which is the default if it's omitted), you will perform a simple, generic search in _all_ of Spotlight's metadata fields. Otherwise, if set to FALSE, you will instead perform an _advanced_ query where everything you provide is passed to mdfind exactly as-is, so that you can use _any_ of mdfind's command line parameters. Note that it's then _your_ job to escape all user-provided parameters with escapeshellarg().

Example:

```php
$results = $wf->mdfind( '"kMDItemContentType == com.apple.mail.emlx"', FALSE );
// or...
$results = $wf->mdfind( 'Alfred 3.app' );
var_export( $results );
```

Result:

```php
array (
  0 => '/Applications/Alfred 3.app',
)
```


### write()

Similar to set(), except that raw binary data is instead dumped directly into a file. If the data is an array, the data is serialized as JSON and written to the output file.

Beware that this function is DANGEROUS and that you almost always want to use atomicwrite() instead, for protection against file corruption.

Example:

```php
$data = 'This is something we want to cache!';
$cacheFile = $wf->filepath( 'cache', 'somecache.dat' );
$wf->write( $cacheFile, $data );
```


### atomicwrite()

Safely writes new contents to a file using an atomic two-step process.

If the script is killed before the write is complete, only the temporary trash file will be corrupted. That's very important, since Alfred might kill a script earlier than its completion.

This function also supports writing arrays to disk, in which case it serializes it to JSON first.

Example:

```php
$data = 'This is something we want to cache!';
$cacheFile = $wf->filepath( 'cache', 'somecache.dat' );
$wf->atomicwrite( $cacheFile, $data );
```


### read()

Opposite of write() / atomicwrite(). This function reads all binary data from the specified file and returns it. If we detect possible JSON data in the file, we will attempt to unserialize it. If it wasn't valid JSON, we return the contents as a regular binary string instead. You never have to worry about those details, since it will do the right thing for you automatically.

Example:

```php
$cacheFile = $wf->filepath( 'cache', 'somecache.dat' );
$data = $wf->read( $cacheFile );
```

Result:

```
This is something we want to cache!
```


### result()

Creates a new result item that is kept within the class object. This set of results is viewable via the results() functions, or can be formatted and returned as XML via the toXml() function.

The autocomplete value is optional. If no value is specified (NULL), Alfred will not be using any autocompletion for that item. If an empty string is specified (""), Alfred will use an empty autocompletion which returns you to your Script Filter keyword without any parameters. Note that if you want Enter to perform autocompletion just like Tab, then you need to set $valid to FALSE to tell Alfred to use autocompletion as the main action.

Possible values for $valid are TRUE or FALSE to set the validity of the result item. A valid item means that Alfred will "action" it and end any further script filtering if you press Enter. Your subsequent Alfred workflow processing blocks will then receive that valid, actioned item.

There is absolutely no point in providing a value for $uid most of the time. It would just cause Alfred to attempt to learn the user's preferred choices for any given combination of typed input terms (from the user) and what result they usually select after typing that term. But UIDs don't work well when the user can type any arbitrary search string, such as a unit conversion or currency conversion workflow. So just set the UID parameter to NULL or "" to omit it from the item.

The $type parameter should usually be omitted (or set to NULL), except when you're dealing with actual files on the user's disk.

All XML workflow parameters are documented in detail at [Alfred's website](https://www.alfredapp.com/help/workflows/inputs/script-filter/xml/).

Example:

```php
$wf->result( 'alfredapp', '/Applications/Alfred 3.app', 'Alfred 3', '/Applications/Alfred 3.app', 'fileicon:/Applications/Alfred 3.app', TRUE, 'Alfred 3', 'file' );
$wf->result( NULL, 'argument', 'title', 'subtitle', ResultIcons::ICON_WEB, FALSE, 'autocomplete' );
echo $wf->toXml();
```

Result:

```xml
<?xml version="1.0"?>
<items>
<item uid="alfredapp" arg="/Applications/Alfred 3.app" valid="yes" autocomplete="Alfred 3" type="file">
<arg>/Applications/Alfred 3.app</arg>
<title>Alfred 3</title>
<subtitle>/Applications/Alfred 3.app</subtitle>
<icon type="fileicon">/Applications/Alfred 3.app</icon>
</item>
<item arg="argument" valid="no" autocomplete="autocomplete">
<arg>argument</arg>
<title>title</title>
<subtitle>subtitle</subtitle>
<icon>/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/BookmarkIcon.icns</icon>
</item>
</items>
```


### results()

Returns a copy of the internal array of result items, meaning what would be printed by toXml().

Example:

```php
$wf->result( NULL, 'argument', 'title', 'subtitle', ResultIcons::ICON_WEB, FALSE, 'autocomplete' );
echo var_export( $wf->results() );
```

Result:

```php
array (
  0 =>
  array (
    'uid' => NULL,
    'arg' => 'argument',
    'title' => 'title',
    'subtitle' => 'subtitle',
    'icon' => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/BookmarkIcon.icns',
    'valid' => 'no',
    'autocomplete' => 'autocomplete',
  ),
)
```
