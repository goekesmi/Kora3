<?php namespace App\Services;

use Illuminate\Support\Facades\Auth;
use \Symfony\Component\Security\Core\User;

/**
 * Helps with the dynamically generated text that needs to be translated at runtime.
 *
 * Class Translator
 * @package App\Services
 */
class Translator
{
    /**
     * English translations of dynamically generated words.
     */
    const ENGLISH_TRANS = ['Recaptcha Private Key', 'Recaptcha Public Key', 'Mail Host', 'Mail User', 'Mail Password'];

    /**
     * Spanish translations of dynamically generated words.
     */
    const SPANISH_TRANS = ['Recaptcha llave privada', 'Clave p&uacute;blica de Recaptcha', 'Anfitri&oacute;n del correo',
        'Usuario del correo', 'Contrase&ntilde;a del correo'];

    /**
     * French translations of dynamically generated words.
     */
    const FRENCH_TRANS = ['Cl&eacute; priv&eacute;e de Recaptcha', 'Cl&eacute; publique de Recaptcha',
        'Centre serveur de courrier', 'Utilisateur de courrier', 'Mot de passe de courrier'];

    /**
     * Constant associative array of the above translations.
     */
    const TRANS = ['en' => self::ENGLISH_TRANS, 'es' => self::SPANISH_TRANS, 'fr' => self::FRENCH_TRANS];

    /**
     * There are some constant text values that are dynamically generated text values that must be translated after the
     * fact, so we do that here using the above arrays.
     *
     * @param $string string The string to be translated.
     * @return string The translated string.
     */
    public static function translate($string) {
        $lang = Auth::user()->language;
        return str_replace(self::TRANS['en'], self::TRANS[$lang], $string);
    }
}