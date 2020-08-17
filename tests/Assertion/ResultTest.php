<?php

namespace Tests\Assertion;

use Pear\OpenId\Assertions\OpenIdAssertionResult;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Exceptions\OpenIdAssertionException;
use Pear\OpenId\OpenId;
use Pear\OpenId\OpenIdMessage;
use PHPUnit\Framework\TestCase;

/**
 * OpenIdAssertionResultTest
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
 * OpenIdAssertionResultTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class ResultTest extends TestCase
{
    protected $result = null;

    public function setUp(): void
    {
        $this->result = new OpenIdAssertionResult;
    }

    public function tearDown(): void
    {
        $this->result = null;
    }

    public function testSetAndGetCheckAuthResponse()
    {
        $message = new OpenIdMessage();
        $message->set('openid.id_res', 'true');
        $this->result->setCheckAuthResponse($message);
        $this->assertSame($message, $this->result->getCheckAuthResponse());
    }

    public function testSuccess()
    {
        $this->result->setAssertionResult(true);
        $this->assertTrue($this->result->success());
        $this->result->setAssertionResult(false);
        $this->assertFalse($this->result->success());
    }

    public function testSetAndGetAssertionMethod()
    {
        $this->result->setAssertionMethod(OpenId::MODE_ASSOCIATE);
        $this->assertSame(OpenId::MODE_ASSOCIATE, $this->result->getAssertionMethod());

        $this->result->setAssertionMethod(OpenId::MODE_ASSOCIATE);
        $this->assertSame(OpenId::MODE_ASSOCIATE, $this->result->getAssertionMethod());
    }

    public function testSetAssertionMethodFail()
    {
        $this->expectException(OpenIdAssertionException::class);
        $this->result->setAssertionMethod('foo');
    }

    public function testSetGetUserSetupURL()
    {
        $url = 'http://example.com';
        $this->result->setUserSetupURL($url);
        $this->assertSame($url, $this->result->getUserSetupURL());
    }

    public function testSetGetDiscover()
    {
        $this->assertNull($this->result->getDiscover());
        $discover = new Discover('http://example.com');
        $this->result->setDiscover($discover);
        $this->assertSame($discover, $this->result->getDiscover());
    }
}
