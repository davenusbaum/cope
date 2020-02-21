<?php
/**
 * Context.php
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
 * The cope Context supports a simple command driven MVC framework and
 * provides helper functions over PHP's $_ENV, $_SERVER and $_REQUEST
 * superglobal arrays.
 */
class Context {

    /** Messages key */
    const MESSAGES = 'MESSAGES';

    /** User key */
    const USER = 'USER';

	/** @var boolean True if an error message has been sent. */
	private static $hasError = false;

	/** @var string The logon uri for the application. */
	private static $logonUri = 'logon.do';

	/** @internal string */
	private static $lazyBaseDir;

	/** @var string The base path for the the current request. */
	private static $lazyBasePath;

	/** @var string The base url for the current request. */
	private static $lazyBaseUrl;

	/** @var array The command properties for the current request. */
	private static $lazyCommand;

	/** @var array The command map for the current request. */
	private static $lazyCommandMap;

	/** @var string The hosname for the current request. */
	private static $lazyHostname;

	/** @var string The unique short id for the application */
	private static $lazyId;

	/** @var string The full path to the map directory. */
	private static $lazyMapDir;

	/** @var array An array of messages */
	private static $lazyMessages;

	/** @var string The HTTP method for this request. */
	private static $lazyMethod;

	/** @var string The full path to the page directory. */
	private static $lazyPageDir;

	/** @var string The url path after the base path. */
	private static $lazyPath;

	/** @var array The parameters passe in the path */
	private static $lazyPathParams;

	/** @var int The TCP/IP port for this request. */
	private static $lazyPort;

	/** @internal string */
	private static $lazyRemoteAddr;

	/** @var string The scheme used for the request. */
	private static $lazyScheme;

	/** @var array The list of possible scope values */
	private static $lazyScopeList;

	/** @var string The full path the script directory. */
	private static $lazyScriptDir;

	/** @var string The full name of the base script for this request. */
	private static $lazyScriptName;

	/**
	 * Clear the current context.
	 */
	public static function clear() {
	    if(session_status() === PHP_SESSION_ACTIVE) {
	        session_unset();
	    }
	}

	/**
	 * Destroy the current context.
	 */
	public static function destroy() {
	    //remove session cookie from browser
	    if ( isset( $_COOKIE[session_name()] ) ) {
	        setcookie( session_name(), "", time()-3600, "/" );
	    }
	    //clear session
	    self::clear();
	    //clear session from disk
	    session_destroy();
	}

	/**
	 * Return the action requested through the url path parameters.
	 * The 'do' parameter without the '.do'.
	 * @return string
	 */
	public static function getAction() {
	    return self::getPathParams()['action'];
	}

	/**
	 * Returns the value for a named session attribute.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getAttribute($name,$default=null) {
	    if(!isset($_SESSION)) {
	        self::start();
	    }
	    if(isset($_SESSION[$name])) {
	        return $_SESSION[$name];
	    }
	    return $default;
	}

	/**
	 * Returns the path to the base directory for the environment.
	 * Defaults to one directory below the current directory, but
	 * can be explicitly set with COPE_BASE_DIR environment variable.
	 *
	 * @return string
	 */
	public static function getBaseDir() {
	    return isset(self::$lazyBaseDir)
	       ? self::$lazyBaseDir
	       : (self::$lazyBaseDir = dirname(getcwd()));
	}

	/**
	 * Returns the portion of the request path that comes before the application
	 * command parameters.
	 * ```
	 * /<base_path>/<kiosk>/<scope>/<action>.do
	 * ```
	 * This is equivalent to Apache RewriteBase
	 * @return string
	 */
	 public static function getBasePath() {
	 	if(!isset(self::$lazyBasePath)) {
	 		$script = self::getScriptName();
	 		self::$lazyBasePath = substr (
	 				$script,
	 				0,
	 				strrpos($script, '/' ));
	 	}
	 	return self::$lazyBasePath;
	 }

