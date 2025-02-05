<?php

namespace Cope;

/**
 * Data structure describing a web command in the cope framework.
 */
class Command {
	
	/** @var boolean Set to true if user authentication is required  */
	public $authenticate = true;
	
	/**
	 * @var string The name of authorization script to run.
	 * This script should return the access level for the current user.
	 */
	public $authorize;
	
	/** @var int The access level required to execute the Command. */
	public $access_level = 1;
	
	/** @var string The name of the validation script to run. */
	public $validate;
	
	/** @var string Set the script to be run on a POST request */
	public $post;
	
	/** @var string Set the script to be run on a GET, or failed POST request */
	public $get;
	
	/** @var string Set the default page to be rendered */
	public $page;
	
	/** @var boolean Set to true (default) if a session is required  */
	public $session = true;
	
	/**
	 * Create a new Command and initialize with an array
	 * @param array $a
	 * @return Command
	 */
	public static function __set_state($a) {
		// odd, but faster and less memory intensive than a loop
		$obj = new static();
		if(isset($a['session'])) $obj->session = $a['session'];
		if(isset($a['authenticate'])) $obj->authenticate = $a['authenticate'];
		if(isset($a['authorize'])) $obj->authorize = $a['authorize'];
		if(isset($a['access_level'])) $obj->access_level = $a['access_level'];
		if(isset($a['validate'])) $obj->validate = $a['validate'];
		if(isset($a['post'])) $obj->post = $a['post'];
		if(isset($a['get'])) $obj->get = $a['get'];
		if(isset($a['page'])) $obj->page = $a['page'];
		if(isset($a['tab'])) $obj->page = $a['tab'];
		if(isset($a['tabgroup'])) $obj->page = $a['tabgroup'];
		return $obj;
	}
	
	/**
	 * Set the access level required to execute this context.
	 * @param int $level
	 * @return Command
	 */
	public function setAccessLevel($level) {
		$this->access_level = $level;
		return $this;
	}
	
	/**
	 * Set the name of the script to handle authorization of requests
	 * for this Command.
	 * @param string script_name
	 * @return Command
	 */
	public function setAuthorize($script_name) {
		$this->authorize = $script_name;
		return $this;
	}
	
	/**
	 * Set the name of the script to handle GET requests, and incomplete
	 * POST requests, for this Command.
	 * @param string $script_name
	 * @return Command
	 */
	public function setGet($script_name) {
		$this->get = $script_name;
		return $this;
	}
	
	
	/**
	 * Set the page name for this Command.
	 * @param string $page_name
	 * @return Command
	 */
	public function setPage($page_name) {
		$this->page = $page_name;
		return $this;
	}
	
	/**
	 * Set the name of the script to handle a post request.
	 * @param string $script_name the script name.
	 * @return Command
	 */
	public function setPost($script_name) {
		$this->post = $script_name;
		return $this;
	}
	
	/**
	 * Set the name of the script validate POST requests.
	 * @param string script_name
	 * @return Command
	 */
	public function setValidate($script_name) {
		$this->validate = $script_name;
		return $this;
	}
	
	/**
	 * Indicate that authentication is required.
	 * If true, Command->authenticate is called to authenticate the user.
	 * @param boolean $is_required true if authentication is required.
	 * @return Command
	 */
	public function setAuthenticate($is_required) {
		$this->authenticate = $is_required;
		return $this;
	}
	
	
	/**
	 * Indicate that a session is required for this Command.
	 * If true, a session is started.
	 * @param boolean $is_required true if a session is required.
	 * @return Command
	 */
	public function setSession($is_required) {
		$this->session = $is_required;
		return $this;
	}
	
}