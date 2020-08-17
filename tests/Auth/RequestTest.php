<?php

namespace Tests\Auth;

/**
 * OpenIdAuthRequest()Test
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

use Pear\OpenId\Auth\OpenIdAuthRequest;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Exceptions\OpenIdAuthException;
use Pear\OpenId\Extensions\OpenIdExtension;
use Pear\OpenId\Extensions\UI;
use Pear\OpenId\Nonce;
use Pear\OpenId\OpenId;
use PHPUnit\Framework\TestCase;
use Tests\Store\StoreMock;

/**
 * OpenIdAuthRequest()Test
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class RequestTest extends TestCase
{
    /**
     * @var OpenIdAuthRequest
     */
    protected $authRequest = null;
    protected $identifier = 'http://user.example.com/';
    protected $returnTo = 'http://examplerp.com';
    protected $opURL = 'http://exampleop.com';
    protected $realm = 'http://example.com';

    /**
     * @var Discover
     */
    protected $discover = null;
    protected $assocHandle = '12345';

    public function setUp(): void
    {
        Discover::$discoveryOrder = [\Tests\Discover\Mock::class];

        $opEndpoint = new \Pear\OpenId\ServiceEndpoint();
        $opEndpoint->setVersion(OpenId::SERVICE_2_0_SERVER);
        $opEndpoint->setTypes([OpenId::SERVICE_2_0_SERVER]);
        $opEndpoint->setURIs([$this->opURL]);

        \Tests\Discover\Mock::$opEndpoint = $opEndpoint;

        $this->setObjects();
    }

    protected function setObjects()
    {
        $this->discover = new Discover($this->identifier);
        $this->discover->discover();

        $this->authRequest = new OpenIdAuthRequest($this->discover, $this->returnTo, $this->realm, $this->assocHandle);
    }

    public function tearDown(): void
    {
        Discover::$discoveryOrder = [
            0  => Discover::TYPE_YADIS,
            10 => Discover::TYPE_HTML
        ];

        \Tests\Discover\Mock::$opEndpoint = null;

        $this->discover    = null;
        $this->authRequest = null;
    }

    public function testAddExtension()
    {
        $ui = new UI(OpenIdExtension::REQUEST);
        $this->authRequest->addExtension($ui);

        $message = $this->authRequest->getMessage();

        $this->assertNotEmpty($message->getData());
    }

    public function testSetModeFail()
    {
        $this->expectException(OpenIdAuthException::class);
        $this->authRequest->setMode('foo');
    }

    public function testSetModeSuccess()
    {
        $mode = OpenId::MODE_CHECKID_IMMEDIATE;
        $this->authRequest->setMode($mode);
        $this->assertSame($mode, $this->authRequest->getMode());
    }

    public function testGetAuthorizeURL()
    {
        $url     = $this->authRequest->getAuthorizeURL();
        $split   = preg_split('/\?/', $url);
        $message = new \Pear\OpenId\OpenIdMessage($split[1], \Pear\OpenId\OpenIdMessage::FORMAT_HTTP);

        $this->assertSame($this->returnTo, $message->get('openid.return_to'));
        $this->assertSame(OpenId::NS_2_0_ID_SELECT, $message->get('openid.identity'));
        $this->assertSame(OpenId::NS_2_0_ID_SELECT, $message->get('openid.claimed_id'));
        $this->assertSame($this->opURL, $split[0]);
    }

    public function testGetAuthorizeURLWithQueryString()
    {
        $originalURL = $this->opURL;
        $newURL = 'http://exampleop.com/foobar?foo=bar';
        $this->opURL = $newURL;
        $this->setUp();
        $url = $this->authRequest->getAuthorizeURL();
        $split = preg_split('/\?/', $url);
        $message = new \Pear\OpenId\OpenIdMessage($split[1], \Pear\OpenId\OpenIdMessage::FORMAT_HTTP);

        $this->assertSame($this->returnTo, $message->get('openid.return_to'));
        $this->assertSame(OpenId::NS_2_0_ID_SELECT, $message->get('openid.identity'));
        $this->assertSame(OpenId::NS_2_0_ID_SELECT, $message->get('openid.claimed_id'));
        $this->assertSame('bar', $message->get('foo'));
        $this->opURL = $originalURL;
    }

    public function testGetAuthorizeURLSignon()
    {
        $opEndpoint = new \Pear\OpenId\ServiceEndpoint();
        $opEndpoint->setVersion(OpenId::SERVICE_2_0_SIGNON);
        $opEndpoint->setTypes([OpenId::SERVICE_2_0_SIGNON]);
        $opEndpoint->setURIs([$this->opURL]);

        \Tests\Discover\Mock::$opEndpoint = $opEndpoint;

        $this->setObjects();

        $url = $this->authRequest->getAuthorizeURL();
        $split = preg_split('/\?/', $url);
        $message = new \Pear\OpenId\OpenIdMessage($split[1], \Pear\OpenId\OpenIdMessage::FORMAT_HTTP);

        $this->assertSame($this->returnTo, $message->get('openid.return_to'));
        $this->assertSame($this->identifier, $message->get('openid.identity'));
        $this->assertSame($this->identifier, $message->get('openid.claimed_id'));
        $this->assertSame($this->opURL, $split[0]);
    }

    public function testGetAuthorizeURLSignonLocalID()
    {
        $opEndpoint = new \Pear\OpenId\ServiceEndpoint();
        $opEndpoint->setVersion(OpenId::SERVICE_2_0_SIGNON);
        $opEndpoint->setTypes([OpenId::SERVICE_2_0_SIGNON]);
        $opEndpoint->setLocalID($this->identifier);
        $opEndpoint->setURIs([$this->opURL]);

        \Tests\Discover\Mock::$opEndpoint = $opEndpoint;

        $this->setObjects();

        $url = $this->authRequest->getAuthorizeURL();
        $split = preg_split('/\?/', $url);
        $message = new \Pear\OpenId\OpenIdMessage($split[1], \Pear\OpenId\OpenIdMessage::FORMAT_HTTP);
        $this->assertSame($this->returnTo, $message->get('openid.return_to'));
        $this->assertSame($this->identifier, $message->get('openid.identity'));
        $this->assertSame($this->identifier, $message->get('openid.claimed_id'));
        $this->assertSame($this->opURL, $split[0]);
    }

    public function testGetAuthorizeURLSignonLocalIDOneOne()
    {
        $opEndpoint = new \Pear\OpenId\ServiceEndpoint();
        $opEndpoint->setVersion(OpenId::SERVICE_1_1_SIGNON);
        $opEndpoint->setTypes([OpenId::SERVICE_1_1_SIGNON]);
        $opEndpoint->setLocalID($this->identifier);
        $opEndpoint->setURIs([$this->opURL]);

        \Tests\Discover\Mock::$opEndpoint = $opEndpoint;

        $this->setObjects();

        $url = $this->authRequest->getAuthorizeURL();
        $split = preg_split('/\?/', $url);
        $message = new \Pear\OpenId\OpenIdMessage($split[1], \Pear\OpenId\OpenIdMessage::FORMAT_HTTP);

        $this->assertNotSame($this->returnTo, $message->get('openid.return_to'));
        $this->assertSame($this->identifier, $message->get('openid.identity'));
        $this->assertSame(null, $message->get('openid.claimed_id'));
        $this->assertSame($this->opURL, $split[0]);

        // Mock nonce/store rather than have a new one created
        $store = new StoreMock();
        $nonce = new Nonce($this->opURL);
        OpenId::setStore($store);
        $this->authRequest->setNonce($nonce);

        $url = $this->authRequest->getAuthorizeURL();
        $this->assertIsString($url);
    }

    public function testGetDiscover()
    {
        $this->assertSame($this->discover, $this->authRequest->getDiscover());
    }
}
