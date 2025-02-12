<?php

use Cope\Context as ctx;
class ParameterTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyString(): void {
        ctx::_reset();
        $test = ctx::getParameters()->get('test');
        $this->assertEquals(null, $test);
    }
    public function testHtmlString(): void {
        ctx::_reset();
        ctx::_request([
            'test' => '<b>Bob</b>',
            'simple' => 'Just a simple string',
            'XSS1' => 'xssbys3c%22%3E%3Cscript%3Ealert(2)%3C/script%3E',
            'XSS2' => '%27%3E%22%3E%3C/script%3E%3Csvg/onload=prompt(1337)%3E',
        ]);
        $this->assertEquals('Just a simple string', ctx::getParameter('simple'));
        $this->assertEquals('<b>Bob</b>', ctx::getParameters()->test);
        /*
        $test = ctx::getParameters()->get('test');
        $this->assertEquals('Bob', $test);
        $test = ctx::getParameters()->get('XSS1');
        $this->assertEquals("xssbys3c\">alert(2)", $test);
        $test = ctx::getParameters()->get('XSS2');
        $this->assertEquals("'>\">", $test);
        */
    }
}