<?php

namespace App\helper;


/**
 * Class Encryption
 */

class Encryption
{
    const METHOD = 'aes-256-cbc';
    private $key;

    /**
     * Encryption constructor.
     * @param $key
     */
    public function __construct($key) {
        if(is_array($key) && !empty($key['key'])){
            $key = $key['key'];
        }
        $count = mb_strlen($key, '8bit');
        if ($count !== 44) {
            die("Needs a 256-bit key! Length: ".$count);
        }
        $this->key = $key;
    }


    /**
     * @param $plaintext
     * @return string
     */
    public function encrypt($plaintext) {
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($ivsize);

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::METHOD,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $this->_vid_base64_encode($iv.$ciphertext);
    }


    /**
     * @param $ciphertext
     * @return string
     */
    public function decrypt($ciphertext) {
        $ciphertext = $this->_vid_base64_decode($ciphertext);
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = mb_substr($ciphertext, 0, $ivsize, '8bit');
        $ciphertext = mb_substr($ciphertext, $ivsize, null, '8bit');
        return @openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }


    /**
     * The objective of the method is to provide a URL safe output
     *
     * @param $string
     * @return string
     */
    private function _vid_base64_encode($string){
        return strtr(base64_encode($string),
            array(
                '+' => '.',
                '=' => '-',
                '/' => '~'
            )
        );
    }


    /**
     * @param $string
     * @return string
     */
    private function _vid_base64_decode($string){
        return base64_decode(strtr($string,
            array(
                '.' => '+',
                '-' => '=',
                '~' => '/'
            )
        ));
    }
}