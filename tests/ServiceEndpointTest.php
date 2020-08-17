<?php

namespace Tests;

use Pear\OpenId\Discover\HTML;
use Pear\OpenId\ServiceEndpoint;
use PHPUnit\Framework\TestCase;

/**
 * OpenID_ServiceEndpointTest
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
 * Test class for the OpenID_ServiceEndpoint class
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class ServiceEndpointTest extends TestCase
{
    /**
     * @var ServiceEndpoint
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new ServiceEndpoint;
    }

    protected function tearDown(): void
    {
        unset($this->object);
    }

    public function testIsValidFalse()
    {
        $isValid = $this->object->isValid();

        $this->assertFalse($isValid);
    }

    public function testSetInvalidURI()
    {
        $invalid = [
            'thisiswrong'
        ];

        $uris = $this->object->getURIs();
        $this->assertEquals([], $uris);

        $this->object->setURIs($invalid);

        $uris = $this->object->getURIs();
        $this->assertEquals([], $uris);
    }

    public function testGetSetURIs()
    {
        $testURIs = [
            'http://example.com',
            'http://myopenid.com'
        ];

        $uris = $this->object->getURIs();
        $this->assertEquals([], $uris);

        $this->object->setURIs($testURIs);

        $uris = $this->object->getURIs();
        $this->assertEquals($testURIs, $uris);
    }

    public function testGetSetTypes()
    {
        $testTypes = [
            'foo',
            'bar'
        ];

        $types = $this->object->getTypes();
        $this->assertEquals([], $types);

        $this->object->setTypes($testTypes);

        $types = $this->object->getTypes();
        $this->assertEquals($testTypes, $types);
    }

    public function testGetSetLocalID()
    {
        $testLocalID = 'foobar';

        $localID = $this->object->getLocalID();
        $this->assertNull($localID);

        $this->object->setLocalID($testLocalID);

        $localID = $this->object->getLocalID();
        $this->assertEquals($testLocalID, $localID);
    }

    public function testGetSetSource()
    {
        $testSource = HTML::class;

        $source = $this->object->getSource();
        $this->assertNull($source);

        $this->object->setSource($testSource);

        $source = $this->object->getSource();
        $this->assertEquals($testSource, $source);
    }

    public function testGetSetVersion()
    {
        $testVersion = 'http://specs.openid.net/auth/2.0/server';

        $version = $this->object->getVersion();
        $this->assertNull($version);

        $this->object->setVersion($testVersion);

        $version = $this->object->getVersion();
        $this->assertEquals($testVersion, $version);
    }

    public function testIsValidTrue()
    {
        $this->object->setURIs(['http://example.com']);

        $isValid = $this->object->isValid();

        $this->assertTrue($isValid);
    }
}
