<?php

namespace Tests\Discover;

use Pear\Services\Yadis\Yadis;
use PHPUnit\Framework\TestCase;

/**
 * OpenID_Discover_MockYadisTest
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
 * OpenID_Discover_MockYadisTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class MockYadisTest extends TestCase
{
    protected $yadis  = null;
    protected $object = null;

    public function setUp(): void
    {
        $this->yadis = $this->createMock(Yadis::class);

        MockYadis::$servicesYadisInstance = $this->yadis;

        $this->object = new Yadis('http://example.com');
    }

    protected function tearDown(): void
    {
        $this->yadis  = null;
        $this->object = null;
    }

    /**
     * testDiscoverFailNotValid
     *
     * @return void
     */
    public function testDiscoverFailNotValid()
    {
        $this->yadis->expects($this->any())
            ->method('valid')
            ->will($this->returnValue(false));

        $this->assertFalse($this->object->discover());
    }
}
