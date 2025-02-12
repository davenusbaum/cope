<?php

use Cope\Context as ctx;
class ServerTest extends \PHPUnit\Framework\TestCase
{
    public function testHtmlString(): void {
        ctx::_reset();
        ctx::_server([
            'SERVER_NAME' => 'nohost',
            ]
        );
        $this->assertEquals('nohost',ctx::_server()->SERVER_NAME);
        $this->assertEquals(80, ctx::_server()->get('SERVER_PORT'));
        ctx::toArray();
    }
}