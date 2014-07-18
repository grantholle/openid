<?php
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
 * Required files
 */
require_once 'Cache/Lite.php';
require_once 'OpenID/Store/Interface.php';

/**
 * PEAR Cache_Lite driver for storage.  This is the default driver used.
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
class OpenID_Store_CacheLite implements OpenID_Store_Interface
{
    /**
     * Instance of Cache_Lite
     * 
     * @var Cache_Lite
     */
    protected $cache = null;

    /**
     * Options currently in use
     * 
     * @var array
     */
    protected $options = array();

    /**
     * Default options for Cache_Lite
     * 
     * @var array
     */
    protected $defaultOptions = array(
        'cacheDir'             => '/tmp',
        'lifeTime'             => 3600,
        'hashedDirectoryLevel' => 2
    );

    /**
     * Sub-directory storage for each type of store
     * 
     * @var array
     */
    protected $storeDirectories = array(
        self::TYPE_ASSOCIATION => 'association',
        self::TYPE_DISCOVER    => 'discover',
        self::TYPE_NONCE       => 'nonce'
    );

    /**
     * Instantiate Cache_Lite.  Allows for options to be passed to Cache_Lite.  
     * 
     * @param array $options Options for Cache_Lite constructor
     * 
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->cache   = new Cache_Lite($this->options);
    }

    /**
     * Gets an OpenID_Assocation instance from storage
     * 
     * @param string $uri    The OP endpoint URI to get an association for
     * @param string $handle The handle if available
     * 
     * @return OpenID_Association
     */
    public function getAssociation($uri, $handle = null)
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
     * Stores an OpenID_Association instance.  Details (such as endpoint url and 
     * expiration) are retrieved from the object itself.
     * 
     * @param OpenID_Association $association Instance of OpenID_Association
     * 
     * @return void
     */
    public function setAssociation(OpenID_Association $association)
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

        return $this->removeFromCache(md5($uri));
    }

    /**
     * Gets an OpenID_Discover object from storage
     * 
     * @param string $identifier The normalized identifier that discovery was 
     *                           performed on
     * 
     * @return OpenID_Discover
     */
    public function getDiscover($identifier)
    {
        $this->setOptions(self::TYPE_DISCOVER);

        $result = $this->cache->get($this->getDiscoverCacheKey($identifier));
        if ($result === false) {
            return $result;
        }
        return unserialize($result);
    }

    /**
     * Stores an instance of OpenID_Discover
     * 
     * @param OpenID_Discover $discover Instance of OpenID_Discover
     * @param int             $expire   How long to cache it for, in seconds
     * 
     * @return void
     */
    public function setDiscover(OpenID_Discover $discover, $expire = null)
    {
        $this->setOptions(self::TYPE_DISCOVER, $expire);

        $key = $this->getDiscoverCacheKey($discover->identifier);

        return $this->cache->save(serialize($discover), $key);
    }

    /**
     * Deletes a cached OpenID_Discover object
     * 
     * @param string $identifier The Identifier
     * 
     * @return void
     */
    public function deleteDiscover($identifier)
    {
        $this->setOptions(self::TYPE_DISCOVER);

        $key = $this->getDiscoverCacheKey($identifier);

        return $this->removeFromCache($key);
    }

    /**
     * Common method for creating a cache key based on the normalized identifier
     * 
     * @param string $identifier User supplied identifier
     * 
     * @return string md5 of the normalized identifier
     */
    protected function getDiscoverCacheKey($identifier)
    {
        return md5(OpenID::normalizeIdentifier($identifier));
    }

    /**
     * Gets a nonce from storage
     * 
     * @param string $nonce The nonce itself
     * @param string $opURL The OP Endpoint URL it was used with
     * 
     * @return string
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
     * 
     * @return void
     */
    public function setNonce($nonce, $opURL)
    {
        $this->setOptions(self::TYPE_NONCE);

        return $this->cache->save($nonce, $this->getNonceCacheKey($nonce, $opURL));
    }

    /**
     * Deletes a nonce from storage
     * 
     * @param string $nonce The nonce to delete
     * @param string $opURL The OP endpoint URL it is associated with
     * 
     * @return void
     */
    public function deleteNonce($nonce, $opURL)
    {
        $this->setOptions(self::TYPE_NONCE);

        return $this->removeFromCache($this->getNonceCacheKey($nonce, $opURL));
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
     * Sets options for Cache_Lite based on the needs of the current method.
     * Options set include the subdirectory to be used, and the expiration.
     * 
     * @param string $key    The sub-directory of the cacheDir
     * @param string $expire The cache lifetime (expire) to be used
     * 
     * @return void
     */
    protected function setOptions($key, $expire = null)
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
     * This is a warpper for Cache_Lite::remove(), since it generates
     * strict warnings.
     * 
     * @param mixed $key The key to remove from cache
     * 
     * @return result of Cache_Lite::remove(), without the strict warnings
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
?>
