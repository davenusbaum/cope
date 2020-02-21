<?php
/**
 * Application.php
 *
 * Copyright 2020 David Nusbaum
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
namespace nusbaum\cope;
use nusbaum\cope\StringHelper as Str;

/**
 * The Application defines the basic stucture for the ... Application.
 */
class Application {
    
    /** @var array The Application environment */
    private $env;
    
    /** @var string The logon uri for the Application. */
    private $logonUri = 'logon.do';
    
    /** @internal string */
    private $lazyBaseDir;
    
    /** @var array The command map for the current request. */
    private $lazyCommandMap;
    
    /** @var string The unique short id for the Application */
    private $id;
    
    /** @var string The full path to the map directory. */
    private $lazyMapDir;
    
    /** @var string The full path to the page directory. */
    private $lazyPageDir;
    
    /** @var array The list of possible scope values */
    private $scope;
    
    /** @var string The full path the script directory. */
    private $lazyScriptDir;
    
    /**
     * Create a new Application object with the specified id and scope.
     * @param string $id The unique short for the Application.
     * @param array|string $scope An array or '|' separated string 
     * of valid scope names.
     */
    public function __construct($id,$scope) {
        $this->env = &$_ENV;
        $this->id = $id;
        $this->scope = is_array($scope) 
            ? $scope 
            : explode('|',str_replace(' ','',$scope));
    }
    
    /**
     * Create a new Application object with the specified id and scope.
     * @param string $id The unique short for the Application.
     * @param array|string $scope An array or '|' separated string
     * of valid scope names.
     * @return Application
     */
    public static function build($id,$scope) {
        return (new static($id,$scope));
    }
    
    /**
     * Returns the path to the base directory for the environment.
     * Defaults to one directory below the current directory, but
     * can be explicitly set with COPE_BASE_DIR environment variable.
     *
     * @return string
     */
    public function getBaseDir() {
        return isset($this->lazyBaseDir)
            ? $this->lazyBaseDir
            : ($this->lazyBaseDir = dirname(getcwd()));
    }
    
     /**
      * Return an array of default valuess for the command parameters.
      * @return array
      */
     public function getCommandDefaults() {
         return array(
             'session' => true,
             'authenticate' => true,
             'authorize' => null,
             'access_level' => 1,
             'validate' => null,
             'post'=> null,
             'get' => null,
             'page' => null,
             'tab' => null,
             'tabgroup' => null
         );
     }
     
     /**
      * Return an associative array of commands for the current request scope.
      * @param boolean $keep true to keep the loaded command map
      * @return array
      */
     public function getCommandMap($keep = false) {
         if(!isset($this->lazyCommandMap)) {
             $filename = $this->getMapDir().'/'. $this->getScope().'.php';
             if (!($map = @include ($filename))) {
                 trigger_error ( "Could not load $filename", E_USER_WARNING );
                 $map = array();
             }
             if($keep) {
                 $this->lazyCommandMap = $map;
             }
         } else {
             $map = $this->lazyCommandMap;
             if(!$keep) {
                 $this->lazyCommandMap = null;
             }
         }
         return $map;
     }
     
     /**
      * Returns an environment variable.
      * @param string $name
      * @param mixed $default
      * @return mixed
      */
     public function getEnv($name,$default = null) {
         if(isset($_ENV[$name])) {
             $value = trim($this->env[$name]);
             
             switch (strtolower($value)) {
                 case 'true':
                 case '(true)':
                     return true;
                 case 'false':
                 case '(false)':
                     return false;
                 case 'empty':
                 case '(empty)':
                     return '';
                 case 'null':
                 case '(null)':
                     return;
             }
             
             if (Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
                 $value = substr($value, 1, -1);
             }
             
             return $value;
         }
         return $default;
     }
     
     /**
      * Returns the short unique id for this environment.
      * This should be explicitly set with an 'ID' environment variable, but
      * a unique value will be generated if necessary.
      *
      * @return string
      */
     public function getId() {
         return isset($this->id)
            ? $this->id
            : ($this->id = base_convert(crc32($this->getBaseDir()),16,32));
     }
     
     /**
      * Returns the logon url for the environment.
      * Default is 'logon.do'.
      *
      * @return string
      */
     public function getLogonUri() {
         return $this->logonUri;
     }
     
     /**
      * The path to the map directory for this environment.
      * Defaults to /maps under the base directory, but can be
      * explicitly set with MAP_DIR environment variable.
      *
      * @return string
      */
     public function getMapDir() {
         return isset($this->lazyMapDir)
            ? $this->lazyMapDir
            : ($this->lazyMapDir = $this->getBaseDir() . '/maps');
     }
     
     /**
      * The path to the page directory for this environment.
      * Defaults to /pages under the base directory, but can be
      * explicitly set.
      *
      * @return string
      */
     public function getPageDir() {
         return isset($this->lazyPageDir)
            ? $this->lazyPageDir
            : ($this->lazyPageDir = $this->getBaseDir() . '/pages');
     }
     
     /**
      * Returns an array of valid scope names for a request.
      *
      * @var array
      */
     public function getScope() {
         return $this->scope;
     }
     
     /**
      * Returns the path to the scripts directory for this environment.
      * Defaults to /scripts under the base directory, but can be
      * explicitly set with SCRIPT_DIR environment variable.
      *
      * @return string
      */
     public function getScriptDir() {
         return isset($this->lazyScriptDir)
            ? $this->lazyScriptDir
            : ($this->lazyScriptDir = $this->getBaseDir() . '/scripts');
     }
     
     /**
      * Return true if there is a HTTP request and false if CLI.
      * @return boolean
      */
     public function hasRequest() {
         return http_response_code()!==FALSE;
     }
     
     /**
      * Set the base directory for the Application.
      * @param string $dir
      * @return \nusbaum\cope\Application
      */
     public function withBaseDir($dir) {
         if($this->lazyBaseDir != $dir) {
             $this->lazyBaseDir = $dir;
             // reset the map and script directories
             $this->lazyMapDir = null;
             $this->lazyPageDir = null;
             $this->lazyScriptDir = null;
         }
         return $this;
     }
     
     /**
      * Override use of the default superglobal $_ENV
      * @param array $env
      * @return \nusbaum\cope\Application
      */
     public function withEnv(&$env) {
         $this->env = &$env;
         return $this;
     }
     
     /**
      * Return the Application with the logon uri set to the
      * specified value.
      * @param string $uri
      * @return \nusbaum\cope\Application
      */
     public function withLogonUri($uri) {
         $this->logonUri = $uri;
         return $this;
     }
     
     /**
      * Set the map directory for the Application.
      * @param string $dir
      * @return \nusbaum\cope\Application
      */
     public function withMapDir($dir) {
         $this->lazyMapDir = $dir;
         return $this;
     }
     
     /**
      * Set the page directory for the Application.
      * @param string $dir
      * @return \nusbaum\cope\Application
      */
     public function withPageDir($dir) {
         $this->lazyPageDir = $dir;
         return $this;
     }

     /**
      * Set the script directory for the Application.
      * @param string $dir
      * @return \nusbaum\cope\Application
      */
     public function withScriptDir($dir) {
         $this->lazyScriptDir = $dir;
         return $this;
     }
}
