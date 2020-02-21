<?php
/**
 * Command.php
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

/**
 * Data structure describing a web Command
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
	
	/** @var string the name of the UI tab for this Command */
	public $tab;
	
	/**@var string the name of the UI tab group for this Command */
	public $tabgroup;
	
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
	
	/**
	 * Set the name of the UI tab for this Command
	 * @param string $tab_name
	 * @return Command
	 */
	public function setTab($tab_name) {
		$this->tab = $tab_name;
		return $this;
	}
	
	/**
	 * Set the name of the UI tab group for this Command
	 * @param string $tabgroup_name
	 * @return Command
	 */
	public function setTabGroup($tabgroup_name) {
		$this->tabgroup = $tabgroup_name;
		return $this;
	}
	
	/**
	 * Send the body as json.
	 * The content-type is set to application/json
	 * @param mixed $body
	 */
	public static function sendJson($json) {
	    header('Content-Type: application/json');
	    echo json_encode($json,JSON_PRETTY_PRINT);
	}
	
	/**
	 * Set the status code for the response.
	 * @param int $status_code
	 */
	public static function sendStatus($status_code,$message=null) {
	    http_response_code($status_code);
	    echo $message;
	}
	
}