	 /**
	  * The base url for the request.
	  * @return string
	  */
	 public static function getBaseUrl() {
	 	if(!isset(self::$lazyBaseUrl)) {
	 	    self::$lazyBaseUrl =
	 			self::getScheme()
	 			. '://'
	 			. self::getHostname()
	 			. ((self::getPort() == 80 || self::getPort() == 443)
	 				? ''
	 				: ':'.self::getPort())
	 			. self::getBasePath();
	 	}
	 	return self::$lazyBaseUrl;
	 }


	/**
      * Returns the requested command, or command property, based on the path parameters.
      * @param string $property optional property name
      * @param mixed $default optional default value for the supplied property name.
      * @return mixed
      */
     public static function getCommand($property = null,$default = null) {
         if(!isset(self::$lazyCommand)) {

             // get the command map
             $map = self::getCommandMap();

             // load the command
             $action = self::getAction();
             if (isset ( $map [$action])) {
                 $command = $map [$action];
             } else if (array_key_exists ( null, $map )) {
                 $command = $map [null];
             } else {
                 return;
             }

             // set the command
             self::$lazyCommand = is_object($command)
             ? (array)$command
             : array_merge(self::getCommandDefaults(),$command);
         }

         if(func_num_args() > 0) {
             if(isset(self::$lazyCommand[$property])) {
                 return self::$lazyCommand[$property];
             }
             return $default;
         }

         return self::$lazyCommand;
     }

