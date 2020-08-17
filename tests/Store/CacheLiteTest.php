<?php

namespace Tests\Store;

use Pear\OpenId\Associations\Association;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Nonce;
use Pear\OpenId\Store\CacheLite;
use PHPUnit\Framework\TestCase;

/**
 * CacheLiteTest
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
 * CacheLiteTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class CacheLiteTest extends TestCase
{
    /**
     * cache
     *
     * @var CacheLite
     */
    protected $cache = null;

    /**
     * Determines the location of the temporary cache directory
     *
     * @return string
     */
    protected function getCacheDir()
    {
        return '/tmp/' . time();
    }

    public function setUp(): void
    {
        $options = [
            'cacheDir' => $this->getCacheDir()
        ];

        $this->cache = new CacheLite($options);
    }

    public function tearDown(): void
    {
        $this->cache = null;
        shell_exec('rm -rf ' . $this->getCacheDir());
    }

    public function testAssociations()
    {
        $uri = 'http://exampleop.com';

        $args = [
            'uri' => $uri,
            'expiresIn' => 14400,
            'created' => time(),
            'assocType' => 'HMAC-SHA256',
            'assocHandle' => '123',
            'sharedSecret' => '4567890'
        ];

        $assoc = new Association($args);

        $this->assertFalse($this->cache->getAssociation($uri));

        $this->cache->setAssociation($assoc);
        $this->assertInstanceOf(Association::class, $this->cache->getAssociation($uri));
        $this->assertInstanceOf(Association::class, $this->cache->getAssociation($uri, $args['assocHandle']));

        $this->cache->deleteAssociation($uri);
        $this->assertFalse($this->cache->getAssociation($uri));
    }

    public function testDiscover()
    {
        $identifier = 'http://example.com';

        $discover = new Discover($identifier);

        $this->assertNull($this->cache->getDiscover($identifier));

        $this->cache->setDiscover($discover);
        $this->assertInstanceOf(Discover::class, $this->cache->getDiscover($identifier));

        $this->cache->deleteDiscover($identifier);
        $this->assertNull($this->cache->getDiscover($identifier));
    }

    public function testNonce()
    {
        $uri = 'http://exampleop.com';
        $object = new Nonce($uri);
        $nonce = $object->createNonce();

        $this->assertFalse($this->cache->getNonce($nonce, $uri));

        $this->cache->setNonce($nonce, $uri);
        $this->assertSame($nonce, $this->cache->getNonce($nonce, $uri));

        $this->cache->deleteNonce($nonce, $uri);
        $this->assertFalse($this->cache->getNonce($nonce, $uri));
    }
}
