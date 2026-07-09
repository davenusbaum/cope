<?php

namespace Cope;

/**
 * The cope Context supports a simple command driven MVC framework and
 * provides helper functions over PHP's $_ENV, $_SERVER and $_REQUEST
 * super global arrays.
 *
 * Context is a static facade over a single SapiContext instance.
 * In production the instance is created lazily from the PHP
 * superglobals on first use — one request, one context. For testing,
 * construct a SapiContext from plain arrays and install it with
 * setCurrent(); every static call then reads the test context.
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

	/** @var SapiContext|null The context for the current request */
	private static $current;

	/**
	 * Returns the context for the current request, creating it
	 * from the PHP superglobals if necessary.
	 * @return SapiContext
	 */
	public static function current(): SapiContext {
		return self::$current
			?? (self::$current = SapiContext::fromGlobals());
	}

	/**
	 * Install a context for the current request and return it,
	 * so a test can install and hold in one line:
	 *
	 * ```php
	 * $ctx = Context::setCurrent(new SapiContext($server, $request));
	 * ```
	 *
	 * Pass null to discard the current context; the next static
	 * call will create a fresh one from the superglobals.
	 * @param SapiContext|null $context
	 * @return SapiContext|null the installed context
	 */
	public static function setCurrent(SapiContext $context = null) {
		return (self::$current = $context);
	}

	/**
	 * Returns an url for the specified action, scope and kiosk.
	 * Options: baseUrl,kiosk,scope,action
	 * @param array $parameters
	 * @return string
	 */
	public static function buildUrl(array $parameters): string {
		return self::current()->buildUrl($parameters);
	}

	/**
	 * Compare a crsf token with the token for this session
	 * @param string $token
	 * @return boolean
	 */
	public static function checkToken(string $token): bool {
		return self::current()->checkToken($token);
	}

	/**
	 * Get a global value.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get(string $name, $default = null) {
		return self::current()->get($name, $default);
	}

	/**
	 * Return the action requested through the url path parameters.
	 * @return string|null
	 */
	public static function getAction(): ?string {
		return self::current()->getAction();
	}

	/**
	 * Returns the value for a named session attribute.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getAttribute(string $name, $default=null) {
		return self::current()->getAttribute($name, $default);
	}

	/**
	 * Returns the path to the base directory for the environment.
	 * @return string
	 */
	public static function getBaseDir(): string {
		return self::current()->getBaseDir();
	}

	/**
	 * Returns the portion of the request path that comes before the
	 * application command parameters.
	 * @return string
	 */
	public static function getBasePath(): string {
		return self::current()->getBasePath();
	}

	/**
	 * The base url for the request.
	 * @return string
	 */
	public static function getBaseUrl(): string {
		return self::current()->getBaseUrl();
	}

	/**
	 * Returns the request body as a string
	 * @return false|string
	 */
	public static function getBody() {
		return self::current()->getBody();
	}

	/**
	 * Returns the requested command, or command property, based on the path parameters.
	 * @param string|null $property optional property name
	 * @param mixed $default optional default value for the supplied property name.
	 * @return mixed
	 */
	public static function getCommand(string $property = null, $default = null) {
		if (func_num_args() > 0) {
			return self::current()->getCommand($property, $default);
		}
		return self::current()->getCommand();
	}

	/**
	 * Return an array of default values for the command parameters.
	 * @return array
	 */
	public static function getCommandDefaults(): array {
		return self::current()->getCommandDefaults();
	}

	/**
	 * Return an associative array of commands for the current request scope.
	 * @param boolean $keep true to keep the loaded command map
	 * @return array
	 */
	public static function getCommandMap(bool $keep = false): array {
		return self::current()->getCommandMap($keep);
	}

	/**
	 * Returns the path to a page file.
	 * @return string|null the page file name
	 */
	public static function getCommandPage(): ?string {
		return self::current()->getCommandPage();
	}

	/**
	 * Returns the full path to an application script.
	 * @param string $name The name of the command property
	 * @return string|null the script file name
	 */
	public static function getCommandScript(string $name): ?string {
		return self::current()->getCommandScript($name);
	}

	/**
	 * The content type for the request.
	 * @return string|null
	 */
	public static function getContentType(): ?string {
		return self::current()->getContentType();
	}

	/**
	 * Returns an environment variable.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getEnv(string $name, $default = null) {
		return self::current()->getEnv($name, $default);
	}

	/**
	 * Return the named request header.
	 * @param string $name
	 * @param string|null $default
	 * @return string|null
	 */
	public static function getHeader(string $name, string $default=null): ?string {
		return self::current()->getHeader($name, $default);
	}

	/**
	 * Returns the host used for the request.
	 * @return string
	 */
	public static function getHost(): string {
		return self::current()->getHost();
	}

	/**
	 * Returns the name of the host server.
	 */
	public static function getHostName() {
		return self::current()->getHostName();
	}

	/**
	 * Returns the short unique id for this environment.
	 * @return string
	 */
	public static function getId(): string {
		return self::current()->getId();
	}

	/**
	 * The decoded data from json input
	 * @return array|null
	 */
	public static function getJson(): ?array {
		return self::current()->getJson();
	}

	/**
	 * Returns the kiosk name passed through the url path parameters.
	 * @return string|null
	 */
	public static function getKiosk(): ?string {
		return self::current()->getKiosk();
	}

	/**
	 * Returns the logon url for the environment.
	 * @return string
	 */
	public static function getLogonUri(): string {
		return self::current()->getLogonUri();
	}

	/**
	 * The path to the map directory for this environment.
	 * @return string
	 */
	public static function getMapDir(): string {
		return self::current()->getMapDir();
	}

	/**
	 * Return an array of messages that have been sent in this context
	 * @return array
	 */
	public static function getMessages(): array {
		return self::current()->getMessages();
	}

	/**
	 * Returns the HTTP method for the request.
	 * @return string
	 */
	public static function getMethod(): ?string {
		return self::current()->getMethod();
	}

	/**
	 * The path to the page directory for this environment.
	 * @return string
	 */
	public static function getPageDir(): string {
		return self::current()->getPageDir();
	}

	/**
	 * Returns the path to a page file.
	 * @param string $name The name of the page
	 * @return string the page file name
	 */
	public static function getPagePath(string $name): string {
		return self::current()->getPagePath($name);
	}

	/**
	 * Returns a named input parameter
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getParameter(string $name, $default=null) {
		return self::current()->getParameter($name, $default);
	}

	public static function getParameters(): ArrayMap {
		return self::current()->getParameters();
	}

	/**
	 * Returns the request path after the base path.
	 * @return string
	 */
	public static function getPath(): string {
		return self::current()->getPath();
	}

	/**
	 * Returns the path parameters passed in the request path.
	 * @return string[]
	 */
	public static function getPathParams(): array {
		return self::current()->getPathParams();
	}

	/**
	 * Returns the port used for the request.
	 * @return int
	 */
	public static function getPort(): int {
		return self::current()->getPort();
	}

	/**
	 * Return the remote ip address used for this request.
	 * @return string|null
	 */
	public static function getRemoteAddr(): ?string {
		return self::current()->getRemoteAddr();
	}

	/**
	 * Returns the scope of this context based on the url path parameters.
	 * @return string|null
	 */
	public static function getScope(): ?string {
		return self::current()->getScope();
	}

	/**
	 * Returns the '|' separated list of valid scope names for this environment.
	 * @return string
	 */
	public static function getScopeList(): string {
		return self::current()->getScopeList();
	}

	/**
	 * Returns the path to the scripts directory for this environment.
	 * @return string
	 */
	public static function getScriptDir(): string {
		return self::current()->getScriptDir();
	}

	/**
	 * Returns the full path to an application script.
	 * @param string $name The name of the script
	 * @return string the script file name
	 */
	public static function getScriptPath(string $name = null): string {
		return self::current()->getScriptPath($name);
	}

	/**
	 * Returns the request scheme used by the client.
	 * @return string
	 */
	public static function getScheme(): string {
		return self::current()->getScheme();
	}

	/**
	 * Returns the pathname of the currently executing script.
	 */
	public static function getScriptName(): string {
		return self::current()->getScriptName();
	}

	public static function getSession($create = true): ?Session {
		return self::current()->getSession($create);
	}

	/**
	 * Returns the current response status code
	 * @return int
	 */
	public static function getStatus(): int {
		return self::current()->getStatus();
	}

	/**
	 * Returns the CRSF token for this session
	 * @return string
	 */
	public static function getToken(): string {
		return self::current()->getToken();
	}

	/**
	 * The url for the request.
	 * @return string
	 */
	public static function getUrl(): string {
		return self::current()->getUrl();
	}

	/**
	 * Return if the named value exists
	 * @param string $name
	 * @return boolean
	 */
	public static function has(string $name): bool {
		return self::current()->has($name);
	}

	/**
	 * Returns true if an error message is present.
	 * @return boolean
	 */
	public static function hasError(): bool {
		return self::current()->hasError();
	}

	/**
	 * Returns true if there are session attributes.
	 * @return boolean
	 */
	public static function hasAttributes(): bool {
		return self::current()->hasAttributes();
	}

	/**
	 * Returns true if there are messages
	 * @return boolean
	 */
	public static function hasMessages(): bool {
		return self::current()->hasMessages();
	}

	/**
	 * Returns true if there is session
	 * @return boolean
	 */
	public static function hasSession(): bool {
		return self::current()->hasSession();
	}

	/**
	 * Returns an url for based on the current context.
	 * Parameters for baseUrl, kiosk, scope, action will
	 * override the values from the current context.
	 * @param array $parameters
	 * @return string
	 */
	public static function href(array $parameters): string {
		return self::current()->href($parameters);
	}

	/**
	 * Import json data into the context parameters
	 */
	public static function importJson() {
		self::current()->importJson();
	}

	/**
	 * Return true if the content type is application/json
	 * @return boolean
	 */
	public static function isJson(): bool {
		return self::current()->isJson();
	}

	/**
	 * Return true if the request method matches the supplied method
	 * @param string $method
	 * @return boolean
	 */
	public static function isMethod(string $method): bool {
		return self::current()->isMethod($method);
	}

	/**
	 * Returns true if the command is being redirected.
	 * @return boolean
	 */
	public static function isRedirect(): bool {
		return self::current()->isRedirect();
	}

	/**
	 * Return true if there is an HTTP request and false if CLI.
	 * @return boolean
	 */
	public static function isRequest(): bool {
		return self::current()->isRequest();
	}

	/**
	 * Returns true if the response status code is still < 300.
	 * @param int|null $accept additional valid status codes as parameters
	 * @return boolean
	 */
	public static function isStatusOk(int $accept = null): bool {
		return self::current()->isStatusOk(...array_filter(
			func_get_args(),
			function ($arg) { return $arg !== null; }
		));
	}

	/**
	 * Returns the application parameters parsed from the supplied path.
	 * @param string $path The path to parse
	 * @param bool $kiosk_scope true to parse kiosk before scope
	 * @return array
	 */
	public static function parsePath(string $path, $kiosk_scope = false): array {
		return self::current()->parsePath($path, $kiosk_scope);
	}

	/**
	 * Render the specified page for the current scope
	 * @param string $page
	 */
	public static function render(string $page) {
		self::current()->render($page);
	}

	/**
	 * Send the body as json.
	 * @param mixed $json
	 */
	public static function sendJson($json) {
		self::current()->sendJson($json);
	}

	/**
	 * Send a message to be displayed on the user page.
	 * @param string $message the message to send.
	 * @param int $type the message type
	 * @param string|null $field the name of the input field in error
	 */
	public static function sendMessage(string $message, int $type = 0, string $field = null) {
		self::current()->sendMessage($message, $type, $field);
	}

	/**
	 * Send a redirect to the client
	 * @param string $to
	 * @param int|null $status
	 * @return boolean
	 */
	public static function sendRedirect(string $to, int $status = null): bool {
		return self::current()->sendRedirect($to, $status);
	}

	/**
	 * Send the status to the client.
	 * @param int $status_code
	 * @param string|null $message The message to be sent with the status code.
	 */
	public static function sendError(int $status_code, string $message=null) {
		self::current()->sendError($status_code, $message);
	}

	/**
	 * Send a 403 to the client.
	 * @param string|null $message The message to be sent with the status code.
	 */
	public static function sendNotAuthorized(string $message = null) {
		self::current()->sendNotAuthorized($message);
	}

	/**
	 * Set the response status code.
	 * @param int $status
	 * @return int
	 */
	public static function setStatus(int $status): int {
		return self::current()->setStatus($status);
	}

	/**
	 * Set a global value.
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public static function set(string $name, $value) {
		return self::current()->set($name, $value);
	}

	/**
	 * Set a session attribute.
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public static function setAttribute(string $name, $value) {
		self::current()->setAttribute($name, $value);
	}

	/**
	 * Set the base directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setBaseDir(string $dir): string {
		return self::current()->setBaseDir($dir);
	}

	/**
	 * Set the base url so that it doesn't need to be calculated
	 * @param string $url
	 */
	public static function setBaseUrl(string $url) {
		self::current()->setBaseUrl($url);
	}

	/**
	 * Override the default command defaults
	 * @param array|string|object $defaults
	 */
	public static function setCommandDefaults($defaults) {
		self::current()->setCommandDefaults($defaults);
	}

	/**
	 * Set the unique short id for the application.
	 * @param string $id
	 */
	public static function setId(string $id) {
		self::current()->setId($id);
	}

	/**
	 * Parse the path with kiosk first followed by scope.
	 * This was the original, but not the best, approach.
	 * @param bool $parse_kiosk_first
	 * @return void
	 */
	public static function setKioskFirst(bool $parse_kiosk_first = true) {
		self::current()->setKioskFirst($parse_kiosk_first);
	}

	/**
	 * Set the map directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setMapDir(string $dir): string {
		return self::current()->setMapDir($dir);
	}

	/**
	 * Set the page directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setPageDir(string $dir): string {
		return self::current()->setPageDir($dir);
	}

	/**
	 * Set a request parameter
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public static function setParameter(string $name, $value) {
		self::current()->setParameter($name, $value);
	}

	/**
	 * Set the valid scope names as a '|' separated list of values.
	 * @param string $list
	 * @return string
	 */
	public static function setScopeList(string $list): string {
		return self::current()->setScopeList($list);
	}

	/**
	 * Set the script directory for the application.
	 * @param string $dir
	 * @return string
	 */
	public static function setScriptDir(string $dir): string {
		return self::current()->setScriptDir($dir);
	}

	/**
	 * Set trust proxy to true if we trust the proxy headers
	 * @param boolean $isTrusted
	 */
	public static function setTrustProxy(bool $isTrusted = true) {
		self::current()->setTrustProxy($isTrusted);
	}

	/**
	 * Return the context properties as an array.
	 * @return array
	 */
	public static function toArray(): array {
		return self::current()->toArray();
	}

	/**
	 * Returns the internal state of the context as an array
	 * @return array
	 */
	public static function _dump(): array {
		return self::current()->_dump();
	}

	/**
	 * Reset the context.
	 * The next static call will create a fresh context from
	 * the PHP superglobals.
	 */
	public static function _reset() {
		self::$current = null;
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
		self::current()->_request($_request);
	}

	public static function _server($_server = null): Server {
		return self::current()->server($_server);
	}
}
