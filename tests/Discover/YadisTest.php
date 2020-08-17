<?php

namespace Tests\Discover;

use Pear\Http\Request2\Response;
use Pear\OpenId\ServiceEndpoints;
use Pear\Services\Yadis\Xrds\XrdsNamespace;
use Pear\Services\Yadis\Xrds\XrdsService;
use Pear\Services\Yadis\Yadis;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

require_once "/Users/grant/Projects/pear-openid/vendor/grantholle/pear-services-yadis/src/Xrds/Xrds.php";

/**
 * OpenID_Discover_YadisTest
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
 * OpenID_Discover_YadisTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class YadisTest extends TestCase
{
    protected $sy      = null;
    protected $object  = null;
    protected $response = null;

    public function setUp(): void
    {
        $this->response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs(['', false])
            ->getMock();

        $this->sy = $this->getMockBuilder(Yadis::class)
            ->onlyMethods(['discover', 'getYadisId', 'getHTTPResponse'])
            ->getMock();
        $this->sy
            ->expects($this->any())
            ->method('getHTTPResponse')
            ->willReturn($this->response);

        $this->object = $this->getMockBuilder(\Pear\OpenId\Discover\Yadis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getServicesYadis'])
            ->getMock();
        $this->object
            ->expects($this->any())
            ->method('getServicesYadis')
            ->willReturn($this->sy);
    }

    protected function tearDown(): void
    {
        $this->sy = null;
        $this->object = null;
        $this->response = null;
    }

    public function testDiscoverSuccess()
    {
        $file = file_get_contents(__DIR__ . '/xrds.xml');
        $xrds = new SimpleXMLElement($file);
        $ns = new XrdsNamespace();
        $services = new XrdsService($xrds, $ns);

        $this->sy->expects($this->any())
            ->method('discover')
            ->willReturn($services);

        $serviceEndpoints = $this->object->discover();
        $this->assertInstanceOf(ServiceEndpoints::class, $serviceEndpoints);
    }

    public function testDiscoverSuccess2()
    {
        $file     = file_get_contents(dirname(__FILE__) . '/xrds2.xml');
        $xrds     = new SimpleXMLElement($file);
        $ns       = new XrdsNamespace();
        $services = new XrdsService($xrds, $ns);

        $this->sy->expects($this->any())
                 ->method('discover')
                 ->will($this->returnValue($services));

        $serviceEndpoints = $this->object->discover();
        $this->assertInstanceOf(ServiceEndpoints::class, $serviceEndpoints);
    }

    /**
     * testDiscoverFail
     *
     * @return void
     */
    public function testDiscoverFail()
    {
        $services = $this->getMock('Services_Yadis_Xrds_Service',
                                   array('valid'),
                                   array(),
                                   '',
                                   false);
        $services->expects($this->any())
                 ->method('valid')
                 ->will($this->returnValue(false));

        $this->sy->expects($this->any())
                 ->method('discover')
                 ->will($this->returnValue($services));

        $serviceEndpoints = $this->object->discover();
        $this->assertFalse($serviceEndpoints);
    }

    /**
     * testGetServicesYadis
     *
     * @return void
     */
    public function testGetServicesYadis()
    {
        $sy = new OpenID_Discover_Yadis('http://example.com');
        $this->assertInstanceOf('Services_Yadis', $sy->getServicesYadis());
    }
}
?>
