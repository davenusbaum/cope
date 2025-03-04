<?php

namespace Cope;

use Cope\StringHelper as Str;
use Exception;

/**
 * The cope Context supports a simple command driven MVC framework and
 * provides helper functions over PHP's $_ENV, $_SERVER and $_REQUEST
 * super global arrays.
 */
class Context {

    /** Messages key */
    const MESSAGES = 'MESSAGES';

    /** User key */
    const USER = 'user';

    const SUCCESS = 0;
    const NOTICE = 1;
    const WARNING = 2;
    const ERROR = 3;

    /** @var string|null The base directory for the application */
    private static $baseDir;

    /** @var string|null The base path for the application url. */
    private static $basePath;

	/** @var boolean True if an error message has been sent. */
	private static $hasError = false;

	/** @var string|null The logon uri for the application. */
	private static $logonUri;

	/** @var string|null The base url for the current request. */
	private static $baseUrl;

	/** @var array|null The command properties for the current request. */
	private static $command;

	/** @var array|null The default command properties */
	private static $commandDefaults;

	/** @var array|null The command map for the current request. */
	private static $commandMap;

	/** @var string|null The host for the current request. */
	private static $host;

	/** @var string|null The name of the host machine. */
	private static $hostname;

	/** @var string|null The unique short id for the application */
	private static $id;

    /** @var string|null The directory for the map files */
    private static $mapDir;

	/** @var array|null An array of messages */
	private static $messages;

	/** @var string|null The HTTP method for this request. */
	private static $method;

    /** @var string|null The directory for the page files */
    private static $pageDir;

    /** @var Parameters|null */
    private static $parameters;

    /** @var bool True is path is parse with kiosk before scope */
    private static $parseKioskFirst = false;

	/** @var string|null The url path after the base path. */
	private static $path;

	/** @var array|null The parameters passed in the path */
	private static $pathParams;

	/** @var int|null The TCP/IP port for this request. */
	private static $port;

	/** @internal string */
	private static $remoteAddr;

	/** @var string|null The scheme used for the request. */
	private static $scheme;

	/** @var string|null The `|` separated list of possible scope values */
	private static $scopeList;

    /** @var string|null The directory for the script files */
    private static $scriptDir;

	/** @var string|null The full name of the base script for this request. */
	private static $scriptName;

    /** @var Session|null The session associated with this request */
    private static $session = null;

	/** @var array|null Response variables for the current request. */
	protected static $state;

	/** @var float The request start time with milliseconds */
	private static $timestamp;

	/** @var boolean Trust proxy headers */
	private static $trust = false;

	private static $url;

	/** @var string|null All the stuff the comes before the path */
	private static $urlBase;

    /**
     * Can be set to an alternative $_REQUEST variable for testing purposes
     * @var array|null
     */
    private static $_request = null;

    /** @var Server|null Object wrapper around the _SERVER array */
    private static $_server;

	/**
	 * Returns an url for the specified action, scope and kiosk.
	 * Options: baseUrl,kiosk,scope,action
	 * @param array $parameters
	 * @return string
	 */
	public static function buildUrl(array $parameters): string {

	    $url='';

	    // set the base url
	    if(isset($parameters['baseUrl'])) {
	        $url .= $parameters['baseUrl'];
	        unset($parameters['baseUrl']);
	    } else {
	        $url .= self::getBaseUrl();
	    }

	    if (self::$parseKioskFirst) {
            if (isset($parameters['kiosk'])) {
                $kiosk = $parameters['kiosk'];
                unset($parameters['kiosk']);
            } else {
                $kiosk = self::getKiosk();
            }
            if (!empty($kiosk)) {
                $url .= '/' . $kiosk;
            }
        }

	    // set the scope
	    if(isset($parameters['scope'])) {
	        $url .= '/'.$parameters['scope'];
	        unset($parameters['scope']);
	    } else {
	        $url .= '/'.self::getScope();
	    }

        if (!self::$parseKioskFirst) {
            if (isset($parameters['kiosk'])) {
                $kiosk = $parameters['kiosk'];
                unset($parameters['kiosk']);
            } else {
                $kiosk = self::getKiosk();
            }
            if (!empty($kiosk)) {
                $url .= '/' . $kiosk;
            }
        }

	    if(isset($parameters['action'])) {
	        $url .= '/'. $parameters['action'].'.do';
	        unset($parameters['action']);
	    } else {
	        $url .= '/'. self::getAction().'.do';
	    }

	    if(count($parameters)) {
	        $url .= '?'.http_build_query($parameters);
	    }

	    return $url;
	}

