<?php

namespace Cope;

class Session extends ArrayMap
{
    protected $is_active = false;
    /**
     * Start a session to store persistent attributes
     */
    public function __construct() {
        parent::__construct();
        if ((PHP_SESSION_ACTIVE == ($status = session_status())
            || (PHP_SESSION_NONE == $status && session_start()))) {
            $this->array = &$_SESSION;
            $this->is_active = true;
        }
    }

    /**
     * Clear the current session.
     */
    public function clear() {
        if(session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
        }
    }

    /**
     * Returns the value for a named session attribute.
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $name, $default=null) {
        return $this->get($name, $default);
    }

    /**
     * Returns the session id.
     * Returns null when there is no session, unlike session_id()
     * which returns an empty string.
     */
    public function getId(): ?string {
        if(empty($id = session_id())) {
            return null;
        }
        return $id;
    }

    /**
     * Invalidate the session for this context.
     */
    public function invalidate() {
        //remove session cookie from browser
        if ( isset( $_COOKIE[session_name()] ) ) {
            setcookie( session_name(), "", time()-3600, "/" );
        }
        //clear session
        $this->clear();
        //clear session from disk
        session_destroy();
    }

    public function isActive(): bool {
        return $this->is_active;
    }
}