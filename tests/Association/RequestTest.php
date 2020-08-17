<?php

namespace Tests\Association;

use Pear\Crypt\DiffieHellman;
use Pear\Http\Request2;
use Pear\Http\Request2\Adapters\Mock;
use Pear\OpenId\Associations\Association;
use Pear\OpenId\Associations\Request;
use Pear\OpenId\Exceptions\OpenIdAssociationException;
use Pear\OpenId\OpenId;
use Pear\OpenId\OpenIdMessage;
use PHPUnit\Framework\TestCase;

/**
 * Request()Test
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
 * Request()Test
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
    protected $URI = 'https://example.com';
    protected $handle = '1234567890';
    protected $sessionType = null;
    protected $httpRequest = null;
    protected $httpMock = null;
    protected $assocRequest = null;
    protected $rpDH = null;
    protected $opDH = null;
    protected $message = null;
    protected $macKey = '12345';

    public function setUp(): void
    {
        $this->sessionType = 'sha256';

        $this->message = new OpenIdMessage();
        $this->message->set('ns', OpenId::NS_2_0);
        $this->message->set('session_type', 'sha256');
        $this->message->set('assoc_handle', $this->handle);
        $this->message->set('expires_in', '10');

        $this->rpDH = new DiffieHellman(563, 5, 9);
        $this->rpDH->generateKeys();
        $this->opDH = new DiffieHellman(563, 5, 13);
        $this->opDH->generateKeys();

        $this->httpRequest = new Request2();
        $this->httpMock = new Mock();
        $this->httpRequest->setAdapter($this->httpMock);

        $this->assocRequest = new Request($this->URI, OpenId::SERVICE_2_0_SERVER, $this->rpDH);
//            [OpenId::getHTTPRequest2Instance()],
//            [
//                $this->URI,
//                OpenId::SERVICE_2_0_SERVER,
//                $this->rpDH
//            ]
//        );

//        $this->assocRequest
//            ->expects($this->any())
//            ->method('getHTTPRequest2Instance')
//            ->will($this->returnValue($this->httpRequest));

        $this->message->set(
            'dh_server_public',
            base64_encode($this->opDH->getPublicKey(DiffieHellman::BTWOC))
        );
    }

    public function tearDown(): void
    {
        $array = [
            'httpRequest',
            'assocRequest',
            'sessionType',
            'opDH',
            'rpDH',
            'message'
        ];

        foreach ($array as $item) {
            $this->{$item} = null;
        }
    }

    public function testAssociate()
    {
        // generate mac key
        $assocType = str_replace('HMAC-', '', $this->assocRequest->getAssociationType());
        $xorSecret = $this->xorSecret($this->rpDH->getPublicKey(), $this->macKey, $assocType);
        $this->message->set('enc_mac_key', base64_encode($xorSecret));
        $this->setResponse();

        $this->assertInstanceOf(Association::class, $this->assocRequest->associate());
    }

    public function testDefaultDH()
    {
//        $this->assocRequest = $this->createMock(
//            Request::class,
//            [OpenId::getHTTPRequest2Instance()],
//            [$this->URI, OpenId::SERVICE_2_0_SERVER]
//        );
//
//        $this->assocRequest->expects($this->any())
//            ->method('getHTTPRequest2Instance')
//            ->will($this->returnValue($this->httpRequest));

        $this->testAssociate();
    }

    /**
     * xorSecret
     *
     * @param string $pubKey Public key
     * @param string $secret Secret
     * @param string $algo Algorithm
     * @return string The mac_key
     * @throws DiffieHellman\DiffieHellmanException
     */
    protected function xorSecret(string $pubKey, string $secret, string $algo)
    {
        $this->opDH->computeSecretKey($pubKey, DiffieHellman::BINARY);
        $sharedSecret = $this->opDH
            ->getSharedSecretKey(DiffieHellman::BTWOC);
        $bytes = mb_strlen(bin2hex($secret), '8bit') / 2;
        $hash_dh_shared = hash($algo, $sharedSecret, true);

        $xsecret = '';

        for ($i = 0; $i < $bytes; $i++) {
            $xsecret .= chr(ord($secret[$i]) ^ ord($hash_dh_shared[$i]));
        }

        return $xsecret;
    }

    public function testGetResponse()
    {
        $this->message->set('enc_mac_key', 'foo');
        $this->setResponse();

        $this->assocRequest->associate();
        $this->assertSame($this->message->getArrayFormat(),
            $this->assocRequest->getResponse());
    }

    public function testConstructFail()
    {
        $this->expectException(OpenIdAssociationException::class);
        $ar = new Request($this->URI, 'http://example.com');
    }

    public function testConstructWithOpenID1()
    {
        $ar = new Request($this->URI, OpenId::SERVICE_1_1_SIGNON);
        $this->assertInstanceOf(Request::class, $ar);
    }

    public function testGetOPEndpointURL()
    {
        $this->assertSame($this->URI, $this->assocRequest->getEndpointURL());
    }

    public function testAssociateMultipleRequests()
    {
        $this->message = new OpenIdMessage();
        $this->message->set('ns', OpenId::NS_2_0);
        $this->message->set('mode', OpenId::MODE_ERROR);
        $this->message->set('error_code', 'unsupported-type');
        $this->message->set('session_type', OpenId::SESSION_TYPE_NO_ENCRYPTION);
        $this->message->set('dh_server_public', base64_encode($this->opDH->getPublicKey(DiffieHellman::BTWOC)));

        $this->setResponse();

        $this->assocRequest->associate();
    }

    public function testBuildAssociationNoEncryption()
    {
        $this->message->set('mac_key', $this->macKey);
        $this->assocRequest
            ->setSessionType(OpenId::SESSION_TYPE_NO_ENCRYPTION);

        $this->setResponse();

        $this->assocRequest->associate();
    }

    public function testBuildAssociationFailNoPublicKey()
    {
        $this->expectException(OpenIdAssociationException::class);
        $this->message->delete('dh_server_public');
        $this->setResponse();

        $this->assocRequest->associate();
    }

    public function testBuildAssociationFailNoMacKey()
    {
        $this->expectException(OpenIdAssociationException::class);
        $this->assocRequest
            ->setSessionType(OpenId::SESSION_TYPE_NO_ENCRYPTION);

        $this->setResponse();
        $this->assocRequest->associate();
    }

    public function testSetSessionTypeFailNoEncryption()
    {
        $this->expectException(OpenIdAssociationException::class);
        $this->URI = 'http://example.com';
        $this->setUP();
        $this->testAssociateMultipleRequests();
    }

    public function testSetSessionTypeFailInvalidType()
    {
        $this->expectException(OpenIdAssociationException::class);
        $this->assocRequest->setSessionType('foo');
    }

    public function testSetAssociationTypeFail()
    {
        $this->expectException(OpenIdAssociationException::class);
        $this->assocRequest->setAssociationType('foo');
    }

    public function testAssociateMultipleRequestsSha1()
    {
        $this->message = new OpenIdMessage();
        $this->message->set('ns', OpenId::NS_2_0);
        $this->message->set('mode', OpenId::MODE_ERROR);
        $this->message->set('error_code', 'unsupported-type');
        $this->message->set('session_type', OpenId::SESSION_TYPE_DH_SHA1);
        $this->message->set('assoc_type', OpenId::ASSOC_TYPE_HMAC_SHA1);
        $this->message->set('dh_server_public', base64_encode($this->opDH->getPublicKey(DiffieHellman::BTWOC)));

        $this->setResponse();
        $this->assocRequest->associate();
    }

    protected function setResponse()
    {
        $response = "HTTP/1.1 200 OK\nContent-Type: text/html\n\n"
            . $this->message->getKVFormat();
        $this->httpMock->addResponse($response);
    }
}
