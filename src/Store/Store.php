<?php

namespace Pear\OpenId\Store;

use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Exceptions\StoreException;

/**
 * OpenID_Store
 *
 * PHP Version 5.2.0+
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Provides a factory for creating storage classes.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
abstract class Store
{
    /**
     * Creates an instance of a storage driver
     *
     * @param string $driver Driver name
     * @param array $options Any options the driver needs
     * @return StoreInterface
     * @throws StoreException
     */
    static public function factory($driver = CacheLite::class, array $options = [])
    {
        $instance = new $driver($options);

        if (!$instance instanceof StoreInterface) {
            throw new StoreException(
                $class . ' does not implement StoreInterface',
                OpenIdException::INVALID_DEFINITION
            );
        }

        return $instance;
    }
}