	/**
	 * Return an array of default valuess for the command parameters.
	 * @return array
	 */
	public static function getCommandDefaults() {
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
	public static function getCommandMap($keep = false) {
	    if(!isset(self::$lazyCommandMap)) {
	        $filename = self::getMapDir().'/'. self::getScope().'.php';
	        if (!($map = @include ($filename))) {
	            trigger_error ( "Could not load $filename", E_USER_WARNING );
	            $map = array();
	        }
	        if($keep) {
	            self::$lazyCommandMap = $map;
	        }
	    } else {
	        $map = self::$lazyCommandMap;
	        if(!$keep) {
	            self::$lazyCommandMap = null;
	        }
	    }
		return $map;
	}

	/**
	 * Get a command parameter
	 * @param string $name
	 * @param mixed $default
	 * @return string
	 */
	public static function getCommandParam($name,$default=null) {
	    return self::pick(self::getCommand(), $name,$default);
	}

	/**
	 * The content type for the request.
	 * This is only set on POST requests.
	 * @var string|null
	 */
	public static function getContentType() {
		return isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : null;
	}

	/**
	 * Returns an environment variable.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getEnv($name,$default = null) {
	    if(isset($_ENV[$name])) {
            $value = trim($_ENV[$name]);

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
	 * Return the named request header.
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public static function getHeader($name,$default=null) {
		$name = 'HTTP_'.strtoupper(str_replace('-','_',$name));
		if(isset($_SERVER[$name])) {
			return $_SERVER[$name];
		}
		return $default;
	}

	/**
	 * Return the hostname used for the request.
	 * @todo Needs to safely handle X-Forwarded-Host
	 * @return string
	 */
	public static function getHostname() {
	    if(!isset(self::$lazyHostname)) {
	        self::$lazyHostname = $_SERVER['SERVER_NAME'];
	    }
	    return self::$lazyHostname;
	}

	/**
	 * Returns the short unique id for this environment.
	 * This should be explicitly set with an 'ID' environment variable, but
	 * a unique value will be generated if necessary.
	 *
	 * @return string
	 */
	public static function getId() {
        return isset(self::$lazyId)
            ? self::$lazyId
            : (self::$lazyId = base_convert(crc32(self::getBaseDir()),16,32));
	}

	/**
	 * The decoded data from json input
	 * @return array
	 */
	public static function getJson() {
	    if(self::isJson()) {
	        $content = json_decode(file_get_contents('php://input'),1);
	        if($content && is_array($content)) {
	            return $content;
	        }
	    }
	    return array();
	}

	/**
	 * Returns the kiosk name passed through the url path parameters.
	 * The kiosk name is a unique identifier for an account, but may
	 * not be the actual number identifier for an account.
	 * @return string
	 */
	public static function getKiosk() {
	    return self::getPathParams()['kiosk'];
	}

	/**
	 * Returns the logon url for the environment.
	 * Default is 'logon.do'.
	 *
	 * @return string
	 */
	public static function getLogonUri() {
	    return self::$logonUri;
	}

	/**
	 * The path to the map directory for this environment.
	 * Defaults to /maps under the base directory, but can be
	 * explicitly set with MAP_DIR environment variable.
	 *
	 * @return string
	 */
	public static function getMapDir() {
        return isset(self::$lazyMapDir)
            ? self::$lazyMapDir
            : (self::$lazyMapDir = self::getBaseDir() . '/maps');
	}

	/**
	 * Return an array of messages that have been sent in this context
	 * @return array
	 */
	public static function getMessages() {
	    if(!isset(self::$lazyMessages)) {
	        // get any messages stored in the session
	        if(self::$lazyMessages = self::getAttribute(self::MESSAGES)) {
	            self::setAttribute(self::MESSAGES, null);
	        } else {
	           self::$lazyMessages = array();
	        }
	    }
	    return self::$lazyMessages;
	}

	/**
	 * Returns the HTTP method for the request.
	 * @return string
	 */
	public static function getMethod() {
	    if(!isset(self::$lazyMethod)) {
	        self::$lazyMethod = isset($_SERVER['REQUEST_METHOD'])
	           ? $_SERVER['REQUEST_METHOD']
	           : null;
	    }
	    return self::$lazyMethod;
	}

	/**
	 * The path to the page directory for this environment.
	 * Defaults to /pages under the base directory, but can be
	 * explicitly set.
	 *
	 * @return string
	 */
	public static function getPageDir() {
        return isset(self::$lazyPageDir)
            ? self::$lazyPageDir
            : (self::$lazyPageDir = self::getBaseDir() . '/pages');
	}

	/**
	 * Returns the path to a page file.
	 *
	 * @param string $name The name of the page
	 * @return string the page file name
	 */
	public static function getPagePath($name) {
	    return self::getPageDir()
	        . '/'
	        . self::getScope()
	        . '/'
	        . $name;
	}

	/**
	 * Returns a named input parameter
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getParameter($name,$default=null) {
	    if(isset($_REQUEST[$name])) {
	       $value = $_REQUEST[$name];
	       return Str::trimAll($value);
	    }
	    return $default;
	}


	/**
	 * Returns the HTTP method for the request.
	 * @return string
	 */
	public static function getPath() {
		return isset(self::$lazyPath)
		    ? self::$lazyPath
		    : (self::$lazyPath = substr(
					parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),
					strlen(self::getBasePath())));
	}

	/**
	 * Returns the path parameters passed in the request path.
	 * @return string[]
	 */
	public static function getPathParams() {
        return isset(self::$lazyPathParams)
            ? self::$lazyPathParams
            : (self::$lazyPathParams = self::parsePath(
                self::getPath(),
                self::getScopeList()));
	}

	/**
	 * Returns the port used for the request.
	 * @todo Need to safely handle X-Forwarded-Host header
	 * @return int
	 */
	public static function getPort() {
	    if(!isset(self::$lazyPort)) {
	        self::$lazyPort = isset($_SERVER['SERVER_PORT'])
	        ? intval($_SERVER['SERVER_PORT'])
	        : 80;
	    }
	    return self::$lazyPort;
	}

	/**
	 * Return the remote ip address used for this request.
	 * @todo Safely handle X-Forwarded-For header.
	 * @return string
	 */
	public static function getRemoteAddr() {
	    if(!isset(self::$lazyRemoteAddr)) {
	        self::$lazyRemoteAddr = isset($_SERVER['REMOTE_ADDR'])
	           ? $_SERVER['REMOTE_ADDR']
	           : null;
	    }
	    return self::$lazyRemoteAddr;
	}

