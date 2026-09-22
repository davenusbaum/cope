<?php

namespace Cope;

use Cope\StringHelper as Str;
use Exception;

/**
 * The state and behavior for a single request.
 *
 * A SapiContext is normally created lazily from the PHP
 * superglobals by Context::current(), but can be constructed
 * from plain arrays for testing:
 *
 * ```php
 * $ctx = new SapiContext(
 *     ['REQUEST_URI' => '/web/kiosk/action.do'],   // server
 *     ['id' => '42']                               // request parameters
 * );
 * Context::setCurrent($ctx);
 * ```
 *
 * The static Context class delegates to the current SapiContext,
 * so existing code using the static API is unaffected.
 */
class SapiContext {

	/** @var string|null The base directory for the application */
	private $baseDir;

	/** @var string|null The base path for the application url. */
	private $basePath;

	/** @var boolean True if an error message has been sent. */
	private $hasError = false;

	/** @var string|null The logon uri for the application. */
	private $logonUri;

	/** @var string|null The base url for the current request. */
	private $baseUrl;

	/** @var array|null The command properties for the current request. */
	private $command;

	/** @var array|null The default command properties */
	private $commandDefaults;

	/** @var array|null The command map for the current request. */
	private $commandMap;

	/** @var string|null The host for the current request. */
	private $host;

	/** @var string|null The unique short id for the application */
	private $id;

	/** @var string|null The directory for the map files */
	private $mapDir;

	/** @var array|null An array of messages */
	private $messages;

	/** @var string|null The HTTP method for this request. */
	private $method;

	/** @var string|null The directory for the page files */
	private $pageDir;

	/** @var Parameters|null */
	private $parameters;

	/** @var bool True if path is parsed with kiosk before scope */
	private $parseKioskFirst = false;

	/** @var string|null The url path after the base path. */
	private $path;

	/** @var array|null The parameters passed in the path */
	private $pathParams;

	/** @var int|null The TCP/IP port for this request. */
	private $port;

	/** @var string|null */
	private $remoteAddr;

	/** @var string|null The scheme used for the request. */
	private $scheme;

	/** @var string|null The `|` separated list of possible scope values */
	private $scopeList;

	/** @var string|null The directory for the script files */
	private $scriptDir;

	/** @var string|null The full name of the base script for this request. */
	private $scriptName;

	/** @var Session|null The session associated with this request */
	private $session;

	/** @var array|null Response variables for the current request. */
	private $state;

	/** @var int|null The response status code sent through this context */
	private $status;

	/** @var boolean Trust proxy headers */
	private $trust = false;

	/** @var string|null */
	private $url;

	/** @var string|null All the stuff the comes before the path */
	private $urlBase;

	/** @var array|null An alternative to $_REQUEST, for testing */
	private $request;

	/** @var Server|null Object wrapper around the _SERVER array */
	private $server;

	/**
	 * Create a request context.
	 * With no arguments the context reads lazily from the PHP
	 * superglobals. Supply plain arrays to build a context for
	 * testing.
	 * @param array|null $server replaces $_SERVER
	 * @param array|null $request replaces $_REQUEST
	 * @param Session|null $session replaces the lazily created session
	 */
	public function __construct(?array $server = null, ?array $request = null, ?Session $session = null) {
		if (isset($server)) {
			$this->server = new Server($server);
			// an injected world is sealed: don't read the
			// process-global response code through the fallback
			$this->status = 200;
		}
		$this->request = $request;
		$this->session = $session;
	}

	/**
	 * Create a request context from the PHP superglobals.
	 * @return SapiContext
	 */
	public static function fromGlobals(): SapiContext {
		return new self();
	}

