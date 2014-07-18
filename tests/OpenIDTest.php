<?php
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

require_once 'OpenID.php';
require_once 'OpenID/Store.php';
require_once 'OpenID/Message.php';
require_once 'OpenID/Observer/Mock.php';

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
class OpenIDTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        OpenID::resetInternalData();
    }

    /**
     * testSetAndGetStore 
     * 
     * @return void
     */
    public function testSetAndGetStore()
    {
        $this->assertInstanceOf('OpenID_Store_CacheLite', OpenID::getStore());
        OpenID::setStore(OpenID_Store::factory('Mock'));
        $this->assertInstanceOf('OpenID_Store_Mock', OpenID::getStore());
    }

    /**
     * testGetXRIGlobalSymbols 
     * 
     * @return void
     */
    public function testGetXRIGlobalSymbols()
    {
        $this->assertTrue(in_array('=', OpenID::getXRIGlobalSymbols()));
    }

    /**
     * testNormalizeIdentifierSuccess 
     * 
     * @return void
     */
    public function testNormalizeIdentifierSuccess()
    {
        // $this->assertSame('=example',
        //                   OpenID::normalizeIdentifier('xri://=example'));
        // $this->assertSame('=example', OpenID::normalizeIdentifier('=example'));
        $this->assertSame('http://example.com/',
                          OpenID::normalizeIdentifier('example.com'));
    }

    public function testNormalizeIdentifierPathStaysIntact()
    {
        $this->assertEquals(
            'http://example.org/foo',
            OpenID::normalizeIdentifier('example.org/foo')
        );
        $this->assertEquals(
            'http://example.org/bar/',
            OpenID::normalizeIdentifier('example.org/bar/')
        );

        //edge cases
        $this->assertEquals(
            'https://e/',
            OpenID::normalizeIdentifier('https://e/')
        );
        $this->assertEquals(
            'https://e/',
            OpenID::normalizeIdentifier('https://e')
        );
    }

    /**
     * testNormalizeIdentifierFail 
     * 
     * @expectedException OpenID_Exception
     * @return void
     */
    public function testNormalizeIdentifierFail()
    {
        OpenID::normalizeIdentifier('&example');
    }

    /**
     * @expectedException OpenID_Exception
     */
    public function testNormalizeIdentifierSchemeOnly()
    {
        OpenID::normalizeIdentifier('http://');
    }

    /**
     * testNormalizeIdentifierFailXRI 
     * 
     * @return void
     */
    public function testNormalizeIdentifierFailXRI()
    {
        try {
            OpenID::normalizeIdentifier('xri://foo.com');
        } catch (OpenID_Exception $e1) {
            $this->assertFalse(false);
        }

        try {
            OpenID::normalizeIdentifier('=example');
        } catch (OpenID_Exception $e2) {
            $this->assertFalse(false);
        }
    }

    /**
     * testDirectRequest 
     * 
     * @return void
     */
    public function testDirectRequest()
    {
        $this->setExpectedException('OpenID_Exception', 'foobar');
        $request = $this->getMock('HTTP_Request2', array('send'));
        $request->expects($this->once())
                ->method('send')
                ->will($this->throwException(new HTTP_Request2_Exception('foobar')));
        $openid = $this->getMock('OpenID', array('getHTTPRequest2Instance'));
        $openid->expects($this->once())
               ->method('getHTTPRequest2Instance')
               ->will($this->returnValue($request));
        $message = new OpenID_Message;
        $message->set('foo', 'bar');
        $openid->directRequest('http://example.com', $message);
    }

    /**
     * testObservers 
     * 
     * @return void
     */
    public function testObservers()
    {
        $event1 = array('name' => 'foo1', 'data' => 'bar1');
        $event2 = array('name' => 'foo2', 'data' => 'bar2');
        $mock   = new OpenID_Observer_Mock;
        OpenID::attach($mock);
        // Test skipping existing observers
        OpenID::attach($mock);
        try {
            OpenID::setLastEvent($event1['name'], $event1['data']);
            // should not execute
            $this->assertTrue(false);
        } catch (OpenID_Exception $e) {
        }
        $this->assertSame($event1, OpenID::getLastEvent());
        OpenID::detach($mock);
        // Test skipping missing observers
        OpenID::detach($mock);
        OpenID::setLastEvent($event2['name'], $event2['data']);
        $this->assertSame($event2, OpenID::getLastEvent());
    }
}
?>
