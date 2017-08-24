<?php
/**
 * OpenID_Association_DiffieHellman
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

require_once 'Crypt/DiffieHellman.php';
require_once 'OpenID/Association/Exception.php';
require_once 'OpenID/Message.php';

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
class OpenID_Association_DiffieHellman
{
    /**
     * DiffieHellman specific constants
     */
    const DH_DEFAULT_MODULUS = '15517289818147369747123225776371553991'
        . '57248019669154044797077953140576293785419175806512274236981'
        . '88993727816152646631438561595825688188889951272158842675419'
        . '95034125870655654980358010487053768147672651325574704076585'
        . '74792912915723345106432450947150072296210941943497839259847'
        . '60375594985848253359305585439638443';

    const DH_DEFAULT_GENERATOR = '2';

    /**
     * The OpenID_Message being used in the request
     *
     * @var OpenID_Message
     */
    protected $message = null;

    /**
     * The instance of Crypt_DiffieHellman.  May be passed into the constructor if
     * you want to use custom keys.
     *
     * @var Crypt_DiffieHellman
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
     * Sets the instance of OpenID_Message being used, and also an optional
     * instance of Crypt_DiffieHellman
     *
     * @param OpenID_Message      $message The request OpenID_Message
     * @param Crypt_DiffieHellman $cdh     Optional instance of Crypt_DiffieHellman
     *
     * @return void
     */
    public function __construct(OpenID_Message $message, $cdh = null)
    {
        $this->message = $message;
        if ($cdh instanceof Crypt_DiffieHellman) {
            $this->cdh = $cdh;
        }
    }

    /**
     * Initialize the diffie-hellman parameters for the association request.
     *
     * @return void
     */
    public function init()
    {
        if ($this->cdh === null) {
            $this->cdh = new Crypt_DiffieHellman(
                self::DH_DEFAULT_MODULUS, self::DH_DEFAULT_GENERATOR
            );
            $this->cdh->generateKeys();
        }

        // Set public key
        $this->message->set(
            'openid.dh_consumer_public',
            base64_encode($this->cdh->getPublicKey(Crypt_DiffieHellman::BTWOC))
        );

        // Set modulus
        $prime = $this->cdh->getPrime(Crypt_DiffieHellman::BTWOC);
        $this->message->set('openid.dh_modulus', base64_encode($prime));

        // Set prime
        $gen = $this->cdh->getGenerator(Crypt_DiffieHellman::BTWOC);
        $this->message->set('openid.dh_gen', base64_encode($gen));
    }

    /**
     * Gets the shared secret out of a response
     *
     * @param array $response The response in array format
     * @param array $params   The parameters being build for
     *                        OpenID_Association_Reqequest::buildAssociation()
     *
     * @return void
     */
    public function getSharedSecret(array $response, array &$params)
    {
        if (!isset($response['dh_server_public'])) {
            throw new OpenID_Association_Exception(
                'Missing dh_server_public parameter in association response',
                OpenID_Exception::MISSING_DATA
            );
        }

        $pubKey       = base64_decode($response['dh_server_public']);
        $sharedSecret = $this->getSharedSecretKey($pubKey);

        $opSecret       = base64_decode($response['enc_mac_key']);
        $bytes          = mb_strlen(bin2hex($opSecret), '8bit') / 2;
        $algo           = str_replace('HMAC-', '', $params['assocType']);
        $hash_dh_shared = hash($algo, $sharedSecret, true);

        $xsecret = '';
        for ($i = 0; $i < $bytes; $i++) {
            $xsecret .= chr(ord($opSecret[$i]) ^ ord($hash_dh_shared[$i]));
        }

        $params['sharedSecret'] = base64_encode($xsecret);
    }

    /**
     * Gets the shared secret key in BTWOC format.  Computes the key if it has not
     * been computed already.
     *
     * @param string $publicKey Public key of the OP
     *
     * @return BTWOC representation of the number
     */
    public function getSharedSecretKey($publicKey)
    {
        if ($this->sharedKeyComputed == 0) {
            $this->cdh->computeSecretKey($publicKey, Crypt_DiffieHellman::BINARY);
            $this->sharedKeyComputed = 1;
        }
        return $this->cdh->getSharedSecretKey(Crypt_DiffieHellman::BTWOC);
    }
}
?>
