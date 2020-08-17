<?php

namespace Tests\Extension;

use Pear\OpenId\Exceptions\OpenIdExtensionException;
use Pear\OpenId\Extensions\OpenIdExtension;
use Pear\OpenId\Extensions\UI;
use PHPUnit\Framework\TestCase;

/**
 * OpenID_Extension_UITest
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
 * OpenID_Extension_UITest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class UITest extends TestCase
{
    protected $ui = null;

    public function setUp(): void
    {
        $this->ui = new UI(OpenIdExtension::REQUEST);
    }

    public function tearDown(): void
    {
        $this->ui = null;
    }

    public function testSetFailInvalidMode()
    {
        $this->expectException(OpenIdExtensionException::class);
        $this->ui->set('mode', 'foo');
    }

    public function testSetSuccess()
    {
        $this->assertInstanceOf(UI::class, $this->ui->set('foo', 'bar'));
    }
}
