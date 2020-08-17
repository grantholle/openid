<?php

namespace Pear\OpenId\Store;

use Pear\Cache\Lite\Lite;
use Pear\OpenId\Associations\Association;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\OpenId;

/**
 * OpenID_Store_CacheLite
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Store_Interface
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Bill Shupp, Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * PEAR Lite driver for storage.  This is the default driver used.
 *
 * @uses      OpenID_Store_Interface
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Bill Shupp, Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class CacheLite implements StoreInterface
{
    /**
     * Instance of Lite
     *
     * @var Lite
     */
    protected $cache = null;

    /**
     * Options currently in use
     *
     * @var array
     */
    protected $options = [];

    /**
     * Default options for Lite
     *
     * @var array
     */
    protected $defaultOptions = array(
        'cacheDir' => '/tmp',
        'lifeTime' => 3600,
        'hashedDirectoryLevel' => 2
    );

    /**
     * Sub-directory storage for each type of store
     *
     * @var array
     */
    protected $storeDirectories = array(
        self::TYPE_ASSOCIATION => 'association',
        self::TYPE_DISCOVER => 'discover',
        self::TYPE_NONCE => 'nonce'
    );

    /**
     * Instantiate Lite. Allows for options to be passed to Lite.
     *
     * @param array $options Options for Lite constructor
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->cache   = new Lite($this->options);
    }

    /**
     * Gets an OpenID_Assocation instance from storage
     *
     * @param string $uri The OP endpoint URI to get an association for
     * @param string|null $handle The handle if available
     * @return Association
     * @throws \Pear\Cache\Lite\Exceptions\CacheLiteException
     */
    public function getAssociation(string $uri, string $handle = null)
    {
        $this->setOptions(self::TYPE_ASSOCIATION);
        if ($handle !== null) {
            $key = $uri . $handle;
        } else {
            $key = $uri;
        }

        return unserialize($this->cache->get(md5($key)));
    }

    /**
     * Stores an Association instance.  Details (such as endpoint url and
     * expiration) are retrieved from the object itself.
     *
     * @param Association $association Instance of Association
     * @return void
     * @throws \Pear\Cache\Lite\Exceptions\CacheLiteException
     */
    public function setAssociation(Association $association)
    {
        $this->setOptions(self::TYPE_ASSOCIATION, $association->expiresIn);

        // Store URI based key
        $this->cache->save(serialize($association), md5($association->uri));
        // Store URI + Handle based key
        $this->cache->save(
            serialize($association),
            md5($association->uri . $association->assocHandle)
        );
    }

    /**
     * Deletes an association from storage
     *
     * @param string $uri OP Endpoint URI
     *
     * @return void
     */
    public function deleteAssociation($uri)
    {
        $this->setOptions(self::TYPE_ASSOCIATION);

        $this->removeFromCache(md5($uri));
    }

    /**
     * Gets an Discover object from storage
     *
     * @param string $identifier The normalized identifier that discovery was performed on
     * @return Discover
     * @throws \Pear\Cache\Lite\Exceptions\CacheLiteException
     */
    public function getDiscover(string $identifier)
    {
        $this->setOptions(self::TYPE_DISCOVER);

        $result = $this->cache->get($this->getDiscoverCacheKey($identifier));

        if ($result === false) {
            return null;
        }

        return unserialize($result);
    }

    /**
     * Stores an instance of Discover
     *
     * @param Discover $discover Instance of Discover
     * @param int|null $expire How long to cache it for, in seconds
     * @return bool
     * @throws \Pear\Cache\Lite\Exceptions\CacheLiteException
     */
    public function setDiscover(Discover $discover, int $expire = null)
    {
        $this->setOptions(self::TYPE_DISCOVER, $expire);

        $key = $this->getDiscoverCacheKey($discover->identifier);

        return $this->cache->save(serialize($discover), $key);
    }

    /**
     * Deletes a cached Discover object
     *
     * @param string $identifier The Identifier
     * @return void
     */
    public function deleteDiscover(string $identifier)
    {
        $this->setOptions(self::TYPE_DISCOVER);

        $key = $this->getDiscoverCacheKey($identifier);

        $this->removeFromCache($key);
    }

    /**
     * Common method for creating a cache key based on the normalized identifier
     *
     * @param string $identifier User supplied identifier
     * @return string md5 of the normalized identifier
     */
    protected function getDiscoverCacheKey(string $identifier)
    {
        return md5(OpenId::normalizeIdentifier($identifier));
    }

    /**
     * Gets a nonce from storage
     *
     * @param string $nonce The nonce itself
     * @param string $opURL The OP Endpoint URL it was used with
     * @return string
     * @throws \Pear\Cache\Lite\Exceptions\CacheLiteException
     */
    public function getNonce($nonce, $opURL)
    {
        $this->setOptions(self::TYPE_NONCE);

        $key = $this->getNonceCacheKey($nonce, $opURL);

        return $this->cache->get($key);
    }

    /**
     * Stores a nonce for an OP endpoint URL
     *
     * @param string $nonce The nonce itself
     * @param string $opURL The OP endpoint URL it was associated with
     * @return bool
     * @throws \Pear\Cache\Lite\Exceptions\CacheLiteException
     */
    public function setNonce(string $nonce, string $opURL)
    {
        $this->setOptions(self::TYPE_NONCE);

        return $this->cache->save($nonce, $this->getNonceCacheKey($nonce, $opURL));
    }

    /**
     * Deletes a nonce from storage
     *
     * @param string $nonce The nonce to delete
     * @param string $opURL The OP endpoint URL it is associated with
     * @return void
     */
    public function deleteNonce($nonce, $opURL)
    {
        $this->setOptions(self::TYPE_NONCE);

        $this->removeFromCache($this->getNonceCacheKey($nonce, $opURL));
    }

    /**
     * Common method for creating a nonce key based on both the nonce and the OP
     * endpoint URL
     *
     * @param string $nonce The nonce
     * @param string $opURL The OP endpoint URL it is associated with
     *
     * @return string Cache key
     */
    protected function getNonceCacheKey($nonce, $opURL)
    {
        return md5('OpenID.Nonce.' . $opURL . $nonce);
    }

    /**
     * Sets options for Lite based on the needs of the current method.
     * Options set include the subdirectory to be used, and the expiration.
     *
     * @param string $key The sub-directory of the cacheDir
     * @param string|null $expire The cache lifetime (expire) to be used
     * @return void
     */
    protected function setOptions(string $key, string $expire = null)
    {
        $cacheDir  = $this->options['cacheDir'] . '/openid/';
        $cacheDir .= rtrim($this->storeDirectories[$key], '/') . '/';

        $this->ensureDirectoryExists($cacheDir);

        $this->cache->setOption('cacheDir', $cacheDir);

        if ($expire !== null) {
            $this->cache->setOption('lifeTime', $expire);
        }
    }

    /**
     * This is a warpper for Lite::remove(), since it generates
     * strict warnings.
     *
     * @param mixed $key The key to remove from cache
     * @return void
     * @throws \Pear\Cache\Lite\Exceptions\CacheLiteException
     */
    protected function removeFromCache($key)
    {
        $current = error_reporting();
        error_reporting($current & ~E_STRICT);
        $this->cache->remove($key);
        error_reporting($current);
    }

    /**
     * Make sure the given sub directory exists.  If not, create it.
     *
     * @param string $dir The full path to the sub director we plan to write to
     *
     * @return void
     */
    protected function ensureDirectoryExists($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
