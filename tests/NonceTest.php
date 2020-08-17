<?php

namespace Tests;

use Pear\OpenId\Nonce;
use Pear\OpenId\OpenId;
use PHPUnit\Framework\TestCase;
use Tests\Store\StoreMock;

/**
 * NonceTest
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
 * NonceTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class NonceTest extends TestCase
{
    protected $skew  = 600;
    protected $opURL = 'http://exampleop.com';
    protected $nonce = null;

    public function setUp(): void
    {
        $this->nonce = new Nonce($this->opURL, $this->skew);
    }

    public function tearDown(): void
    {
        $this->nonce = null;
    }

    public function testValidate()
    {
        $nonce = gmstrftime('%Y-%m-%dT%H:%M:%SZ', time()). '12345abcde';
        $this->assertTrue($this->nonce->validate($nonce));
    }

    public function testValidateFail()
    {
        $this->assertFalse($this->nonce->validate('foo'));
        $nonce = gmstrftime('%Y-%m-%dT%H:%M:%SZ',
                            time() - ($this->skew + 100)) . '12345abcde';
        $this->assertFalse($this->nonce->validate($nonce));
        $nonce = '5000-13-47T50:70:70Z&&&&&';
        $this->assertFalse($this->nonce->validate($nonce));
    }

    public function testVerifyResponseNonce()
    {
        $store = $this->createMock(StoreMock::class);

        OpenId::setStore($store);
        $this->nonce = new Nonce($this->opURL, $this->skew);

        $store->expects($this->any())
            ->method('getNonce')
            ->will($this->returnValue(false));

        $nonce = gmstrftime('%Y-%m-%dT%H:%M:%SZ', time()) . '12345abcde';
        $this->assertTrue($this->nonce->verifyResponseNonce($nonce));
    }

    public function testVerifyResponseNonceFail()
    {
        $store = $this->createMock(StoreMock::class);

        OpenId::setStore($store);
        $this->nonce = new Nonce($this->opURL, $this->skew);

        $store->expects($this->any())
            ->method('getNonce')
            ->will($this->returnValue(true));

        $nonce = gmstrftime('%Y-%m-%dT%H:%M:%SZ', time()). '12345abcde';
        $this->assertFalse($this->nonce->verifyResponseNonce($nonce));
    }

    public function testCreateNonce()
    {
        $nonce = $this->nonce->createNonce(4, time());
        $this->assertTrue($this->nonce->validate($nonce));

        $nonce = $this->nonce->createNonce(0, time());
        $this->assertTrue($this->nonce->validate($nonce));
    }

    public function testCreateNonceAndStore()
    {
        $store = $this->createMock(StoreMock::class);
        OpenId::setStore($store);
        $this->nonce = new Nonce($this->opURL, $this->skew);
        $nonce = $this->nonce->createNonceAndStore();
        $this->assertTrue($this->nonce->validate($nonce));
    }

    public function testValidateFailTooLong()
    {
        $this->nonce = new Nonce($this->opURL, $this->skew);
        $nonce = $this->nonce->createNonce('300');
        $this->assertFalse($this->nonce->validate($nonce));
    }
}