	/**
	 * Returns the scope of this context based on the url path parameters
	 * ( /kiosk/scope/action.do ).
	 * @return string
	 */
	public static function getScope() {
		return self::getPathParams()['scope'];
	}

	/**
	 * Returns the '|' separated list of valid scope names for this environment.
	 * This value *should be set with the SCOPE_LIST environment variable,
	 * but a list be be generated from the file system if necessary.
	 *
	 * @var string
	 */
	public static function getScopeList() {
		if(!isset(self::$lazyScopeList)) {
            trigger_error('Context::scopeList should be set!!',E_USER_WARNING);
            self::$lazyScopeList = implode('|',array_map(function ($s) {
				    return substr(basename($s),0,- 4);
			    },glob(self::getMapDir() . '/*.php')));
		}
		return self::$lazyScopeList;
	}

	/**
	 * Returns the path to the scripts directory for this environment.
	 * Defaults to /scripts under the base directory, but can be
	 * explicitly set with SCRIPT_DIR environment variable.
	 *
	 * @return string
	 */
	public static function getScriptDir() {
	    return isset(self::$lazyScriptDir)
	       ? self::$lazyScriptDir
	       : (self::$lazyScriptDir = self::getBaseDir() . '/scripts');
	}

	/**
	* Returns the full path to an application script.
	* @param string $name The name of the script
	* @return string the script file name
	*/
    public static function getScriptPath($name = null) {
	    return self::getScriptDir()
	        .'/'
	        . self::getScope()
	        . '/'
	        . $name;
	}

