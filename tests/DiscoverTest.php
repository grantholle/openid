<?php

namespace Tests;

use Pear\OpenId\Discover\Discover;
use Pear\OpenId\OpenId;
use Pear\OpenId\ServiceEndpoint;
use Pear\OpenId\ServiceEndpoints;
use PHPUnit\Framework\TestCase;
use Tests\Discover\Mock;
use Tests\Discover\MockFail;
use Tests\Discover\MockNoInterface;
use Tests\Discover\MockSubClass;
use Tests\Extension\MockExtension;
use Tests\Store\StoreMock;

/**
 * OpenID_DiscoverTest
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
 * OpenID_DiscoverTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class DiscoverTest extends TestCase
{
    protected $discover = null;
    protected $id = 'http://user.example.com';

    public function setUp(): void
    {
        $this->discover = new Discover($this->id);
    }

    public function testSetRequestOptions()
    {
        $options = ['allowRedirects' => true];
        $this->assertInstanceOf(Discover::class, $this->discover->setRequestOptions($options));
    }

    public function testGetFail()
    {
        $this->assertSame(null, $this->discover->foobar);
    }

    public function testDiscoverFail()
    {
        $oldTypes = Discover::$discoveryOrder;

        Discover::$discoveryOrder = [MockFail::class];

        $discover = new Discover('http://yahoo.com');
        $this->assertFalse($discover->discover());

        Discover::$discoveryOrder = $oldTypes;
    }

    public function testDiscoverFactoryFailNoClassOrNoInterface()
    {
        Discover::$discoveryOrder = [MockNoInterface::class];

        $discover = new Discover('http://yahoo.com');
        $this->assertFalse($discover->discover());
    }

    public function testGetDiscover()
    {
        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs(['http://op.example.com']);
        $opEndpoint->setVersion(OpenId::SERVICE_2_0_SERVER);

        Mock::$opEndpoint = $opEndpoint;

        Discover::$discoveryOrder = [Mock::class];

        $store = $this->getMockBuilder(StoreMock::class)
            ->onlyMethods(['getDiscover'])
            ->getMock();

        $store->expects($this->once())
            ->method('getDiscover')
            ->willReturn(false);

        $this->assertInstanceOf(Discover::class, Discover::getDiscover('http://yahoo.com', $store));
    }

    public function testExtensionSupportedSuccess()
    {
        $endpoints = new ServiceEndpoints('http://example.com');
        $service   = new ServiceEndpoint();
        $service->setURIs(['http://example.com']);
        $service->setTypes(['http://example.com/mock']);
        $endpoints->addService($service);

        $discover = new MockSubClass('http://example.com');
        $discover->setServices($endpoints);
        $this->assertTrue($discover->extensionSupported(MockExtension::class));
    }

    /**
     * testExtensionSupportedFailure
     *
     * @return void
     */
    public function testExtensionSupportedFailure()
    {
        $endpoints = new ServiceEndpoints('http://example.com');
        $service   = new ServiceEndpoint();
        $service->setURIs(['http://example.com']);
        $endpoints->addService($service);

        $discover = new MockSubClass('http://example.com');
        $discover->setServices($endpoints);
        $this->assertFalse($discover->extensionSupported(MockExtension::class));
    }
}
