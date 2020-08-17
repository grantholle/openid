<?php

namespace Tests;

use Pear\OpenId\Exceptions\OpenIdMessageException;
use Pear\OpenId\Extensions\AX;
use Pear\OpenId\Extensions\OpenIdExtension;
use Pear\OpenId\OpenIdMessage;
use PHPUnit\Framework\TestCase;

/**
 * OpenIdMessageTest
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
 * OpenIdMessageTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class MessageTest extends TestCase
{
    /**
     * @var OpenIdMessage
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new OpenIdMessage;
    }

    protected function tearDown(): void
    {
        unset($this->object);
    }

    public function testGet()
    {
        $key = 'openid.foo';
        $value = 'bar';

        $this->object->set($key, $value);
        $this->assertSame($value, $this->object->get($key));
    }

    public function testSet()
    {
        $key = 'openid.foo';
        $value = 'bar';

        $this->assertSame(null, $this->object->get($key));

        $this->object->set($key, $value);
        $this->assertSame($value, $this->object->get($key));
    }

    public function testSetFailure()
    {
        $this->expectException(OpenIdMessageException::class);
        $this->object->set('openid.ns', 'foo');
    }

    public function testDelete()
    {
        $key   = 'openid.foo';
        $value = 'bar';

        $this->assertSame(null, $this->object->get($key));

        $this->object->set($key, $value);
        $this->assertSame($value, $this->object->get($key));

        $this->object->delete($key);
        $this->assertSame(null, $this->object->get($key));
    }

    public function testGetKVFormat()
    {
        $kv = "openid.foo:bar\n";
        $this->object->set('openid.foo', 'bar');
        $this->assertSame($kv, $this->object->getKVFormat());
    }

    public function testGetHTTPFormat()
    {
        $http = "openid.foo=foo+bar&openid.bar=foo+bar";
        $this->object->set('openid.foo', 'foo bar');
        $this->object->set('openid.bar', 'foo bar');
        $this->assertSame($http, $this->object->getHTTPFormat());
    }

    public function testGetArrayFormat()
    {
        $http = [
            'openid.foo' => 'foo bar',
            'openid.bar' => 'foo bar',
        ];

        $this->object->set('openid.foo', 'foo bar');
        $this->object->set('openid.bar', 'foo bar');
        $this->assertSame($http, $this->object->getArrayFormat());
    }

    public function testGetMessageFail()
    {
        $this->expectException(OpenIdMessageException::class);
        $this->object->getMessage('foo');
    }

    public function testSetMessage()
    {
        // KV
        $kv = "openid.foo:foo bar\nopenid.bar:foo bar\n";

        // Test constructor setting
        $this->object = new OpenIdMessage($kv, OpenIdMessage::FORMAT_KV);
        $this->assertSame($kv, $this->object->getKVFormat());

        // HTTP
        $http = "openid.foo=foo+bar&openid.bar=foo+bar";

        $this->object->setMessage($http, OpenIdMessage::FORMAT_HTTP);
        $this->assertSame($http, $this->object->getHTTPFormat());

        // Array
        $array = [
            'openid.foo' => 'foo bar',
            'openid.bar' => 'foo bar'
        ];

        $this->object->setMessage($array, OpenIdMessage::FORMAT_ARRAY);
        $this->assertSame($array, $this->object->getArrayFormat());
    }

    public function testSetMessageKvInvalid()
    {
        $this->object = new OpenIdMessage('a', OpenIdMessage::FORMAT_KV);
        $this->assertSame('', $this->object->getKVFormat());
    }

    public function testSetMessageFailure()
    {
        $this->expectException(OpenIdMessageException::class);
        $this->object->setMessage("openid.foo:bar\n", 'foobar');
    }

    public function testAddExtension()
    {
        $extension = new AX(OpenIdExtension::REQUEST);
        $extension->set('foo', 'bar');
        $this->object->addExtension($extension);
        $this->assertSame('bar', $this->object->get('openid.ax.foo'));
    }
}