	/**
	 * Returns the request scheme used by the client.
	 * @todo Needs to safely handle X-Forwarded-Proto
	 * @return string
	 */
	public static function getScheme() {
	    if(!isset(self::$lazyScheme)) {
	        self::$lazyScheme = (
					443 == self::getPort()
					||  8443 == self::getPort()
					|| (isset($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'])
					) ? 'https': 'http';
		}
		return self::$lazyScheme;
	}

	/**
	 * Returns the pathname of the currently executing script.
	 */
	public static function getScriptName() {
        if(!isset(self::$lazyScriptName)) {
            // check for built in php server
            if (php_sapi_name() == 'cli-server') {
                self::$lazyScriptName = '';
            } else {
                if(isset($_SERVER['PATH_INFO'])
                    && 0 === substr_compare(
					   $_SERVER['PHP_SELF'],
					   $_SERVER['PATH_INFO'],
                       - ($len=strlen($_SERVER['PATH_INFO'])))) {
                    self::$lazyScriptName =  substr($_SERVER['PHP_SELF'],0,-$len);
                } else {
                    self::$lazyScriptName = $_SERVER['PHP_SELF'];
                }
                // should be a relative path
                //if(self::$lazyScriptName[0] = '/') {
                //    self::$lazyScriptName = substr(self::$lazyScriptName,1);
                //}
            }
        }
        return self::$lazyScriptName;
	}

	/**
	 * Returns the current response status code
	 * @return mixed
	 */
	public static function getStatus() {
		return http_response_code();
	}

	/**
	 * Returns true if an error message is present.
	 * @return boolean
	 */
	public static function hasError() {
		return self::$hasError;
	}

	/**
	 * Returns true if there are messages
	 * @return boolean
	 */
	public static function hasMessages() {
	    return (!empty(self::$lazyMessages));
	}

	/**
	 * Returns true if there are session attributes.
	 * A session will NOT be automatically created.
	 * @return boolean
	 */
	public static function hasAttributes() {
	    return (!empty($_SESSION));
	}

	/**
	 * Import json data into the context parameters
	 */
	public static function importJson() {
	    if(self::isJson()) {
	        foreach (self::getJson() as $name => $value) {
	            $_REQUEST[$name] = $value;
	        }
	    }
	}

	/**
	 * Return true if the content type is application/json
	 * @return boolean
	 */
	public static function isJson() {
	    if(Str::endsWith(self::getContentType(),'json')) {
	        return true;
	    }
	    return false;
	}

	/**
	 * Returns true it the response status is 200.
	 * @return boolean
	 */
	public static function isOk() {
	    return (200 == self::getStatus()) ? true : false;
	}

	/**
	 * Returns true if the command is being redirected.
	 * @return boolean
	 */
	public function isRedirect() {
	    return in_array(http_response_code(),[301,302,303,307,308]);
	}

	/**
	 * Return true if there is a HTTP request and false if CLI.
	 * @return boolean
	 */
	public static function isRequest() {
	    return http_response_code()!==FALSE;
	}

	/**
	 * Returns true if the response code is still 200.
	 * @return boolean
	 */
	public static function isResponseOk() {
	    return (200 == http_response_code()) ? true : false;
	}

	/**
	 * Return true if the command request is a
	 * @return boolean
	 */
	public static function isPost() {
		return ('POST' === self::getMethod() ? true : false);
	}

	/**
	 * Returns the application parameters parsed from the supplied path.
	 * A parameters are parsed based on the following pattern:
	 *
	 * > `<kiosk>/<scope>/<action>.do`
	 *
	 * **kiosk** represent the unique entry point for a client/account.
	 *
	 * **scope** represents the request scope, or sub-application
	 *
	 * **action** represent the command to be executed
     *
	 * @param string $path The path to parse
	 * @return array
	 */
	public static function parsePath($path) {
		// create a path params object
		$params = ['kiosk'=>null,'scope'=>null,'action'=>null];

		// explode the path
		$parts = explode ( '/', trim($path,"/ \t\n\r\0\x0B"));

		$scope = explode('|',self::getScopeList());

		// check for .do parameter
		if (count ( $parts ) > 0
				&& strlen ( ($s = end ( $parts )) ) > 3
				&& substr_compare ( $s, '.do', - 3 ) == 0)
		{
			$params['action'] = substr ( array_pop ( $parts ), 0, - 3 );
		}

		// check for scope parameter
		if (count( $parts ) > 0 && in_array( ($s = end($parts)), $scope )) {
			$params['scope'] = array_pop ( $parts );
		}

		// check for kiosk
		if (count ( $parts ) == 1) {
			$params['kiosk'] = array_pop ( $parts );
		}

		// if we have kiosk and no scope, set the defaut scope
		if(count($parts) == 0) {
			if(isset($kiosk_scope) && isset($params['kiosk']) && !isset($params['scope'])) {
				$params['scope'] = end($scope);
			} else if(!isset($params['scope'])) {
				$params['scope'] = reset ($scope);
			}
		} else {
			$params['kiosk'] = $params['scope'] = $params['action'] = null;
		}

		return $params;
	}

	/**
	 * Send a redirect to the client
	 * @param string $to
	 * @param int $status
	 * @return boolean
	 */
	public static function redirect($to,$status = null) {
		// make sure headers are not already sent
		if (headers_sent()) {
			return false;
		}

		// save messages to the session
		if(isset(self::$messages) && isset($_SESSION)) {
			$_SESSION['MESSAGES'] = self::$messages;
		}

		// check for full redirect URL
		if(FALSE === strpos($to, '://')) {
			//build our own local redirect
			if(substr_compare($to, '/',0,1) !== 0 ) {
				$to = '/'.$to;
			}
			$to = self::getBaseUrl().$to;
		}

		// status depends on http protocol
		if(!$status) {
			$status = strpos($_SERVER['SERVER_PROTOCOL'],'1.1') ? 303 : 302;
		}
		// set redirect headers
		http_response_code($status);
		header("Location: $to");
		return true;
	}

	/**
	 * Reset all of the properties for this context.
	 */
	public static function reset() {
	    self::$hasError = false;
	    self::$logonUri = 'logon.do';
	    self::$lazyBaseDir = null;
	    self::$lazyBasePath = null;
	    self::$lazyBaseUrl = null;
	    self::$lazyCommand = null;
	    self::$lazyCommandMap = null;
	    self::$lazyHostname = null;
	    self::$lazyId = null;
	    self::$lazyMapDir = null;
	    self::$lazyMethod = null;
	    self::$lazyPageDir = null;
	    self::$lazyPath = null;
	    self::$lazyPathParam = null;
	    self::$lazyPort = null;
	    self::$lazyRemoteAddr = null;
	    self::$lazyScheme = null;
	    self::$lazyScopeList = null;
	    self::$lazyScriptDir = null;
	    self::$lazyScriptName = null;
	}

	/**
	 * Run the filter stack.
	 */
	public static function run() {
		if (isset ( self::$functions ) && count ( self::$functions )) {
			foreach ( call_user_func ( array_shift ( self::$functions )) as $v ) {
				self::run();
			}
		}
	}

	/**
	 * Send the body as json.
	 * The content-type is set to application/json
	 * @param mixed $json
	 */
	public static function sendJson($json) {
	    header('Content-Type: application/json');
	    echo json_encode($json);
	}

	/**
	 * Send a message to be displayed on the user page.
	 * @param string $message the message to send.
	 * @param int $type the message type
	 * @param string $field the name of the input field in error
	 */
	public static function sendMessage($message, $type = 0, $field = null) {
        if(E_USER_ERROR == $type) {
            self::$hasError = true;
        }
        // initialize messages if necessary
        if(!isset(self::$lazyMessages)) {
            self::getMessages();
        }
        self::$lazyMessages[] = array($type,$message,$field);
	}

	/**
	 * Send a redirect to the client
	 * @param string $to
	 * @param int $status
	 * @return boolean
	 */
	public static function sendRedirect($to,$status = null) {

	    // make sure headers are not already sent
	    if (headers_sent()) {
	        return false;
	    }

	    // save messages to the session
	    if(self::hasMessages() && self::hasAttributes()) {
	        self::setAttribute(self::MESSAGES, self::getMessages());
	    }

	    // check for full redirect URL
	    if(FALSE === strpos($to, '://')) {
	        //build our own local redirect
	        if(substr_compare($to, '/',0,1) !== 0 ) {
	            $to = '/'.$to;
	        }
	        $to = self::getBaseUrl().$to;
	    }

	    // status depends on http protocol
	    if(!$status) {
	        $status = strpos($_SERVER['SERVER_PROTOCOL'],'1.1') ? 303 : 302;
	    }
	    // set redirect headers
	    http_response_code($status);
	    header("Location: $to");
	    return true;
	}

	/**
	 * Send the status to the client.
	 * @param int $status_code
	 * @param string $message The message to be sent with the status code.
	 */
	public static function sendStatus($status_code,$message=null) {
		http_response_code($status_code);
		echo $message;
	}

	/**
	 * Set a global value.
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public static function set($name,$value) {
		return ($GLOBALS[$name] = $value);
	}

	/**
	 * Set a session attribute.
	 * @param string $name
	 * @param mixed $value
	 */
	public static function setAttribute($name,$value) {
	    if($value === null) {
	        unset($_SESSION[$name]);
	        return null;
	    }
	    return ($_SESSION[$name] = $value);
	}

	/**
	 * Set the base directory for the application.
	 * @param string $dir
	 */
	public static function setBaseDir($dir) {
	    if(self::$lazyBaseDir != $dir) {
	       self::$lazyBaseDir = $dir;
	       // reset the map and script directories
	       self::$lazyMapDir = null;
	       self::$lazyPageDir = null;
	       self::$lazyScriptDir = null;
	    }
	    return $dir;
	}

	/**
	 * Set the unique short id for the application.
	 * @param string $id
	 */
	public static function setId($id) {
	    if(!isset(self::$lazyId)) {
	        self::$lazyId = $id;
	    } else if(self::$lazyId != $id) {
	        trigger_error('The context id cannot be reset',E_USER_WARNING);
	    }
	}

	/**
	 * Set the map directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setMapDir($dir) {
	    return (self::$lazyMapDir = $dir);
	}

	/**
	 * Set the page directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setPageDir($dir) {
	    return (self::$lazyPageDir = $dir);
	}


	/**
	 * Set a request parameter
	 * @param string $name
	 * @param mixed $value
	 */
	public static function setParameter($name,$value) {
	    if($value === null) {
	        unset($_REQUEST[$name]);
	        return null;
	    }
		return ($_REQUEST[$name] = $value);
	}

	/**
	 * Set the valid scope names as a '|' separated list of values.
	 * @param string $list
	 * @return string
	 */
	public static function setScopeList($list) {
	    self::$lazyScopeList = str_replace(' ','',$list);
	}

	/**
	 * Set the script directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setScriptDir($dir) {
	    return (self::$lazyScriptDir = $dir);
	}

	/**
	 * Start a session to store persistent attributes
	 * @return boolean
	 */
	public static function start() {
	    if(self::isRequest()
	        && (PHP_SESSION_ACTIVE == ($status = session_status())
	            || (PHP_SESSION_NONE == $status && session_start()))) {
	        return true;
	    } else {
	        if(!isset($_SESSION)) {
	            // create a dummy session array
	            $_SESSION = array();
	        }
	       return false;
	    }
	}

	/**
	 * Return the context properties as an array.
	 * @return array
	 */
	public static function toArray() {
	    return [
	        'action' => self::getAction(),
	        'baseDir' => self::getBaseDir(),
	        'basePath' => self::getBasePath(),
	        'baseUrl' => self::getBaseUrl(),
	        'command' => self::getCommand(),
	        'contentType' => self::getContentType(),
	        'kiosk' => self::getKiosk(),
	        'hasError' => self::hasError(),
	        'hostname' => self::getHostname(),
	        'id' => self::getId(),
	        'isJson' => self::isJson(),
	        'isRequest' => self::isRequest(),
	        'isPost' => self::isPost(),
	        'logonUri' => self::getLogonUri(),
	        'mapDir' => self::getMapDir(),
	        'method' => self::getMethod(),
	        'messages' => self::getMessages(),
	        'pageDir' => self::getPageDir(),
	        'path' => self::getPath(),
	        'pathParams' => self::getPathParams(),
	        'port' => self::getPort(),
	        'remoteAddr' => self::getRemoteAddr(),
	        'scope' => self::getScope(),
	        'scopeList' => self::getScopeList(),
	        'scriptDir' => self::getScriptDir(),
	        'scheme' => self::getScheme(),
	        'scriptName' => self::getScriptName()
	    ];
	}

	/**
	 * Returns a url for the specified action, scope and kiosk.
	 * Options: base_url,kiosk,scope,action
	 * @param array $options
	 * @return string
	 */
	public static function url($options) {

		$url='';

		// set the base url
		if(isset($options['base_url'])) {
			$url .= $options['base_url'];
			unset($options['base_url']);
		} else {
			$url .= self::getBaseUrl();
		}

		// set the kiosk
		if(isset($options['kiosk'])) {
			$kiosk =  $options['kiosk'];
			unset($options['kiosk']);
		} else {
			$kiosk = self::getKiosk();
		}
		if(!empty($kiosk)) {
			$url .= '/'.$kiosk;
		}

		// set the scope
		if(isset($options['scope'])) {
			$url .= '/'.$options['scope'];
			unset($options['scope']);
		} else {
			$url .= '/'.self::getScope();
		}

		if(isset($options['action'])) {
			$url .= '/'. $options['action'].'.do';
			unset($options['action']);
		} else {
			$url .= '/'. self::getAction().'.do';
		}

		if(count($options)) {
			$url .= '?'.http_build_query($options);
		}

		return $url;
	}
}
