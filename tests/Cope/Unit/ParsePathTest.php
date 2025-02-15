<?php

use Cope\Context as ctx;
class ParsePathTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void {
        ctx::setScopeList('api|web|webhook');
    }

    public function testScopeFirstFullPath(): void {
        $pathParams = ctx::parsePath('api/nusbaum/build.do');
        $this->assertJsonStringEqualsJsonString(
            json_encode($pathParams),
            json_encode(['scope' => 'api', 'kiosk' => 'nusbaum', 'action' => 'build']));
    }
    public function testScopeFirstNoScope(): void {
        $pathParams = ctx::parsePath('nusbaum/build.do');
        $this->assertJsonStringEqualsJsonString(
            json_encode($pathParams),
            json_encode(['scope' => 'webhook', 'kiosk' => 'nusbaum', 'action' => 'build']));
    }

    public function testScopeFirstNoKiosk(): void {
        $pathParams = ctx::parsePath('webhook/build.do');
        $this->assertJsonStringEqualsJsonString(
            json_encode($pathParams),
            json_encode(['scope' => 'webhook', 'kiosk' => null, 'action' => 'build']));
    }

    public function testScopeFirstNoScopeNoKiosk(): void {
        $pathParams = ctx::parsePath('build.do');
        $this->assertJsonStringEqualsJsonString(
            json_encode($pathParams),
            json_encode(['scope' => 'api', 'kiosk' => null, 'action' => 'build']));
    }

    public function testScopeFirstNoScopeNoAction(): void {
        $pathParams = ctx::parsePath('nusbaum');
        $this->assertJsonStringEqualsJsonString(
            json_encode($pathParams),
            json_encode(['scope' => 'webhook', 'kiosk' => 'nusbaum', 'action' => null]));
    }

    public function testScopeFirstTooMuchPath(): void {
        $pathParams = ctx::parsePath('api/smurf/nusbaum/build.do');
        $this->assertJsonStringEqualsJsonString(
            json_encode($pathParams),
            json_encode(['scope' => null,'kiosk' => null, 'action' => null]));
    }
}