<?php

namespace Pear\OpenId\Extensions;

use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Exceptions\OpenIdExtensionException;

/**
 * OpenID_Extension_UI
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Provides support for the UI extension
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class UI extends OpenIdExtension
{
    /**
     * URI of the UI namespace
     *
     * @var string
     */
    protected $namespace ='http://specs.openid.net/extensions/ui/1.0';

    /**
     * Alias to use
     *
     * @var string
     */
    protected $alias = 'ui';

    /**
     * Valid modes (only 'popup' so far)
     *
     * @var array
     */
    protected $validModes = array('popup');

    /**
     * Adds mode checking to set()
     *
     * @param mixed $key Key
     * @param mixed $value Value
     * @return void
     * @throws OpenIdExtensionException
     */
    public function set($key, $value)
    {
        if (strpos($key, 'mode') === 0
            && !in_array($value, $this->validModes)
        ) {
            throw new OpenIdExtensionException(
                'Invalid UI mode: ' . $key,
                OpenIdException::INVALID_VALUE
            );
        }

        parent::set($key, $value);
    }
}
