<?php

namespace Pear\OpenId\Store;

use Pear\OpenId\Associations\Association;
use Pear\OpenId\Discover\Discover;

/**
 * OpenID_Store_Interface
 *
 * PHP Version 5.2.0+
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Bill Shupp, Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Defines the OpenID storage interface.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Bill Shupp, Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
interface StoreInterface
{
    /**
     *  Constants used for setting which type of storage is being used.
     */
    const TYPE_ASSOCIATION = 1;
    const TYPE_DISCOVER    = 2;
    const TYPE_NONCE       = 3;

    /**
     * Gets an Discover object from storage
     *
     * @param string $identifier The normalized identifier that discovery was performed on
     * @return Discover
     */
    public function getDiscover(string $identifier);

    /**
     * Stores an instance of Discover
     *
     * @param Discover $discover Instance of Discover
     * @param int|null $expire How long to cache it for, in seconds
     * @return void
     */
    public function setDiscover(Discover $discover, int $expire = null);

    /**
     * Gets an OpenID_Assocation instance from storage
     *
     * @param string $uri The OP endpoint URI to get an association for
     * @param string|null $handle The association handle if available
     * @return Association
     */
    public function getAssociation(string $uri, string $handle = null);

    /**
     * Stores an Association instance.  Details (such as endpoint url and
     * exiration) are retrieved from the object itself.
     *
     * @param Association $association Instance of Association
     * @return void
     */
    public function setAssociation(Association $association);

    /**
     * Deletes an association from storage
     *
     * @param string $uri OP Endpoint URI
     * @return void
     */
    public function deleteAssociation(string $uri);

    /**
     * Gets a nonce from storage
     *
     * @param string $nonce The nonce itself
     * @param string $opURL The OP Endpoint URL it was used with
     * @return string
     */
    public function getNonce(string $nonce, string $opURL);

    /**
     * Stores a nonce for an OP endpoint URL
     *
     * @param string $nonce The nonce itself
     * @param string $opURL The OP endpoint URL it was associated with
     * @return void
     */
    public function setNonce(string $nonce, string $opURL);

    /**
     * Deletes a nonce from storage
     *
     * @param string $nonce The nonce to delete
     * @param string $opURL The OP endpoint URL it is associated with
     * @return void
     */
    public function deleteNonce(string $nonce, string $opURL);
}
