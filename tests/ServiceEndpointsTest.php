<?php

namespace Tests;

use Pear\OpenId\ServiceEndpoint;
use Pear\OpenId\ServiceEndpoints;
use PHPUnit\Framework\TestCase;

/**
 * OpenID_ServiceEndpointsTest
 *
 * PHP version 5.2.0+
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Test class for the OpenID_ServiceEndpoints class
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class ServiceEndpointsTest extends TestCase
{
    /**
     * @var ServiceEndpoints
     */
    protected $object;

    /**
     * Dummy identifier to use for testing
     *
     * @var string
     */
    protected $identifier = 'http://id.myopenidprovider.com';

    /**
     * A valid service endpoint object
     *
     * @var ServiceEndpoint
     */
    protected $goodService = null;

    /**
     * An invalid service endpoint object
     *
     * @var ServiceEndpoint
     */
    protected $badService = null;

    protected function setUp(): void
    {
        $this->object = new ServiceEndpoints($this->identifier);
        $this->badService = new ServiceEndpoint();
        $this->goodService = new ServiceEndpoint();
        $this->goodService->setURIs([$this->identifier]);
    }

    protected function tearDown(): void
    {
        unset($this->object);
        unset($this->badService);
        unset($this->goodService);
    }

    public function testConstructorNoEndpoint()
    {
        $this->assertInstanceOf(ServiceEndpoints::class, $this->object);
        $this->assertEquals($this->identifier, $this->object->getIdentifier());
    }

    public function testConstructorWithEndpoint()
    {
        $services = new ServiceEndpoints($this->identifier, $this->badService);

        $this->assertInstanceOf(ServiceEndpoints::class, $services);
        $this->assertEquals($this->identifier, $services->getIdentifier());
    }

    public function testAddServiceFail()
    {
        $this->assertNull($this->object[0]);
        $this->object->addService($this->badService);
        $this->assertNull($this->object[0]);
    }

    public function testAddServiceSuccess()
    {
        $this->object->addService($this->goodService);
        $this->assertInstanceOf(ServiceEndpoint::class, $this->object[0]);
        $this->assertEquals($this->goodService, $this->object[0]);
    }

    public function testGetIterator()
    {
        $this->object->addService($this->goodService);
        $iterator = $this->object->getIterator();

        $this->assertInstanceOf('ArrayIterator', $iterator);
        $this->assertTrue($iterator->valid());
        $this->assertInstanceOf(ServiceEndpoint::class, $iterator->current());
        $this->assertEquals($this->goodService, $iterator->current());
        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    public function testOffsetSetAndUnset()
    {
        $index = 5;

        $this->assertNull($this->object[$index]);

        $this->object[$index] = $this->goodService;

        $this->assertInstanceOf(ServiceEndpoint::class, $this->object[$index]);
        $this->assertEquals($this->goodService, $this->object[$index]);

        unset($this->object[$index]);

        $this->assertNull($this->object[$index]);
    }

    public function testCount()
    {
        $this->object->addService($this->goodService);
        $this->object->addService($this->goodService);
        $this->object->addService($this->goodService);
        $this->object->addService($this->goodService);

        $count = count($this->object);

        $this->assertEquals(4, $count);
    }
}
