<?php
/**
 * OpenID_Extension_Mock
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

require_once 'src/Extension.php';

/**
 * OpenID_Extension_Mock
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class OpenID_Extension_Mock extends OpenID_Extension
{
    protected $requestKeys  = array('one', 'two', 'three');
    protected $responseKeys = array('four', 'five', 'six');

    protected $alias     = 'mock';
    protected $namespace = 'http://example.com/mock';
}
?>
