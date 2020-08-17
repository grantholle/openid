<?php

namespace Tests;

use Pear\Http\Request2;
use Pear\Http\Request2\Exceptions\Request2Exception;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\OpenId;
use Pear\OpenId\OpenIdMessage;
use Pear\OpenId\Store\CacheLite;
use Pear\OpenId\Store\Store;
use PHPUnit\Framework\TestCase;
use Tests\Store\StoreMock;

/**
 * OpenIDTest
 *
 * PHP Version 5.2.0+
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenIDTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class OpenIdTest extends TestCase
{
    public function setUp(): void
    {
        OpenId::resetInternalData();
    }

    public function testSetAndGetStore()
    {
        $this->assertInstanceOf(CacheLite::class, OpenId::getStore());
        OpenId::setStore(Store::factory(StoreMock::class));
        $this->assertInstanceOf(StoreMock::class, OpenId::getStore());
    }

    public function testGetXRIGlobalSymbols()
    {
        $this->assertTrue(in_array('=', OpenId::getXRIGlobalSymbols()));
    }

    public function testNormalizeIdentifierSuccess()
    {
        // $this->assertSame('=example',
        //                   OpenId::normalizeIdentifier('xri://=example'));
        // $this->assertSame('=example', OpenId::normalizeIdentifier('=example'));
        $this->assertSame('http://example.com/', OpenId::normalizeIdentifier('example.com'));
    }

    public function testNormalizeIdentifierPathStaysIntact()
    {
        $this->assertEquals(
            'http://example.org/foo',
            OpenId::normalizeIdentifier('example.org/foo')
        );
        $this->assertEquals(
            'http://example.org/bar/',
            OpenId::normalizeIdentifier('example.org/bar/')
        );

        // edge cases
        $this->assertEquals(
            'https://e/',
            OpenId::normalizeIdentifier('https://e/')
        );
        $this->assertEquals(
            'https://e/',
            OpenId::normalizeIdentifier('https://e')
        );
    }

    public function testNormalizeIdentifierFail()
    {
        $this->expectException(OpenIdException::class);
        OpenId::normalizeIdentifier('&example');
    }

    public function testNormalizeIdentifierSchemeOnly()
    {
        $this->expectException(OpenIdException::class);
        OpenId::normalizeIdentifier('http://');
    }

//    public function testDirectRequest()
//    {
//        $this->expectException(OpenIdException::class);
//
//        $request = $this->createMock(Request2::class);
//        $request->expects($this->once())
//            ->method('send')
//            ->will($this->throwException(new Request2Exception('foobar')));
//
//        $openid = $this->getMockBuilder(OpenId::class)
//            ->onlyMethods(['getHTTPRequest2Instance'])
//            ->getMock();
//
//        $openid->method('getHTTPRequest2Instance')
//               ->will($this->returnValue($request));
//
//        $message = new OpenIdMessage();
//        $message->set('foo', 'bar');
//        $openid->directRequest('http://example.com', $message);
//    }

//    public function testObservers()
//    {
//        $event1 = array('name' => 'foo1', 'data' => 'bar1');
//        $event2 = array('name' => 'foo2', 'data' => 'bar2');
//        $mock   = new OpenID_Observer_Mock;
//        OpenId::attach($mock);
//        // Test skipping existing observers
//        OpenId::attach($mock);
//        try {
//            OpenId::setLastEvent($event1['name'], $event1['data']);
//            // should not execute
//            $this->assertTrue(false);
//        } catch (OpenID_Exception $e) {
//        }
//        $this->assertSame($event1, OpenId::getLastEvent());
//        OpenId::detach($mock);
//        // Test skipping missing observers
//        OpenId::detach($mock);
//        OpenId::setLastEvent($event2['name'], $event2['data']);
//        $this->assertSame($event2, OpenId::getLastEvent());
//    }
}
