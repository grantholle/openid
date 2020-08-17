<?php

namespace Pear\OpenId;

/**
 * OpenID_Nonce
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
 * Handles nonce functionality.  Requires the OP Endpoint URL nonces are to be
 * associated with.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class Nonce
{
    /**
     *  Constant for the parameter used with OpenID 1.1 nonces in the return_to URL
     */
    const RETURN_TO_NONCE = 'openid.1_1_nonce';

    /**
     * The OP Endoint URL a nonce is associated with
     *
     * @var string
     */
    protected $opEndpointURL = null;

    /**
     * Default clock skew, i.e. how long in the past we're willing to allow for.
     *
     * @var int
     * @see validate()
     */
    protected $clockSkew = 18000;

    /**
     * Sets the OP endpoint URL, and optionally the clock skew and custom storage
     * driver.
     *
     * @param string $opEndpointURL OP Endpoint URL
     * @param int|null $clockSkew How many seconds old can a nonce be?
     */
    public function __construct(string $opEndpointURL, int $clockSkew = null)
    {
        $this->opEndpointURL = $opEndpointURL;

        if ($clockSkew) {
            $this->clockSkew = $clockSkew;
        }
    }

    /**
     * Checks to see if the response nonce has been seen before.  If not, store it
     * and then validate its syntax
     *
     * @param string $nonce The nonce from the OP response
     * @return bool true on success, false on failure
     * @throws Exceptions\StoreException
     */
    public function verifyResponseNonce(string $nonce)
    {
        // See if it is already stored
        if (OpenId::getStore()->getNonce($nonce, $this->opEndpointURL) !== false) {
            return false;
        }

        // Store it
        OpenId::getStore()
            ->setNonce($nonce, $this->opEndpointURL);

        return $this->validate($nonce);
    }

    /**
     * Validates the syntax of a nonce, as well as checks to see if its timestamp is
     * within the allowed clock skew
     *
     * @param mixed $nonce The nonce to validate
     *
     * @return bool true on success, false on failure
     * @see $clockSkew
     */
    public function validate($nonce)
    {
        if (strlen($nonce) > 255) {
            return false;
        }

        $result = preg_match(
            '/(\d{4})-(\d\d)-(\d\d)T(\d\d):(\d\d):(\d\d)Z(.*)/',
            $nonce,
            $matches
        );
        if ($result != 1 || count($matches) != 8) {
            return false;
        }

        $stamp = gmmktime(
            $matches[4], $matches[5], $matches[6],
            $matches[2], $matches[3], $matches[1]
        );

        $time = time();
        if ($stamp < ($time - $this->clockSkew)
            || $stamp > ($time + $this->clockSkew)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Creates a nonce, but does not store it.  You may specify the lenth of the
     * random string, as well as the time stamp to use.
     *
     * @param int $length Length of the random string, defaults to 6
     * @param int|null $time A unix timestamp in seconds
     * @return string The nonce
     * @see createNonceAndStore()
     */
    public function createNonce(int $length = 6, int $time = null)
    {
        $time = ($time === null) ? time() : $time;

        $nonce = gmstrftime('%Y-%m-%dT%H:%M:%SZ', $time);
        if ($length < 1) {
            return $nonce;
        }

        $length = (int) $length;
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars .= 'abcdefghijklmnopqrstuvwxyz';
        $chars .= '1234567890';

        $unique = '';
        for ($i = 0; $i < $length; $i++) {
            $unique .= substr($chars, (rand() % (strlen($chars))), 1);
        }

        return $nonce . $unique;
    }

    /**
     * Creates a nonce and also stores it.
     *
     * @param int $length Length of the random string, defaults to 6
     * @param int|null $time A unix timestamp in seconds
     * @return string The nonce
     * @throws Exceptions\StoreException
     * @see createNonce()
     */
    public function createNonceAndStore(int $length = 6, int $time = null)
    {
        $nonce = $this->createNonce($length, $time);
        OpenId::getStore()->setNonce($nonce, $this->opEndpointURL);
        return $nonce;
    }
}