    /**
     * Compare a crsf token with the token for this session
     * @param string $token
     * @return boolean
     */
    public static function checkToken(string $token): bool {
        return (hash_equals(self::getToken(), $token));
    }

	/**
	 * Get a global value.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get(string $name, $default = null) {
		return (self::$state[$name] ?? $default);
	}

	/**
	 * Return the action requested through the url path parameters.
	 * The 'do' parameter without the '.do'.
	 * @return string|null
	 */
	public static function getAction(): ?string {
	    return self::getPathParams()['action'];
	}

	/**
	 * Returns the value for a named session attribute.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getAttribute(string $name, $default=null) {
        return self::getSession()->getAttribute($name, $default);
	}

	/**
	 * Returns the path to the base directory for the environment.
	 * Defaults to one directory below the current directory, but
	 * can be explicitly set.
	 *
	 * @return string
	 */
	public static function getBaseDir(): string {
        return self::$baseDir ?? (self::$baseDir = dirname(getcwd()));
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
	 public static function getBasePath(): string {
	 	if(!isset(self::$basePath)) {
	 		$script = self::getScriptName();
	 		self::$basePath = substr (
	 				$script,
	 				0,
	 				strrpos($script, '/' ));
	 	}
	 	return self::$basePath;
	 }

	 /**
	  * The base url for the request.
	  * @return string
	  */
	 public static function getBaseUrl(): string {
	 	if(!isset(self::$baseUrl)) {
	 	    self::$baseUrl =
	 			self::getUrlBase().self::getBasePath();
	 	}
	 	return self::$baseUrl;
	 }

    /**
     * Returns the request body as a string
     * @return false|string
     */
    public static function getBody() {
        return file_get_contents('php://input');
    }

    /**
     * Returns the requested command, or command property, based on the path parameters.
     * @param string|null $property optional property name
     * @param mixed $default optional default value for the supplied property name.
     * @return mixed
     */
     public static function getCommand(string $property = null, $default = null) {
         if(!isset(self::$command)) {

             // get the command map
             $map = self::getCommandMap();

             // load the command
             $action = self::getAction();
             if (isset ( $map [$action])) {
                 $command = $map [$action];
             } else if (array_key_exists ( null, $map )) {
                 $command = $map [null];
             } else {
                 return null;
             }

             // set the command
             self::$command = is_object($command)
             ? (array)$command
             : array_merge(self::getCommandDefaults(),$command);
         }

         if(func_num_args() > 0) {
             if(isset(self::$command[$property])) {
                 return self::$command[$property];
             }
             return $default;
         }

         return self::$command;
     }

	/**
	 * Return an array of default values for the command parameters.
	 * @return array
	 */
	public static function getCommandDefaults(): array {
	    return self::$commandDefaults
	       ?? (self::$commandDefaults = array(
	           'session' => true,
	           'authenticate' => true,
	           'authorize' => null,
	           'access_level' => 1,
	           'validate' => null,
	           'post'=> null,
	           'get' => null,
	           'page' => null
	          ));
	}

	/**
	 * Return an associative array of commands for the current request scope.
	 * @param boolean $keep true to keep the loaded command map
	 * @return array
	 */
	public static function getCommandMap(bool $keep = false): array {
	    if(!isset(self::$commandMap)) {
	        $filename = self::getMapDir().'/'. self::getScope().'.php';
	        if (!($map = @include ($filename))) {
	            trigger_error ( "Could not load $filename", E_USER_WARNING );
	            $map = array();
	        }
	        if($keep) {
	            self::$commandMap = $map;
	        }
	    } else {
	        $map = self::$commandMap;
	        if(!$keep) {
	            self::$commandMap = null;
	        }
	    }
		return $map;
	}

	/**
	 * Returns the path to a page file.
	 * @return string|null the page file name
	 */
	public static function getCommandPage(): ?string {
	    $name = static::getCommand('page');
	    if($name) {
	        return self::getPageDir()
	           . '/'
	           . self::getScope()
	           . '/'
	           . $name;
	    }
	    return null;
	}

	/**
	 * Returns the full path to an application script.
	 * @param string $name The name of the command property
	 * @return string|null the script file name
	 */
	public static function getCommandScript(string $name): ?string {
	    $script = static::getCommand($name);
	    if($script) {
	        return static::getScriptDir()
	           .'/'
	           . static::getScope()
	           . '/'
	           . $script;
	    }
	    return null;
	}

	/**
	 * The content type for the request.
	 * This is only set on POST requests.
	 * @return string|null
	 */
	public static function getContentType(): ?string {
		return $_SERVER['CONTENT_TYPE'] ?? null;
	}

	/**
	 * Returns an environment variable.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getEnv(string $name, $default = null) {
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
	               return null;
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
	 * @param string|null $default
	 * @return string|null
	 */
	public static function getHeader(string $name, string $default=null): ?string {
		return self::_server()->getHeader($name, $default);
	}

	/**
	 * Returns the host used for the request.
	 * @return string
	 */
	public static function getHost(): string {
		if(!isset(self::$host)) {
            self::$host = ((self::$trust && ($host = self::getHeader('X_FORWARDED_HOST'))) ? $host : null)
                ?? self::getHeader('HOST')
                ?? self::_server()->get('SERVER_NAME');
	    }
	    return self::$host;
	}

	/**
	 * Returns the name of the host server.
	 */
	public static function getHostName() {
		$server = gethostname();
        if(!empty($server) && ($pos = strpos($server,'.')) > 1) {
            $server = substr($server,0,$pos);
        }
		return $server;
	}

	/**
	 * Returns the short unique id for this environment.
	 *
	 * @return string
	 */
	public static function getId(): string {
        return self::$id ?? (self::$id = base_convert(crc32(self::getBaseDir()), 16, 32));
	}

	/**
	 * The decoded data from json input
	 * @return array|null
	 */
	public static function getJson(): ?array {
	    if(self::isJson()) {
	        $content = json_decode(static::getBody(),1);
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
	 * @return string|null
	 */
	public static function getKiosk(): ?string {
	    return self::getPathParams()['kiosk'];
	}

	/**
	 * Returns the logon url for the environment.
	 * Default is 'logon.do'.
	 *
	 * @return string
	 */
	public static function getLogonUri(): string {
	    return self::$logonUri ?? 'logon.do';
	}

	/**
	 * The path to the map directory for this environment.
	 * Defaults to /maps under the base directory, but can be
	 * explicitly set with MAP_DIR environment variable.
	 *
	 * @return string
	 */
	public static function getMapDir(): string {
        return self::$mapDir ?? (self::$mapDir = self::getBaseDir() . '/maps');
	}

	/**
	 * Return an array of messages that have been sent in this context
	 * @return array
	 */
	public static function getMessages(): array {
	    if(!isset(self::$messages)) {
	        // get any messages stored in the session
	        if(self::$messages = self::getAttribute(self::MESSAGES)) {
	            self::setAttribute(self::MESSAGES, null);
	        } else {
	           self::$messages = array();
	        }
	    }
	    return self::$messages;
	}

	/**
	 * Returns the HTTP method for the request.
	 * @return string
	 */
	public static function getMethod(): ?string {
	    if(!isset(self::$method)) {
	        self::$method = self::_server()->get('REQUEST_METHOD');
	    }
	    return self::$method;
	}

	/**
	 * The path to the page directory for this environment.
	 * Defaults to /pages under the base directory, but can be
	 * explicitly set.
	 *
	 * @return string
	 */
	public static function getPageDir(): string {
        return self::$pageDir
            ?? (self::$pageDir = self::getBaseDir() . '/pages');
	}

	/**
	 * Returns the path to a page file.
	 *
	 * @param string $name The name of the page
	 * @return string the page file name
	 */
	public static function getPagePath(string $name): string {
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
	public static function getParameter(string $name, $default=null) {
        return self::getParameters()->get($name, $default);
	}

    public static function getParameters(): ArrayMap {
        if (!isset(self::$parameters)) {
            if (isset(self::$_request)) {
                self::$parameters = new Parameters(self::$_request);
                self::$_request = null;
            } else {
                self::$parameters = new Parameters($_REQUEST);
            }
            if(self::isJson()) {
                $content = json_decode(static::getBody(),1);
                if($content && is_array($content)) {
                    self::$parameters->addAll($content);
                }
            }
        }
        return self::$parameters;
    }

	/**
	 * Returns the HTTP method for the request.
	 * @return string
	 */
	public static function getPath(): string {
		return self::$path ?? (self::$path = substr(
            parse_url(self::_server()->get('REQUEST_URI'), PHP_URL_PATH),
            strlen(self::getBasePath())));
	}

	/**
	 * Returns the path parameters passed in the request path.
	 * @return string[]
	 */
	public static function getPathParams(): array {
        return self::$pathParams ?? (self::$pathParams = self::parsePath(
            self::getPath(), self::$parseKioskFirst));
	}

	/**
	 * Returns the port used for the request.
	 * @todo Need to safely handle X-Forwarded-Host header
	 * @return int
	 */
	public static function getPort(): int {
	    if(!isset(self::$port)) {
	        self::$port = isset($_SERVER['SERVER_PORT'])
	        ? intval($_SERVER['SERVER_PORT'])
	        : 80;
	    }
	    return self::$port;
	}

	/**
	 * Return the remote ip address used for this request.
	 * @todo Safely handle X-Forwarded-For header.
	 * @return string|null
	 */
	public static function getRemoteAddr(): ?string {
	    if(!isset(self::$remoteAddr)) {
	        self::$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
	    }
	    return self::$remoteAddr;
	}

	/**
	 * Returns the scope of this context based on the url path parameters
	 * ( /kiosk/scope/action.do ).
	 * @return string|null
	 */
	public static function getScope(): ?string {
		return self::getPathParams()['scope'];
	}

	/**
	 * Returns the '|' separated list of valid scope names for this environment.
	 * This value *should be set with the SCOPE_LIST environment variable,
	 * but a list be generated from the file system if necessary.
	 *
	 * @return string
	 */
	public static function getScopeList(): string {
		if (!isset(self::$scopeList)) {
            trigger_error('Context::scopeList should be set!!!',E_USER_WARNING);
            self::$scopeList = implode('|',array_map(function ($s) {
				    return substr(basename($s),0,- 4);
			    },glob(self::getMapDir() . '/*.php')));
		}
		return self::$scopeList;
	}

	/**
	 * Returns the path to the scripts directory for this environment.
	 * Defaults to /scripts under the base directory, but can be
	 * explicitly set with SCRIPT_DIR environment variable.
	 *
	 * @return string
	 */
	public static function getScriptDir(): string {
	    return self::$scriptDir
            ?? (self::$scriptDir = self::getBaseDir() . '/scripts');
	}

	/**
	* Returns the full path to an application script.
	* @param string $name The name of the script
	* @return string the script file name
	*/
    public static function getScriptPath(string $name = null): string {
	    return static::getScriptDir()
	        .'/'
	        . static::getScope()
	        . '/'
	        . $name;
	}

	/**
	 * Returns the request scheme used by the client.
	 * @return string
	 */
	public static function getScheme(): string {
	    if(!isset(self::$scheme)) {
			if(self::$trust && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
				self::$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
			} else if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
				self::$scheme = "https";
			} else if(443 == self::getPort() ||  8443 == self::getPort()) {
				self::$scheme = "https";
			} else	{
				self::$scheme = "http";
			}
		}
		return self::$scheme;
	}

	/**
	 * Returns the pathname of the currently executing script.
	 */
	public static function getScriptName(): string {
        if(!isset(self::$scriptName)) {
            // check for built-in php server
            if (php_sapi_name() == 'cli-server') {
                self::$scriptName = '';
            } else {
                if(isset($_SERVER['PATH_INFO'])
                    && 0 === substr_compare(
					   $_SERVER['PHP_SELF'],
					   $_SERVER['PATH_INFO'],
                       - ($len=strlen($_SERVER['PATH_INFO'])))) {
                    self::$scriptName =  substr($_SERVER['PHP_SELF'],0,-$len);
                } else {
                    self::$scriptName = $_SERVER['PHP_SELF'];
                }
            }
        }
        return self::$scriptName;
	}

    public static function getSession($create = true): ?Session {
        if (!isset(self::$session) && $create) {
            self::$session = new Session();
        }
        return self::$session;
    }

	/**
	 * Returns the current response status code
	 * @return int
	 */
	public static function getStatus(): int {
		return http_response_code();
	}

    /**
     * Returns the CRSF token for this session
     * @return string
     */
    public static function getToken(): string {
        try {
            $token = $_SESSION['crsf'] ?? ($_SESSION['crsf'] = bin2hex(random_bytes(32)));
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            $token = '';
        }
        return $token;
    }

	/**
	  * The url for the request.
	  * @return string
	  */
	  public static function getUrl(): string {
		if(!isset(self::$url)) {
			self::$url = self::getUrlBase();
			if(isset($_SERVER['REQUEST_URI'])) {
				self::$url .= $_SERVER['REQUEST_URI'];
			}
		}
		return self::$url;
	}

	/**
	  * Returns the url base for getBaseUrl and getUrl.
	  * @return string
	  */
	  private static function getUrlBase(): string {
		if(!isset(self::$urlBase)) {
			self::$urlBase =
				self::getScheme()
				. '://'
				. self::getHost()
				. ((self::getPort() == 80 || self::getPort() == 443)
					? ''
					: ':'.self::getPort())
				. self::getBasePath();
		}
		return self::$urlBase;
	}

	/**
	 * Return if the named value exists
	 * @param string $name
	 * @return boolean
	 */
	public static function has(string $name): bool {
		return isset(self::$state[$name]);
	}

	/**
	 * Returns true if an error message is present.
	 * @return boolean
	 */
	public static function hasError(): bool {
		return !empty(self::$hasError);
	}

	/**
	 * Returns true if there are session attributes.
	 * A session will NOT be automatically created.
	 * @return boolean
	 */
	public static function hasAttributes(): bool {
	    return self::getSession()->count() > 0;
	}

	/**
	 * Returns true if there are messages
	 * @return boolean
	 */
	public static function hasMessages(): bool {
	    return (!empty(self::$messages));
	}

	/**
	 * Returns true if there is session
	 * @return boolean
	 */
	public static function hasSession(): bool {
        return self::getSession()->isActive();
	}

	/**
	 * Returns an url for based on the current context.
	 * Parameters for baseUrl, kiosk, scope, action will
	 * override the values from the current context.
	 * Any additional parameters will simply be parameters
	 * on the query string.
	 * @param array $parameters
	 * @return string
	 */
	public static function href(array $parameters): string {
        return self::buildUrl($parameters);
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
	public static function isJson(): bool {
	    if(Str::endsWith(self::getContentType(),'json')) {
	        return true;
	    }
	    return false;
	}

    /**
     * Return true if the request method matches the supplied method
     * @param string $method
     * @return boolean
     */
    public static function isMethod(string $method): bool {
        return 0 === strcmp($method, self::getMethod());
    }

	/**
	 * Returns true if the command is being redirected.
	 * @return boolean
	 */
	public static function isRedirect(): bool {
	    return in_array(self::getStatus(),[301,302,303,307,308]);
	}

	/**
	 * Return true if there is an HTTP request and false if CLI.
	 * @return boolean
	 */
	public static function isRequest(): bool {
	    return http_response_code()!==FALSE;
	}

	/**
	 * Returns true if the response status code is still < 300.
	 * @param int|null $accept additional valid status codes as parameters
	 * @return boolean
	 */
	public static function isStatusOk(int $accept = null): bool {
	    // check for a code less than 300
	    if (($status = self::getStatus()) < 300) {
	        return true;
	    }
        // check for additional status codes to accept
	    if ($accept !== null) {
            // just one additional status code
	        if (func_num_args() == 1) {
	            if ($status === $accept) {
	                return true;
	            }
	        } else if (in_array($status, func_get_args())) {
	            return true;
	        }
	    }
	    return false;
	}

	/**
	 * Returns the application parameters parsed from the supplied path.
	 * A parameters are parsed based on the following pattern:
     *
     * > '<scope>/<kiosk>/<action>/.do
	 *
     * unless $kiosk_scope is true and then the path as parsed as
     *
	 * > `<kiosk>/<scope>/<action>.do`
	 *
	 * **kiosk** represent the unique entry point for a client/account.
	 *
	 * **scope** represents the request scope, or sub-application
	 *
	 * **action** represents the command to be executed
     *
	 * @param string $path The path to parse
	 * @return array
	 */
	public static function parsePath(string $path, $kiosk_scope = false): array {
		// create a path params object
		$params = ['kiosk'=>null,'scope'=>null,'action'=>null];

		// explode the path
		$parts = explode ( '/', trim($path,"/ \t\n\r\0\x0B"));

		$scope = explode('|', self::getScopeList());

		// check for .do parameter
		if (count ( $parts ) > 0
				&& strlen ( ($s = end( $parts )) ) > 3
				&& substr_compare ( $s, '.do', - 3 ) == 0)
		{
			$params['action'] = substr ( array_pop ( $parts ), 0, - 3 );
		}

        // check for scope parameter
        if ($kiosk_scope) {
            if (count($parts) > 0 && in_array((end($parts)), $scope)) {
                $params['scope'] = array_pop($parts);
            }
        } else {
            if (count($parts) > 0 && in_array((reset($parts)), $scope)) {
                $params['scope'] = array_shift($parts);
            }
        }

		// check for kiosk (kiosk cannot be empty)
		if (count ( $parts ) == 1) {
			$params['kiosk'] = trim(array_pop ( $parts )) ?: null;
		}

		// set the default scope
		if(count($parts) == 0) {
			if(isset($params['kiosk']) && !isset($params['scope'])) {
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
	 * Render the specified page for the current scope
	 * @param string $page
	 */
	public static function render(string $page) {
	    if(isset(self::$state)) {
	        extract(self::$state,EXTR_SKIP);
	    }
	    include(self::getPagePath($page));
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
	 * @param string|null $field the name of the input field in error
	 */
	public static function sendMessage(string $message, int $type = 0, string $field = null) {
        if(E_USER_ERROR == $type) {
            self::$hasError = true;
        }
        // initialize messages if necessary
        if(!isset(self::$messages)) {
            self::getMessages();
        }
        self::$messages[] = array($type,$message,$field);
	}

	/**
	 * Send a redirect to the client
	 * @param string $to
	 * @param int|null $status
	 * @return boolean
	 */
	public static function sendRedirect(string $to, int $status = null): bool {

	    // make sure headers are not already sent
	    if (headers_sent()) {
	        return false;
	    }

	    // save messages to the session
	    if(self::hasMessages() && self::hasSession()) {
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
	 * @param string|null $message The message to be sent with the status code.
	 */
	public static function sendError(int $status_code, string $message=null) {
		http_response_code($status_code);
		echo $message;
	}

	/**
	 * Set a global value.
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public static function set(string $name, $value) {
		return (self::$state[$name] = $value);
	}

    /**
     * Set a session attribute.
     * @param string $name
     * @param mixed $value
     * @return void
     */
	public static function setAttribute(string $name, $value) {
        self::getSession()->set($name, $value);
	}

    /**
     * Set the base directory for the application.
     * @param string $dir
     * @return string
     */
	public static function setBaseDir(string $dir): string {
	    if(self::getBaseDir() != $dir) {
	       self::$baseDir = $dir;
	       // reset the map and script directories
	       self::$mapDir = null;
	       self::$pageDir = null;
	       self::$scriptDir = null;
	    }
	    return $dir;
	}

	/**
	 * Set the base url so that it doesn't need to be calculated
	 * @param string $url
	 */
	public static function setBaseUrl(string $url) {
	    if($parts = parse_url($url)) {
	        if(isset($parts['scheme'])) self::$scheme = $parts['scheme'];
	        if(isset($parts['host'])) self::$host = $parts['host'];
	        if(isset($parts['port'])) self::$port = $parts['port'];
	        if(isset($parts['path'])) self::$basePath = $parts['path'];
	    }
	}

	/**
	 * Override the default command defaults
	 * @param array|string|object $defaults
	 */
	public static function setCommandDefaults($defaults) {
	    if(is_string($defaults)) {
	        $defaults = json_decode($defaults,1);
	    } else if(is_object($defaults)) {
	        $defaults = (array)$defaults;
	    }
	    if(is_array($defaults)) {
	        self::$commandDefaults = $defaults;
	    }
	}

    /**
	 * Set the unique short id for the application.
	 * @param string $id
	 */
	public static function setId(string $id) {
	    if(!isset(self::$id)) {
	        self::$id = $id;
	    } else if(self::$id != $id) {
	        trigger_error('The context id cannot be reset',E_USER_WARNING);
	    }
	}

    /**
     * Parse the path with kiosk first followed by scope.
     * This was the original, but not the best, approach.
     * @param bool $parse_kiosk_first
     * @return void
     */
    public static function setKioskFirst(bool $parse_kiosk_first = true) {
        self::$parseKioskFirst = $parse_kiosk_first;
    }

	/**
	 * Set the map directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setMapDir(string $dir): string {
	    return self::$mapDir = $dir;
	}

	/**
	 * Set the page directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setPageDir(string $dir): string {
	    return self::$pageDir = $dir;
	}


    /**
     * Set a request parameter
     * @param string $name
     * @param mixed $value
     * @return void
     */
	public static function setParameter(string $name, $value) {
        self::getParameters()->set($name, $value);
	}

	/**
	 * Set the valid scope names as a '|' separated list of values.
	 * @param string $list
	 * @return string
	 */
	public static function setScopeList(string $list): string {
	    return (self::$scopeList = str_replace(' ','',$list));
	}

	/**
	 * Set the script directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setScriptDir(string $dir): string {
	    return self::$scriptDir =  $dir;
	}

	/**
	 * Set trust proxy to true if we trust the proxy headers
	 * @param boolean $isTrusted
	 */
	public static function setTrustProxy(bool $isTrusted = true) {
	    self::$trust = $isTrusted;
	}

    /**
     * Return the context properties as an array.
     * @return array
     */
    public static function toArray(): array {
        $array = [];
        foreach(self::_names() as $name => $method) {
            if (strpos($name, '_') === false) {
                $array[$name] = self::{$method}();
            }
        }
        return $array;
    }

    /**
     * Returns the internal state of the context as an array
     * @return array
     */
    public static function _dump(): array {
        $dump = [];
        foreach(array_keys(self::_names()) as $name) {
            if (property_exists(self::class, $name)) {
                $dump[$name] = self::$$name;
            }
        }
        return $dump;
    }

    protected static function _names(): array {
        return [
            'action' => 'getAction',
            'baseDir' => 'getBaseDir',
            'basePath' => 'getBasePath',
            'baseUrl' => 'getBaseUrl',
            'command' => 'getCommand',
            'contentType' => 'getContentType',
            'kiosk' => 'getKiosk',
            'hasError' => 'hasError',
            'host' => 'getHost',
            'hostname' => 'getHostname',
            'id' => 'getId',
            'isJson' => 'isJson',
            'isRequest' => 'isRequest',
            'logonUri' => 'getLogonUri',
            'mapDir' => 'getMapDir',
            'method' => 'getMethod',
            'messages' => 'getMessages',
            'pageDir' => 'getPageDir',
            'parameters' => 'getParameters',
            'path' => 'getPath',
            'pathParams' => 'getPathParams',
            'port' => 'getPort',
            'remoteAddr' => 'getRemoteAddr',
            'scope' => 'getScope',
            'scopeList' => 'getScopeList',
            'scriptDir' => 'getScriptDir',
            'scheme' => 'getScheme',
            'scriptName' => 'getScriptName',
            '_request' => null,
            '_server' => '_server'
        ];
    }

    /**
     * Reset all the properties for this context.
     */
    public static function _reset() {
        foreach(array_keys(self::_names()) as $name) {
            if (isset(self::$$name)) {
                self::$$name = null;
            }
        }
    }

    /**
     * Set the underlying _REQUEST array
     *
     * This only needs to be done for testing purposes as $_REQUEST
     * is loaded by default.
     *
     * @param array $_request
     * @return void
     */
    public static function _request(array $_request) {
        self::$_request = $_request;
    }

    public static function _server($_server = null): Server {
        return self::$_server ?? (self::$_server = new Server($_server));
    }
}
