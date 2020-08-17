<?php

namespace Tests;

use Pear\Net\Url2;
use Pear\OpenId\Assertions\Assertion;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Exceptions\OpenIdAssertionException;
use Pear\OpenId\Nonce;
use Pear\OpenId\OpenId;
use Pear\OpenId\OpenIdMessage;
use Pear\OpenId\ServiceEndpoint;
use Pear\OpenId\ServiceEndpoints;
use PHPUnit\Framework\TestCase;
use Tests\Store\StoreMock;

/**
 * OpenID_AssertionTest
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
 * OpenID_AssertionTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class AssertionTest extends TestCase
{
    protected $message = null;
    protected $requestedURL = 'http://examplerp.com';
    protected $claimedID = 'http://user.example.com';
    protected $opEndpointURL = 'http://exampleop.com';
    protected $store = null;
    protected $discover = null;
    protected $assertion = null;
    protected $clockSkew = 600;

    public function setUp(): void
    {
        $this->store = $this->createMock(StoreMock::class);

        $nonce = new Nonce($this->opEndpointURL);

        $this->message = new OpenIdMessage;
        $this->message->set('openid.ns', OpenId::NS_2_0);
        $this->message->set('openid.return_to', $this->requestedURL);
        $this->message->set('openid.op_endpoint', $this->opEndpointURL);
        $this->message->set('openid.claimed_id', $this->claimedID);
        $this->message->set('openid.response_nonce', $nonce->createNonce());
    }

    public function tearDown(): void
    {
        $this->message = null;
        $this->store = null;
        $this->assertion = null;
        $this->discover = null;
    }

    protected function createObjects()
    {
        OpenId::setStore($this->store);

        $this->assertion = $this->getMockBuilder(Assertion::class)
            ->onlyMethods(['getHTTPRequest2Instance'])
            ->setConstructorArgs([
                $this->message,
                new Url2($this->requestedURL),
                $this->clockSkew
            ])
            ->getMock();
    }

    public function testValidateReturnTo()
    {
        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs([$this->opEndpointURL]);
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $this->discover = $this->getMockBuilder(Discover::class)
            ->onlyMethods(['__get'])
            ->setConstructorArgs([$this->claimedID])
            ->getMock();

        $this->store
            ->expects($this->once())
            ->method('getDiscover')
            ->will($this->returnValue($this->discover));

        $this->store
            ->expects($this->once())
            ->method('getNonce')
            ->will($this->returnValue(false));

        $this->createObjects();
    }

    public function testValidateReturnToOneOneImmediateNegative()
    {
        // $this->validateReturnTo();
        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs([$this->opEndpointURL]);
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $nonce = new Nonce($this->opEndpointURL);
        $nonceValue = $nonce->createNonce();

        $rt = new Url2('http://examplerp.com');
        $rt->setQueryVariable(Nonce::RETURN_TO_NONCE, $nonceValue);

        $setupMessage = new OpenIdMessage();
        $setupMessage->set('openid.identity', $this->claimedID);
        $setupMessage->set('openid.return_to', $rt->getURL());
        $setupMessage->set(Nonce::RETURN_TO_NONCE, $nonceValue);

        $this->message = new OpenIdMessage();
        $this->message->set('openid.mode', OpenId::MODE_ID_RES);
        $this->message->set(Nonce::RETURN_TO_NONCE, $nonceValue);
        $this->message->set('openid.user_setup_url', 'http://examplerp.com/?' . $setupMessage->getHTTPFormat());
    }

    /**
     * testValidateReturnToWithQueryStringParameters
     *
     * @return void
     */
    public function testValidateReturnToWithQueryStringParameters()
    {
        $this->requestedURL = $this->requestedURL . '?foo=bar';
        $this->setUp();

        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs(array($this->opEndpointURL));
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->claimedID));
        $this->discover->expects($this->once())
                       ->method('__get')
                       ->will($this->returnValue($opEndpoints));

        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getNonce')
                    ->will($this->returnValue(false));
        $this->createObjects();
    }

    public function testValidateReturnToFailInvalidURI()
    {
        $this->expectException(OpenIdAssertionException::class);
        $this->message->set('openid.return_to', 'http:///foo&bar');
        $this->createObjects();
    }

    public function testValidateReturnToFailDifferentURLs()
    {
        $this->expectException(OpenIdAssertionException::class);
        $this->message->set('openid.return_to', 'http://foo.com');

        $this->discover = $this->getMockBuilder(Discover::class)
            ->onlyMethods(['__get'])
            ->setConstructorArgs([$this->claimedID])
            ->getMock();

        $this->createObjects();
    }

    /**
     * testValidateReturnToFailDifferentQueryStringParameters
     *
     * @expectedException OpenID_Assertion_Exception
     * @return void
     */
    public function testValidateReturnToFailDifferentQueryStringParameters()
    {
        $this->message->set('openid.return_to', $this->requestedURL . '?foo=bar');
        $this->discover = $this->getMock('OpenID_Discover',
                                         ['__get'],
                                         [$this->claimedID]);
        $this->createObjects();
    }

    /**
     * testValidateReturnToNonce
     *
     * @return void
     */
    public function testValidateReturnToNonce()
    {
        $nonce      = new Nonce($this->opEndpointURL);
        $nonceValue = $nonce->createNonce();

        $this->message->delete('openid.ns');
        $this->message->delete('openid.claimed_id');
        $this->message->set('openid.identity', $this->claimedID);
        $rtnonce = $this->requestedURL . '?' . Nonce::RETURN_TO_NONCE
                   . '=' . urlencode($nonceValue);
        $this->message->set('openid.return_to', $rtnonce);
        $this->requestedURL = $rtnonce;

        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->claimedID));

        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs(array($this->opEndpointURL));
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $this->discover->expects($this->once())
                       ->method('__get')
                       ->will($this->returnValue($opEndpoints));

        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->any())
                    ->method('getNonce')
                    ->will($this->returnValue($rtnonce));

        $this->createObjects();
    }

    /**
     * testValidateReturnToNonceFailInvalid
     *
     * @expectedException OpenID_Assertion_Exception
     * @return void
     */
    public function testValidateReturnToNonceFailInvalid()
    {
        $nonce      = new Nonce($this->opEndpointURL);
        $nonceValue = $nonce->createNonce();

        $this->message->delete('openid.ns');
        $this->message->delete('openid.claimed_id');
        $this->message->set('openid.identity', $this->claimedID);
        $rtnonce = $this->requestedURL . '?' . Nonce::RETURN_TO_NONCE
                   . '=' . urlencode($nonceValue);
        $this->message->set('openid.return_to', $rtnonce);
        $this->requestedURL = $rtnonce;

        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->claimedID));

        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs(array($this->opEndpointURL));
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $this->discover->expects($this->once())
                       ->method('__get')
                       ->will($this->returnValue($opEndpoints));

        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->any())
                    ->method('getNonce')
                    ->will($this->returnValue(false));

        $this->createObjects();
    }

    /**
     * testValidateReturnToNonceFailMissing
     *
     * @expectedException OpenID_Assertion_Exception
     * @return void
     */
    public function testValidateReturnToNonceFailMissing()
    {
        $this->message->delete('openid.ns');
        $this->message->delete('openid.claimed_id');
        $this->message->set('openid.identity', $this->claimedID);

        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->claimedID));

        $this->createObjects();
    }

    /**
     * testValidateDiscoverFailNoClaimedID
     *
     * @expectedException OpenID_Assertion_Exception_NoClaimedID
     * @return void
     */
    public function testValidateDiscoverFailNoClaimedID()
    {
        $this->message->delete('openid.claimed_id');
        $this->createObjects();
    }

    /**
     * testValidateDiscoverFailOPIdentifier
     *
     * @expectedException OpenID_Assertion_Exception
     * @return void
     */
    public function testValidateDiscoverFailOPIdentifier()
    {
        $this->message->set('openid.claimed_id', OpenId::SERVICE_2_0_SERVER);
        $this->createObjects();
    }

    /**
     * testValidateDiscoverFail
     *
     * @expectedException OpenID_Assertion_Exception
     * @return void
     */
    public function testValidateDiscoverFail()
    {
        OpenId::setStore($this->store);

        $this->assertion = $this->getMock('OpenID_Assertion',
                                          ['getHTTPRequest2Instance', 'getDiscover'],
                                          array($this->message,
                                                new Url2($this->requestedURL),
                                                $this->clockSkew));
    }

    /**
     * testValidateDiscoverFailOPNotAuthorized
     *
     * @expectedException OpenID_Assertion_Exception
     * @return void
     */
    public function testValidateDiscoverFailOPNotAuthorized()
    {
        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->claimedID));

        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs(array('http://exampleop2.com'));
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $this->discover->expects($this->once())
                       ->method('__get')
                       ->will($this->returnValue($opEndpoints));

        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));

        $this->createObjects();
    }

    /**
     * testValidateNonceFail
     *
     * @expectedException OpenID_Assertion_Exception
     * @return void
     */
    public function testValidateNonceFail()
    {
        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->claimedID));

        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs(array($this->opEndpointURL));
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $this->discover->expects($this->once())
                       ->method('__get')
                       ->will($this->returnValue($opEndpoints));

        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getNonce')
                    ->will($this->returnValue(true));
        $this->createObjects();
    }

    /**
     * testVerifySignature
     *
     * @return void
     */
    public function testVerifySignature()
    {
        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs(array($this->opEndpointURL));
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->claimedID));
        $this->discover->expects($this->once())
                       ->method('__get')
                       ->will($this->returnValue($opEndpoints));

        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getNonce')
                    ->will($this->returnValue(false));
        $this->createObjects();

        $association = new OpenID_Association(array(
                                              'uri'          => $this->opEndpointURL,
                                              'expiresIn'    => 600,
                                              'created'      => time(),
                                              'assocType'    => 'HMAC-SHA1',
                                              'assocHandle'  => '12345',
                                              'sharedSecret' => '6789'));

        $this->message->set('openid.assoc_handle', '12345');
        $association->signMessage($this->message);
        $this->assertTrue($this->assertion->verifySignature($association));

        $this->message->set('openid.sig', 'foo');
        $this->assertFalse($this->assertion->verifySignature($association));
    }

    /**
     * testCheckAuthentication
     *
     * @return void
     */
    public function testCheckAuthentication()
    {
        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs([$this->opEndpointURL]);
        $opEndpoints = new ServiceEndpoints($this->claimedID, $opEndpoint);

        $this->discover = $this->getMock('OpenID_Discover',
                                         array('__get'),
                                         array($this->claimedID));
        $this->discover->expects($this->once())
                       ->method('__get')
                       ->will($this->returnValue($opEndpoints));

        $this->store->expects($this->once())
                    ->method('getDiscover')
                    ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
                    ->method('getNonce')
                    ->will($this->returnValue(false));
        $this->createObjects();

        $adapter  = new HTTP_Request2_Adapter_Mock;
        $content  = "HTTP/1.1 200\n";
        $content .= "Content-Type: text/html; charset=iso-8859-1\n\n\n";
        $content .= "foo:bar\n";
        $adapter->addResponse($content);

        $httpRequest = new HTTP_Request2;
        $httpRequest->setAdapter($adapter);

        $this->assertion->expects($this->once())
                        ->method('getHTTPRequest2Instance')
                        ->will($this->returnValue($httpRequest));

        $result = $this->assertion->checkAuthentication();
    }
}
?>
