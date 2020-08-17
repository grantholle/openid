<?php

namespace Tests\Extension;

use Pear\OpenId\Exceptions\OpenIdExtensionException;
use Pear\OpenId\Extensions\OpenIdExtension;
use Pear\OpenId\Extensions\SREG11;
use PHPUnit\Framework\TestCase;

/**
 * OpenID_Extension_SREGTest
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
 * OpenID_Extension_SREGTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class SREGTest extends TestCase
{
    protected $sreg = null;

    public function setUp(): void
    {
        $this->sreg = new SREG11(OpenIdExtension::REQUEST);
    }

    public function tearDown(): void
    {
        $this->sreg = null;
    }

    public function testSetFailInvalidKey()
    {
        $this->expectException(OpenIdExtensionException::class);
        $this->sreg->set('foo', 'bar');
    }

    public function testSetFailInvalidKeyResponse()
    {
        $this->expectException(OpenIdExtensionException::class);
        $this->sreg = new SREG11(OpenIdExtension::RESPONSE);
        $this->sreg->set('foo', 'bar');
    }

    public function testSetSuccess()
    {
        $this->assertInstanceOf(SREG11::class, $this->sreg->set('required', 'nickname'));
    }
}
