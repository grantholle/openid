<?php

namespace Tests\Extension;

use Pear\OpenId\Exceptions\OpenIdExtensionException;
use Pear\OpenId\Extensions\AX;
use Pear\OpenId\Extensions\OpenIdExtension;
use PHPUnit\Framework\TestCase;

/**
 * OpenID_Extension_AXTest
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
 * OpenID_Extension_AXTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class AXTest extends TestCase
{
    protected $ax = null;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->ax = new AX(OpenIdExtension::REQUEST);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->ax = null;
    }

    public function testSetFailInvalidMode()
    {
        $this->expectException(OpenIdExtensionException::class);
        $this->ax->set('mode', 'foo');
    }

    public function testSetFailInvalidURI()
    {
        $this->expectException(OpenIdExtensionException::class);
        $this->ax->set('type.foo', 'http:///example.com');
    }

    public function testSetSuccess()
    {
        $this->assertInstanceOf(AX::class, $this->ax->set('foo', 'bar'));
    }
}