	/**
	 * Returns an url for the specified action, scope and kiosk.
	 * Options: baseUrl,kiosk,scope,action
	 * @param array $parameters
	 * @return string
	 */
	public function buildUrl(array $parameters): string {

		$url='';

		// set the base url
		if(isset($parameters['baseUrl'])) {
			$url .= $parameters['baseUrl'];
			unset($parameters['baseUrl']);
		} else {
			$url .= $this->getBaseUrl();
		}

		if ($this->parseKioskFirst) {
			if (isset($parameters['kiosk'])) {
				$kiosk = $parameters['kiosk'];
				unset($parameters['kiosk']);
			} else {
				$kiosk = $this->getKiosk();
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
			$url .= '/'.$this->getScope();
		}

		if (!$this->parseKioskFirst) {
			if (isset($parameters['kiosk'])) {
				$kiosk = $parameters['kiosk'];
				unset($parameters['kiosk']);
			} else {
				$kiosk = $this->getKiosk();
			}
			if (!empty($kiosk)) {
				$url .= '/' . $kiosk;
			}
		}

		if(isset($parameters['action'])) {
			$url .= '/'. $parameters['action'].'.do';
			unset($parameters['action']);
		} else {
			$url .= '/'. $this->getAction().'.do';
		}

		if(count($parameters)) {
			$url .= '?'.http_build_query($parameters);
		}

		return $url;
	}

	/**
	 * Get a global value.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(string $name, $default = null) {
		return ($this->state[$name] ?? $default);
	}

	/**
	 * Return the action requested through the url path parameters.
	 * The 'do' parameter without the '.do'.
	 * @return string|null
	 */
	public function getAction(): ?string {
		return $this->getPathParams()['action'];
	}

	/**
	 * Returns the value for a named session attribute.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getAttribute(string $name, $default=null) {
		return $this->getSession()->getAttribute($name, $default);
	}

	/**
	 * Returns the path to the base directory for the environment.
	 * Defaults to one directory below the current directory, but
	 * can be explicitly set.
	 *
	 * @return string
	 */
	public function getBaseDir(): string {
		return $this->baseDir ?? ($this->baseDir = dirname(getcwd()));
	}

	/**
	 * Returns the portion of the request path that comes before the application
	 * command parameters.
	 * ```
	 * /<base_path>/<scope>/<kiosk>/<action>.do
	 * ```
	 * This is equivalent to Apache RewriteBase
	 * @return string
	 */
	public function getBasePath(): string {
		if(!isset($this->basePath)) {
			$script = $this->getScriptName();
			$this->basePath = substr (
					$script,
					0,
					strrpos($script, '/' ));
		}
		return $this->basePath;
	}

	/**
	 * The base url for the request.
	 * @return string
	 */
	public function getBaseUrl(): string {
		if(!isset($this->baseUrl)) {
			$this->baseUrl =
				$this->getUrlBase().$this->getBasePath();
		}
		return $this->baseUrl;
	}

	/**
	 * Returns the request body as a string
	 * @return false|string
	 */
	public function getBody() {
		return file_get_contents('php://input');
	}

	/**
	 * Returns the requested command, or command property, based on the path parameters.
	 * @param string|null $property optional property name
	 * @param mixed $default optional default value for the supplied property name.
	 * @return mixed
	 */
	public function getCommand(?string $property = null, $default = null) {
		if(!isset($this->command)) {

			// get the command map
			$map = $this->getCommandMap();

			// load the command
			$action = $this->getAction();
			if (isset ( $map [$action])) {
				$command = $map [$action];
			} else if (array_key_exists ( null, $map )) {
				$command = $map [null];
			} else {
				return null;
			}

			// set the command
			$this->command = is_object($command)
			? (array)$command
			: array_merge($this->getCommandDefaults(),$command);
		}

		if(func_num_args() > 0) {
			if(isset($this->command[$property])) {
				return $this->command[$property];
			}
			return $default;
		}

		return $this->command;
	}

	/**
	 * Return an array of default values for the command parameters.
	 * @return array
	 */
	public function getCommandDefaults(): array {
		return $this->commandDefaults
			?? ($this->commandDefaults = array(
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
	public function getCommandMap(bool $keep = false): array {
		if(!isset($this->commandMap)) {
			$filename = $this->getMapDir().'/'. $this->getScope().'.php';
			// a missing file includes as false; a map with no commands is an
			// empty array, which is a valid map and not an error
			$map = @include ($filename);
			if (!is_array($map)) {
				trigger_error ( "Could not load $filename", E_USER_WARNING );
				$map = array();
			}
			if($keep) {
				$this->commandMap = $map;
			}
		} else {
			$map = $this->commandMap;
			if(!$keep) {
				$this->commandMap = null;
			}
		}
		return $map;
	}

	/**
	 * Returns the path to a page file.
	 * @return string|null the page file name
	 */
	public function getCommandPage(): ?string {
		$name = $this->getCommand('page');
		if($name) {
			return $this->getPageDir()
				. '/'
				. $this->getScope()
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
	public function getCommandScript(string $name): ?string {
		$script = $this->getCommand($name);
		if($script) {
			return $this->getScriptDir()
				.'/'
				. $this->getScope()
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
	public function getContentType(): ?string {
		return $this->server()->get('CONTENT_TYPE');
	}

	/**
	 * Returns an environment variable.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getEnv(string $name, $default = null) {
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
	public function getHeader(string $name, ?string $default=null): ?string {
		return $this->server()->getHeader($name, $default);
	}

	/**
	 * Returns the host used for the request.
	 * @return string
	 */
	public function getHost(): string {
		if(!isset($this->host)) {
			$host = (($this->trust && ($host = $this->getHeader('X_FORWARDED_HOST'))) ? $host : null)
				?? $this->getHeader('HOST')
				?? $this->server()->get('SERVER_NAME');
			// the Host header carries ":port" whenever the port is not the
			// default; the port is getPort()'s job, so keep the name only
			$this->host = preg_replace('/:\d+$/', '', $host);
		}
		return $this->host;
	}

	/**
	 * Returns the name of the host server.
	 */
	public function getHostName() {
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
	public function getId(): string {
		return $this->id ?? ($this->id = base_convert(crc32($this->getBaseDir()), 16, 32));
	}

	/**
	 * The decoded data from json input
	 * @return array|null
	 */
	public function getJson(): ?array {
		if($this->isJson()) {
			$content = json_decode($this->getBody(),1);
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
	public function getKiosk(): ?string {
		return $this->getPathParams()['kiosk'];
	}

	/**
	 * Returns the logon url for the environment.
	 * Default is 'logon.do'.
	 *
	 * @return string
	 */
	public function getLogonUri(): string {
		return $this->logonUri ?? 'logon.do';
	}

	/**
	 * The path to the map directory for this environment.
	 * Defaults to /maps under the base directory, but can be
	 * explicitly set.
	 *
	 * @return string
	 */
	public function getMapDir(): string {
		return $this->mapDir ?? ($this->mapDir = $this->getBaseDir() . '/maps');
	}

	/**
	 * Return an array of messages that have been sent in this context
	 * @return array
	 */
	public function getMessages(): array {
		if(!isset($this->messages)) {
			// get any messages stored in the session
			if($this->messages = $this->getAttribute(Context::MESSAGES)) {
				$this->setAttribute(Context::MESSAGES, null);
			} else {
				$this->messages = array();
			}
		}
		return $this->messages;
	}

	/**
	 * Returns the HTTP method for the request.
	 * @return string
	 */
	public function getMethod(): ?string {
		if(!isset($this->method)) {
			$this->method = $this->server()->get('REQUEST_METHOD');
		}
		return $this->method;
	}

	/**
	 * The path to the page directory for this environment.
	 * Defaults to /pages under the base directory, but can be
	 * explicitly set.
	 *
	 * @return string
	 */
	public function getPageDir(): string {
		return $this->pageDir
			?? ($this->pageDir = $this->getBaseDir() . '/pages');
	}

	/**
	 * Returns the path to a page file.
	 *
	 * @param string $name The name of the page
	 * @return string the page file name
	 */
	public function getPagePath(string $name): string {
		return $this->getPageDir()
			. '/'
			. $this->getScope()
			. '/'
			. $name;
	}

	/**
	 * Returns a named input parameter
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getParameter(string $name, $default=null) {
		return $this->getParameters()->get($name, $default);
	}

	public function getParameters(): ArrayMap {
		if (!isset($this->parameters)) {
			if (isset($this->request)) {
				$this->parameters = new Parameters($this->request);
				$this->request = null;
			} else {
				$this->parameters = new Parameters($_REQUEST);
			}
			if($this->isJson()) {
				$content = json_decode($this->getBody(),1);
				if($content && is_array($content)) {
					$this->parameters->addAll($content);
				}
			}
		}
		return $this->parameters;
	}

	/**
	 * Returns the request path after the base path.
	 * @return string
	 */
	public function getPath(): string {
		return $this->path ?? ($this->path = substr(
			parse_url($this->server()->get('REQUEST_URI'), PHP_URL_PATH),
			strlen($this->getBasePath())));
	}

	/**
	 * Returns the path parameters passed in the request path.
	 * @return string[]
	 */
	public function getPathParams(): array {
		return $this->pathParams ?? ($this->pathParams = $this->parsePath(
			$this->getPath(), $this->parseKioskFirst));
	}

	/**
	 * Returns the port used for the request.
	 * @return int
	 */
	public function getPort(): int {
		if(!isset($this->port)) {
			$this->port = intval($this->server()->get('SERVER_PORT', 80));
		}
		return $this->port;
	}

	/**
	 * Return the remote ip address used for this request.
	 * @return string|null
	 */
	public function getRemoteAddr(): ?string {
		if(!isset($this->remoteAddr)) {
			$this->remoteAddr = $this->server()->get('REMOTE_ADDR');
		}
		return $this->remoteAddr;
	}

	/**
	 * Returns the scope of this context based on the url path parameters
	 * ( /scope/kiosk/action.do ).
	 * @return string|null
	 */
	public function getScope(): ?string {
		return $this->getPathParams()['scope'];
	}

	/**
	 * Returns the '|' separated list of valid scope names for this environment.
	 * This value should be set explicitly, but a list can be generated
	 * from the file system if necessary.
	 *
	 * @return string
	 */
	public function getScopeList(): string {
		if (!isset($this->scopeList)) {
			trigger_error('Context::scopeList should be set!!!',E_USER_WARNING);
			$this->scopeList = implode('|',array_map(function ($s) {
					return substr(basename($s),0,- 4);
				},glob($this->getMapDir() . '/*.php')));
		}
		return $this->scopeList;
	}

	/**
	 * Returns the path to the scripts directory for this environment.
	 * Defaults to /scripts under the base directory, but can be
	 * explicitly set.
	 *
	 * @return string
	 */
	public function getScriptDir(): string {
		return $this->scriptDir
			?? ($this->scriptDir = $this->getBaseDir() . '/scripts');
	}

	/**
	 * Returns the full path to an application script.
	 * @param string $name The name of the script
	 * @return string the script file name
	 */
	public function getScriptPath(?string $name = null): string {
		return $this->getScriptDir()
			.'/'
			. $this->getScope()
			. '/'
			. $name;
	}

	/**
	 * Returns the request scheme used by the client.
	 * @return string
	 */
	public function getScheme(): string {
		if(!isset($this->scheme)) {
			$https = $this->server()->get('HTTPS');
			if($this->trust && ($proto = $this->server()->get('HTTP_X_FORWARDED_PROTO'))) {
				$this->scheme = $proto;
			} else if(isset($https) && $https !== 'off') {
				$this->scheme = "https";
			} else if(443 == $this->getPort() || 8443 == $this->getPort()) {
				$this->scheme = "https";
			} else {
				$this->scheme = "http";
			}
		}
		return $this->scheme;
	}

	/**
	 * Returns the pathname of the currently executing script.
	 */
	public function getScriptName(): string {
		if(!isset($this->scriptName)) {
			// check for built-in php server
			if (php_sapi_name() == 'cli-server') {
				$this->scriptName = '';
			} else {
				$pathInfo = $this->server()->get('PATH_INFO');
				$phpSelf = $this->server()->get('PHP_SELF', '');
				if(isset($pathInfo)
					&& 0 === substr_compare(
						$phpSelf,
						$pathInfo,
						- ($len=strlen($pathInfo)))) {
					$this->scriptName = substr($phpSelf,0,-$len);
				} else {
					$this->scriptName = $phpSelf;
				}
			}
		}
		return $this->scriptName;
	}

	public function getSession($create = true): ?Session {
		if (!isset($this->session) && $create) {
			$this->session = new Session();
		}
		return $this->session;
	}

	/**
	 * Returns the current response status code.
	 * Tracked on the context so that status-driven flow works
	 * in any SAPI, including CLI where http_response_code()
	 * is unavailable until set.
	 * @return int
	 */
	public function getStatus(): int {
		if (isset($this->status)) {
			return $this->status;
		}
		$code = http_response_code();
		return $code === false ? 200 : $code;
	}

	/**
	 * Set the response status code.
	 * @param int $status
	 * @return int
	 */
	public function setStatus(int $status): int {
		http_response_code($status);
		return ($this->status = $status);
	}

	/**
	 * The url for the request.
	 * @return string
	 */
	public function getUrl(): string {
		if(!isset($this->url)) {
			$this->url = $this->getUrlBase();
			if(null !== ($uri = $this->server()->get('REQUEST_URI'))) {
				$this->url .= $uri;
			}
		}
		return $this->url;
	}

	/**
	 * Returns the url base for getBaseUrl and getUrl.
	 * @return string
	 */
	private function getUrlBase(): string {
		if(!isset($this->urlBase)) {
			$this->urlBase =
				$this->getScheme()
				. '://'
				. $this->getHost()
				. (($this->getPort() == 80 || $this->getPort() == 443)
					? ''
					: ':'.$this->getPort())
				. $this->getBasePath();
		}
		return $this->urlBase;
	}

	/**
	 * Return if the named value exists
	 * @param string $name
	 * @return boolean
	 */
	public function has(string $name): bool {
		return isset($this->state[$name]);
	}

	/**
	 * Return all state values for the current request.
	 * Used by page rendering to expose state as template variables.
	 * @return array
	 */
	public function state(): array {
		return $this->state ?? [];
	}

	/**
	 * Returns true if an error message is present.
	 * @return boolean
	 */
	public function hasError(): bool {
		return !empty($this->hasError);
	}

	/**
	 * Returns true if there are session attributes.
	 * @return boolean
	 */
	public function hasAttributes(): bool {
		return $this->getSession()->count() > 0;
	}

	/**
	 * Returns true if there are messages
	 * @return boolean
	 */
	public function hasMessages(): bool {
		return (!empty($this->messages));
	}

	/**
	 * Returns true if there is session
	 * @return boolean
	 */
	public function hasSession(): bool {
		return $this->getSession()->isActive();
	}

	/**
	 * Returns an url based on the current context.
	 * @param array $parameters
	 * @return string
	 */
	public function href(array $parameters): string {
		return $this->buildUrl($parameters);
	}

	/**
	 * Import json data into the context parameters
	 */
	public function importJson() {
		if($this->isJson()) {
			foreach ($this->getJson() as $name => $value) {
				$_REQUEST[$name] = $value;
			}
		}
	}

	/**
	 * Return true if the content type is application/json
	 * @return boolean
	 */
	public function isJson(): bool {
		if(Str::endsWith($this->getContentType(),'json')) {
			return true;
		}
		return false;
	}

	/**
	 * Return true if the request method matches the supplied method
	 * @param string $method
	 * @return boolean
	 */
	public function isMethod(string $method): bool {
		return 0 === strcmp($method, $this->getMethod());
	}

	/**
	 * Returns true if the command is being redirected.
	 * @return boolean
	 */
	public function isRedirect(): bool {
		return in_array($this->getStatus(),[301,302,303,307,308]);
	}

	/**
	 * Return true if there is an HTTP request and false if CLI.
	 * @return boolean
	 */
	public function isRequest(): bool {
		return http_response_code()!==FALSE;
	}

	/**
	 * Returns true if the response status code is still < 300.
	 * @param int ...$accept additional valid status codes
	 * @return boolean
	 */
	public function isStatusOk(int ...$accept): bool {
		if (($status = $this->getStatus()) < 300) {
			return true;
		}
		return in_array($status, $accept);
	}

	/**
	 * Returns the application parameters parsed from the supplied path.
	 * Parameters are parsed based on the following pattern:
	 *
	 * > `<scope>/<kiosk>/<action>.do`
	 *
	 * unless $kiosk_scope is true and then the path is parsed as
	 *
	 * > `<kiosk>/<scope>/<action>.do`
	 *
	 * @param string $path The path to parse
	 * @param bool $kiosk_scope
	 * @return array
	 */
	public function parsePath(string $path, $kiosk_scope = false): array {
		// create a path params object
		$params = ['kiosk'=>null,'scope'=>null,'action'=>null];

		// explode the path
		$parts = explode ( '/', trim($path,"/ \t\n\r\0\x0B"));

		$scope = explode('|', $this->getScopeList());

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
	public function render(string $page) {
		if(isset($this->state)) {
			extract($this->state,EXTR_SKIP);
		}
		include($this->getPagePath($page));
	}

	/**
	 * Send the body as json.
	 * The content-type is set to application/json
	 * @param mixed $json
	 */
	public function sendJson($json) {
		header('Content-Type: application/json');
		echo json_encode($json);
	}

	/**
	 * Send a message to be displayed on the user page.
	 * @param string $message the message to send.
	 * @param int $type the message type
	 * @param string|null $field the name of the input field in error
	 */
	public function sendMessage(string $message, int $type = 0, ?string $field = null) {
		if(E_USER_ERROR == $type) {
			$this->hasError = true;
		}
		// initialize messages if necessary
		if(!isset($this->messages)) {
			$this->getMessages();
		}
		$this->messages[] = array($type,$message,$field);
	}

	/**
	 * Send a redirect to the client
	 * @param string $to
	 * @param int|null $status
	 * @return boolean
	 */
	public function sendRedirect(string $to, ?int $status = null): bool {

		// make sure headers are not already sent
		if (headers_sent()) {
			return false;
		}

		// save messages to the session
		if($this->hasMessages() && $this->hasSession()) {
			$this->setAttribute(Context::MESSAGES, $this->getMessages());
		}

		// check for full redirect URL
		if(FALSE === strpos($to, '://')) {
			//build our own local redirect
			if(substr_compare($to, '/',0,1) !== 0 ) {
				$to = '/'.$to;
			}
			$to = $this->getBaseUrl().$to;
		}

		// status depends on http protocol
		if(!$status) {
			$status = strpos($this->server()->get('SERVER_PROTOCOL',''),'1.1') ? 303 : 302;
		}
		// set redirect headers
		$this->setStatus($status);
		header("Location: $to");
		return true;
	}

	/**
	 * Send the status to the client.
	 * @param int $status_code
	 * @param string|null $message The message to be sent with the status code.
	 */
	public function sendError(int $status_code, ?string $message=null) {
		$this->setStatus($status_code);
		echo $message;
	}

	/**
	 * Send a 403 to the client.
	 * @param string|null $message The message to be sent with the status code.
	 */
	public function sendNotAuthorized(?string $message = null) {
		$this->sendError(403, $message ?? 'Not authorized');
	}

	/**
	 * Set a global value.
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function set(string $name, $value) {
		return ($this->state[$name] = $value);
	}

	/**
	 * Set a session attribute.
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setAttribute(string $name, $value) {
		$this->getSession()->set($name, $value);
	}

	/**
	 * Set the base directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public function setBaseDir(string $dir): string {
		if($this->getBaseDir() != $dir) {
			$this->baseDir = $dir;
			// reset the map and script directories
			$this->mapDir = null;
			$this->pageDir = null;
			$this->scriptDir = null;
		}
		return $dir;
	}

	/**
	 * Set the base url so that it doesn't need to be calculated
	 * @param string $url
	 */
	public function setBaseUrl(string $url) {
		if($parts = parse_url($url)) {
			if(isset($parts['scheme'])) $this->scheme = $parts['scheme'];
			if(isset($parts['host'])) $this->host = $parts['host'];
			if(isset($parts['port'])) $this->port = $parts['port'];
			if(isset($parts['path'])) $this->basePath = $parts['path'];
		}
	}

	/**
	 * Override the default command defaults
	 * @param array|string|object $defaults
	 */
	public function setCommandDefaults($defaults) {
		if(is_string($defaults)) {
			$defaults = json_decode($defaults,1);
		} else if(is_object($defaults)) {
			$defaults = (array)$defaults;
		}
		if(is_array($defaults)) {
			$this->commandDefaults = $defaults;
		}
	}

	/**
	 * Set the unique short id for the application.
	 * @param string $id
	 */
	public function setId(string $id) {
		if(!isset($this->id)) {
			$this->id = $id;
		} else if($this->id != $id) {
			trigger_error('The context id cannot be reset',E_USER_WARNING);
		}
	}

	/**
	 * Parse the path with kiosk first followed by scope.
	 * This was the original, but not the best, approach.
	 * @param bool $parse_kiosk_first
	 * @return void
	 */
	public function setKioskFirst(bool $parse_kiosk_first = true) {
		$this->parseKioskFirst = $parse_kiosk_first;
	}

	/**
	 * Set the map directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public function setMapDir(string $dir): string {
		return $this->mapDir = $dir;
	}

	/**
	 * Set the page directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public function setPageDir(string $dir): string {
		return $this->pageDir = $dir;
	}

	/**
	 * Set a request parameter
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setParameter(string $name, $value) {
		$this->getParameters()->set($name, $value);
	}

	/**
	 * Set the valid scope names as a '|' separated list of values.
	 * @param string $list
	 * @return string
	 */
	public function setScopeList(string $list): string {
		return ($this->scopeList = str_replace(' ','',$list));
	}

	/**
	 * Set the script directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public function setScriptDir(string $dir): string {
		return $this->scriptDir = $dir;
	}

	/**
	 * Set trust proxy to true if we trust the proxy headers
	 * @param boolean $isTrusted
	 */
	public function setTrustProxy(bool $isTrusted = true) {
		$this->trust = $isTrusted;
	}

	/**
	 * Return the context properties as an array.
	 * @return array
	 */
	public function toArray(): array {
		$array = [];
		foreach($this->_names() as $name => $method) {
			if (strpos($name, '_') === false) {
				$array[$name] = $this->{$method}();
			}
		}
		return $array;
	}

	/**
	 * Returns the internal state of the context as an array
	 * @return array
	 */
	public function _dump(): array {
		return get_object_vars($this);
	}

	protected function _names(): array {
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
			'scriptName' => 'getScriptName'
		];
	}

	/**
	 * Set the underlying request array before parameters are built.
	 * This only needs to be done for testing purposes as $_REQUEST
	 * is loaded by default.
	 * @param array $request
	 * @return void
	 */
	public function _request(array $request) {
		$this->request = $request;
	}

	/**
	 * Returns the Server wrapper, optionally seeding it on first use.
	 * @param array|null $server
	 * @return Server
	 */
	public function server(?array $server = null): Server {
		return $this->server ?? ($this->server = new Server($server));
	}
}
