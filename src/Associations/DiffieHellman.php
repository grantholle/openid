<?php

namespace Pear\OpenId\Associations;

use Pear\OpenId\Exceptions\OpenIdAssociationException;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenID\OpenIdMessage;
use Pear\Crypt\DiffieHellman as CryptDiffieHellman;

/**
 * OpenID_Association_DiffieHellman
 *
 * Segregates the DiffieHellman specific parts of an association request.  This is
 * aimed at folks that don't want to use DH for associations.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class DiffieHellman
{
    /**
     * DiffieHellman specific constants
     */
    const DH_DEFAULT_MODULUS = '155172898181473697471232257763715539915724801966915404479707795314057629378541917580651227423698188993727816152646631438561595825688188889951272158842675419950341258706556549803580104870537681476726513255747040765857479291291572334510643245094715007229621094194349783925984760375594985848253359305585439638443';

    const DH_DEFAULT_GENERATOR = '2';

    /**
     * The OpenIDMessage being used in the request
     *
     * @var OpenIdMessage
     */
    protected $message = null;

    /**
     * The instance of DiffieHellman.  May be passed into the constructor if
     * you want to use custom keys.
     *
     * @var DiffieHellman
     */
    protected $cdh = null;

    /**
     * Whether or not the sharedSecretKey has been computed or not
     *
     * @see getSharedSecretKey()
     * @var int
     */
    protected $sharedKeyComputed = 0;

    /**
     * Sets the instance of OpenIDMessage being used, and also an optional
     * instance of DiffieHellman
     *
     * @param OpenIdMessage $message The request OpenIDMessage
     * @param CryptDiffieHellman|null $cdh Optional instance of DiffieHellman
     *
     */
    public function __construct(OpenIdMessage $message, CryptDiffieHellman $cdh = null)
    {
        $this->message = $message;
        if ($cdh instanceof DiffieHellman) {
            $this->cdh = $cdh;
        }
    }

    /**
     * Initialize the diffie-hellman parameters for the association request.
     *
     * @return void
     * @throws CryptDiffieHellman\DiffieHellmanException
     * @throws \Pear\OpenId\Exceptions\OpenIdMessageException
     */
    public function init()
    {
        if ($this->cdh === null) {
            $this->cdh = new CryptDiffieHellman(
                self::DH_DEFAULT_MODULUS, self::DH_DEFAULT_GENERATOR
            );

            $this->cdh->generateKeys();
        }

        // Set public key
        $this->message->set(
            'openid.dh_consumer_public',
            base64_encode($this->cdh->getPublicKey(CryptDiffieHellman::BTWOC))
        );

        // Set modulus
        $prime = $this->cdh->getPrime(CryptDiffieHellman::BTWOC);
        $this->message->set('openid.dh_modulus', base64_encode($prime));

        // Set prime
        $gen = $this->cdh->getGenerator(CryptDiffieHellman::BTWOC);
        $this->message->set('openid.dh_gen', base64_encode($gen));
    }

    /**
     * Gets the shared secret out of a response
     *
     * @param array $response The response in array format
     * @param array &$params The parameters being build for Request::buildAssociation()
     * @return void
     * @throws OpenIdAssociationException|CryptDiffieHellman\DiffieHellmanException
     */
    public function getSharedSecret(array $response, array &$params)
    {
        if (!isset($response['dh_server_public'])) {
            throw new OpenIdAssociationException(
                'Missing dh_server_public parameter in association response',
                OpenIdException::MISSING_DATA
            );
        }

        $pubKey = base64_decode($response['dh_server_public']);
        $sharedSecret = $this->getSharedSecretKey($pubKey);

        $opSecret = base64_decode($response['enc_mac_key']);
        $bytes = mb_strlen(bin2hex($opSecret), '8bit') / 2;
        $algo = str_replace('HMAC-', '', $params['assocType']);
        $hash_dh_shared = hash($algo, $sharedSecret, true);

        $xSecret = '';
        for ($i = 0; $i < $bytes; $i++) {
            $xSecret .= chr(ord($opSecret[$i]) ^ ord($hash_dh_shared[$i]));
        }

        $params['sharedSecret'] = base64_encode($xSecret);
    }

    /**
     * Gets the shared secret key in BTWOC format. Computes the key if it has not
     * been computed already.
     *
     * @param string $publicKey Public key of the OP
     * @return string
     * @throws CryptDiffieHellman\DiffieHellmanException
     */
    public function getSharedSecretKey($publicKey)
    {
        if ($this->sharedKeyComputed == 0) {
            $this->cdh->computeSecretKey($publicKey, CryptDiffieHellman::BINARY);
            $this->sharedKeyComputed = 1;
        }

        return $this->cdh->getSharedSecretKey(CryptDiffieHellman::BTWOC);
    }
}
