<?php

use Cope\Context as ctx;
class ServerTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyString(): void {
        ctx::reset();
    }

    public function testHtmlString(): void {
        ctx::reset();
        ctx::setServer([
            'SERVER_NAME' => 'nohost',
            ]
        );
        $this->assertEquals('nohost',ctx::getServer()->SERVER_NAME);
        $this->assertEquals(80, ctx::getServer()->get('SERVER_PORT'));
    }
}