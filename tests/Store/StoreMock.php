<?php

namespace Tests\Store;

use Pear\OpenId\Associations\Association;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Store\StoreInterface;

/**
 * OpenID_Store_Mock
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Store_Interface
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenID_Store_Mock
 *
 * @uses      OpenID_Store_Interface
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class StoreMock implements StoreInterface
{
    /**
     * @inheritDoc
     */
    public function getDiscover($identifier)
    {
    }

    /**
     * @inheritDoc
     */
    public function setDiscover(Discover $discover, int $expire = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function getAssociation(string $uri, string $handle = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function setAssociation(Association $association)
    {
    }

    /**
     * @inheritDoc
     */
    public function deleteAssociation($uri)
    {
    }

    /**
     * @inheritDoc
     */
    public function getNonce($nonce, $opURL)
    {
    }

    /**
     * @inheritDoc
     */
    public function setNonce($nonce, $opURL)
    {
    }

    /**
     * @inheritDoc
     */
    public function deleteNonce($nonce, $opURL)
    {
    }
}

