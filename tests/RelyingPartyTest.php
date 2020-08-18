<?php

namespace Tests;

use Pear\Net\Url2;
use Pear\OpenId\Assertions\Assertion;
use Pear\OpenId\Assertions\OpenIdAssertionResult;
use Pear\OpenId\Associations\Association;
use Pear\OpenId\Associations\Request;
use Pear\OpenId\Auth\OpenIdAuthRequest;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Nonce;
use Pear\OpenId\Observers\Log;
use Pear\OpenId\OpenId;
use Pear\OpenId\OpenIdMessage;
use Pear\OpenId\RelyingParty;
use Pear\OpenId\ServiceEndpoint;
use Pear\OpenId\ServiceEndpoints;
use PHPUnit\Framework\TestCase;
use Tests\Discover\Mock;
use Tests\RelyingParty\RelyingPartyMock;
use Tests\Store\StoreMock;

/**
 * OpenID_RelyingPartyTest
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
 * OpenID_RelyingPartyTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class RelyingPartyTest extends TestCase
{
    protected $id = 'http://user.example.com';
    protected $returnTo = 'http://openid.examplerp.com';
    protected $realm = 'http://examplerp.com';
    protected $rp = null;
    protected $opEndpointURL = 'http://exampleop.com';
    protected $discover = null;
    protected $store = null;
    protected $association = null;

    public function setUp(): void
    {
        $this->rp = $this->getMockBuilder(RelyingParty::class)
            ->onlyMethods(['getAssociationRequestObject', 'getAssertionObject'])
            ->setConstructorArgs([$this->returnTo, $this->realm, $this->id])
            ->getMock();

        $this->store = $this->createMock(StoreMock::class);

        OpenId::setStore($this->store);

        $this->discover = new Mock($this->id);

        $params = [
            'uri' => 'http://example.com',
            'expiresIn' => 600,
            'created' => 1240980848,
            'assocType' => 'HMAC-SHA256',
            'assocHandle' => 'foobar{}',
            'sharedSecret' => '12345qwerty'
        ];

        $this->association = $this->getMockBuilder(Association::class)
            ->onlyMethods(['checkMessageSignature'])
            ->setConstructorArgs([$params])
            ->getMock();
    }

    public function tearDown(): void
    {
        $this->rp = null;
        $this->store = null;
        $this->association = null;
    }

    protected function setStoreMethods($once = false)
    {
        $this->store->expects($once ? $this->once() : $this->any())
            ->method('getDiscover')
            ->will($this->returnValue($this->discover));
        $this->store->expects($once ? $this->once() : $this->any())
            ->method('getAssociation')
            ->will($this->returnValue(false));
    }

    public function testEnableDisableAssociations()
    {
        $anExceptionWasThrown = false;

        try {
            $this->rp->enableAssociations();
            $this->rp->disableAssociations();
        } catch (\Exception $exception) {
            $anExceptionWasThrown = true;
        }

        $this->assertFalse($anExceptionWasThrown);
    }

    public function testSetClockSkew()
    {
        $this->rp->setClockSkew(50);
        $this->assertEquals(50, $this->rp->getClockSkew());
    }

    public function testPrepareFail()
    {
        $this->expectException(OpenIdException::class);
        $rp = new RelyingParty($this->returnTo, $this->realm);
        $rp->prepare();
    }

    public function testPrepareRequestOptions()
    {
        $mock = new \Pear\Http\Request2\Adapters\Mock();
        //yadis application/xrds+xml request
        $mock->addResponse(
            "HTTP/1.1 200 OK\r\n"
            . "Connection: close\r\n"
            . "\r\n"
            . "Explicit response for example.org",
            OpenID::normalizeIdentifier($this->id)
        );
        //HTML: GET
        $mock->addResponse(
            "HTTP/1.1 200 OK\r\n"
            . "Connection: close\r\n"
            . "\r\n"
            . "<html><head><link rel='openid.server' href='http://id.example.org/'/>'",
            OpenID::normalizeIdentifier($this->id)
        );

        $this->rp = new RelyingParty($this->returnTo, $this->realm, $this->id);
        $options = $this->rp->getRequestOptions();
        $options['adapter'] = $mock;

        $this->rp->setRequestOptions($options);
        $auth = $this->rp->prepare();
        $this->assertInstanceOf(OpenIdAuthRequest::class, $auth);
    }

    public function testGetAssociationFail()
    {
        $this->setStoreMethods(true);

        $assocRequest = $this->getMockBuilder(Request::class)
            ->onlyMethods(['associate'])
            ->setConstructorArgs([$this->opEndpointURL, OpenId::SERVICE_2_0_SERVER])
            ->getMock();

        $assocRequest->expects($this->once())
            ->method('associate')
            ->will($this->returnValue($this->association));

        $this->rp->expects($this->once())
            ->method('getAssociationRequestObject')
            ->will($this->returnValue($assocRequest));

        $this->assertInstanceOf(OpenIdAuthRequest::class, $this->rp->prepare());
    }

    public function testGetAssociation()
    {
        $this->setStoreMethods(true);

        $assocRequest = $this->getMockBuilder(Request::class)
            ->onlyMethods(['associate'])
            ->setConstructorArgs([$this->opEndpointURL, OpenId::SERVICE_2_0_SERVER])
            ->getMock();

        $assocRequest->expects($this->once())
            ->method('associate')
            ->will($this->returnValue(false));

        $this->rp->expects($this->once())
            ->method('getAssociationRequestObject')
            ->will($this->returnValue($assocRequest));

        $this->assertInstanceOf(OpenIdAuthRequest::class, $this->rp->prepare());
    }

    public function testGetAssociationRequestObject()
    {
        $rp = new RelyingPartyMock($this->returnTo, $this->realm, $this->id);

        $a = $rp->returnGetAssociationRequestObject($this->opEndpointURL, OpenID::SERVICE_2_0_SERVER);
        $this->assertInstanceOf(Request::class, $a);
    }

    /**
     * Converts an OpenIdMessage instance to a Net_URL2 instance based on
     * $this->returnTo.  This was added to ease the transition from the old
     * verify() signature to the new one.
     *
     * @param OpenIdMessage $message Instance of OpenIdMessage
     * @return Url2
     * @throws \Pear\OpenId\Exceptions\OpenIdMessageException
     */
    protected function messageToNetURL2(OpenIdMessage $message)
    {
        return new Url2($this->returnTo . '?' . $message->getHTTPFormat());
    }

    public function testVerifyCancel()
    {
        $message = new OpenIdMessage();
        $message->set('openid.mode', OpenID::MODE_CANCEL);

        $result = $this->rp->verify($this->messageToNetURL2($message), $message);
        $this->assertInstanceOf(OpenIdAssertionResult::class, $result);
        $this->assertFalse($result->success());
        $this->assertSame(OpenID::MODE_CANCEL, $result->getAssertionMethod());
    }

    public function testVerifyOneOneImmediateFail()
    {
        $url = 'http://examplerp.com/';
        $message = new OpenIdMessage();
        $message->set('openid.mode', OpenID::MODE_ID_RES);
        $message->set('openid.user_setup_url', $url);

        $result = $this->rp->verify($this->messageToNetURL2($message), $message);
        $this->assertInstanceOf(OpenIdAssertionResult::class, $result);
        $this->assertFalse($result->success());
        $this->assertSame(OpenID::MODE_ID_RES, $result->getAssertionMethod());
        $this->assertSame($url, $result->getUserSetupURL());
    }

    public function testVerifyError()
    {
        $this->expectException(OpenIdException::class);
        $message = new OpenIdMessage();
        $message->set('openid.mode', OpenID::MODE_ERROR);

        $this->rp->verify($this->messageToNetURL2($message), $message);
    }

    public function testVerifyInvalidMode()
    {
        $this->expectException(OpenIdException::class);
        $message = new OpenIdMessage();
        $message->set('openid.mode', 'foo');

        $this->rp->verify($this->messageToNetURL2($message), $message);
    }

    public function testVerifyAssociation()
    {
        $this->store->expects($this->any())
            ->method('getDiscover')
            ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
            ->method('getAssociation')
            ->will($this->returnValue($this->association));

        $this->association->expects($this->once())
            ->method('checkMessageSignature')
            ->will($this->returnValue(true));

        $nonceObj = new Nonce($this->opEndpointURL);
        $nonce = $nonceObj->createNonce();

        $message = new OpenIdMessage();
        $message->set('openid.mode', 'id_res');
        $message->set('openid.ns', OpenID::NS_2_0);
        $message->set('openid.return_to', $this->returnTo);
        $message->set('openid.claimed_id', $this->id);
        $message->set('openid.identity', $this->id);
        $message->set('openid.op_endpoint', $this->opEndpointURL);
        $message->set('openid.assoc_handle', '12345qwerty');
        $message->set('openid.response_nonce', $nonce);

        $this->assertInstanceOf(OpenIdAssertionResult::class, $this->rp->verify($this->messageToNetURL2($message), $message));
    }

    public function testVerifyUnsolicited()
    {
        $this->setStoreMethods();

        $authMessage = new OpenIdMessage;
        $authMessage->set('is_valid', 'true');

        $assertion = $this->getMockBuilder(Assertion::class)
            ->onlyMethods(['checkAuthentication'])
            ->disableOriginalConstructor()
            ->getMock();

        $assertion->expects($this->any())
            ->method('checkAuthentication')
            ->will($this->returnValue($authMessage));

        $rp = $this->getMockBuilder(RelyingParty::class)
            ->setConstructorArgs([$this->returnTo, $this->realm])
            ->onlyMethods(['getAssociationRequestObject', 'getAssertionObject'])
            ->getMock();

        $rp->expects($this->any())
            ->method('getAssertionObject')
            ->will($this->returnValue($assertion));

        $this->association->expects($this->any())
            ->method('checkMessageSignature')
            ->will($this->returnValue(true));

        $nonceObj = new Nonce($this->opEndpointURL);
        $nonce = $nonceObj->createNonce();

        $message = new OpenIdMessage();
        $message->set('openid.mode', 'id_res');
        $message->set('openid.ns', OpenID::NS_2_0);
        $message->set('openid.return_to', $this->returnTo);
        $message->set('openid.claimed_id', $this->id);
        $message->set('openid.identity', $this->id);
        $message->set('openid.op_endpoint', $this->opEndpointURL);
        $message->set('openid.assoc_handle', '12345qwerty');
        $message->set('openid.response_nonce', $nonce);

        $verify = $rp->verify($this->messageToNetURL2($message), $message);

        $this->assertInstanceOf(OpenIdAssertionResult::class, $rp->verify($this->messageToNetURL2($message), $message));
        $this->assertTrue($verify->success());
    }

    public function testVerifyCheckAuthentication()
    {
        $this->store->expects($this->any())
            ->method('getDiscover')
            ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
            ->method('getNonce')
            ->will($this->returnValue(false));

        $nonceObj = new Nonce($this->opEndpointURL);
        $nonce = $nonceObj->createNonce();

        $message = new OpenIdMessage();
        $message->set('openid.mode', 'id_res');
        $message->set('openid.ns', OpenID::NS_2_0);
        $message->set('openid.return_to', $this->returnTo);
        $message->set('openid.claimed_id', $this->id);
        $message->set('openid.identity', $this->id);
        $message->set('openid.op_endpoint', $this->opEndpointURL);
        $message->set('openid.invalidate_handle', '12345qwerty');
        $message->set('openid.response_nonce', $nonce);

        $assertion = $this->getMockBuilder(Assertion::class)
            ->onlyMethods(['checkAuthentication'])
            ->setConstructorArgs([$message, new Url2($this->returnTo)])
            ->getMock();

        $authMessage = new OpenIdMessage;
        $authMessage->set('is_valid', 'true');

        $assertion->expects($this->once())
            ->method('checkAuthentication')
            ->will($this->returnValue($authMessage));

        $this->rp->expects($this->once())
            ->method('getAssertionObject')
            ->will($this->returnValue($assertion));

        $this->assertInstanceOf(OpenIdAssertionResult::class, $this->rp->verify($this->messageToNetURL2($message), $message));
    }

    public function testGetAssertionObject()
    {
        $this->store->expects($this->once())
            ->method('getDiscover')
            ->will($this->returnValue($this->discover));
        $this->store->expects($this->once())
            ->method('getNonce')
            ->will($this->returnValue(false));

        $nonceObj = new Nonce($this->opEndpointURL);
        $nonce = $nonceObj->createNonce();

        $message = new OpenIdMessage();
        $message->set('openid.mode', 'id_res');
        $message->set('openid.ns', OpenID::NS_2_0);
        $message->set('openid.return_to', $this->returnTo);
        $message->set('openid.claimed_id', $this->id);
        $message->set('openid.identity', $this->id);
        $message->set('openid.op_endpoint', $this->opEndpointURL);
        $message->set('openid.invalidate_handle', '12345qwerty');
        $message->set('openid.response_nonce', $nonce);

        $rp = new RelyingPartyMock($this->id, $this->returnTo, $this->realm);
        $this->assertInstanceOf(Assertion::class, $rp->returnGetAssertionObject($message, new Url2($this->returnTo)));
    }
}
