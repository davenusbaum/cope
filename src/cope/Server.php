<?php

namespace Cope;

class Server extends ArrayMap
{
    public function __construct(?array $server = null) {
        parent::__construct(
            array_replace(
                [
                    'SERVER_NAME' => 'localhost',
                    'SERVER_PORT' => 80,
                    'HTTP_HOST' => 'localhost',
                    'HTTP_USER_AGENT' => 'Cope',
                    'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
                    'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
                    'REMOTE_ADDR' => '127.0.0.1',
                    'SCRIPT_NAME' => '',
                    'SCRIPT_FILENAME' => '',
                    'SERVER_PROTOCOL' => 'HTTP/1.1',
                    'REQUEST_TIME' => time(),
                    'REQUEST_TIME_FLOAT' => microtime(true),
                ],
                ($server ?? $_SERVER)
            )
        );
    }

    /**
     * Return the named request header.
     * @param string $name
     * @param string|null $default
     * @return string|null
     */
    public function getHeader(string $name, string $default=null): ?string {
        $name = strtoupper(str_replace('-','_',$name));
        if (empty($name)) {
            return $default;
        }
        if (!in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
            $name = 'HTTP_' . $name;
        }
        return $this->get($name, $default);
    }
}