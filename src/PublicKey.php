<?php

/*
 * Copyright 2017,2018,2019  François Kooman <fkooman@tuxed.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace fkooman\SAML\DS;

use fkooman\SAML\DS\Exception\KeyException;
use RuntimeException;

class PublicKey
{
    /** @var resource */
    private $publicKey;

    /** @var string */
    private $pemStr;

    /**
     * @param string $pemStr
     */
    public function __construct($pemStr)
    {
        if (false === $publicKey = \openssl_pkey_get_public($pemStr)) {
            throw new KeyException('not a public key');
        }
        /* @var false|array<string,int|array<string,string>> */
        if (false === $keyInfo = \openssl_pkey_get_details($publicKey)) {
            throw new KeyException('unable to get key information');
        }
        if (!\array_key_exists('type', $keyInfo) || OPENSSL_KEYTYPE_RSA !== $keyInfo['type']) {
            throw new KeyException('not an RSA key');
        }
        $this->publicKey = $publicKey;
        $this->pemStr = $pemStr;
    }

    /**
     * @param string $fileName
     *
     * @return self
     */
    public static function fromFile($fileName)
    {
        $fileData = \file_get_contents($fileName);
        if (false === $fileData) {
            throw new RuntimeException(\sprintf('unable to read key file "%s"', $fileName));
        }

        return new self($fileData);
    }

    /**
     * @param string $encodedString
     *
     * @return self
     */
    public static function fromEncodedString($encodedString)
    {
        $encodedString = \str_replace([' ', "\t", "\n", "\r", "\0", "\x0B"], '', $encodedString);

        return new self("-----BEGIN CERTIFICATE-----\n".\chunk_split($encodedString, 64, "\n").'-----END CERTIFICATE-----');
    }

    /**
     * @return string
     */
    public function toEncodedString()
    {
        return \str_replace(
            [' ', "\t", "\n", "\r", "\0", "\x0B"],
            '',
            \preg_replace(
                '/.*-----BEGIN CERTIFICATE-----(.*)-----END CERTIFICATE-----.*/msU',
                '$1',
                $this->pemStr
            )
        );
    }

    /**
     * @return resource
     */
    public function raw()
    {
        return $this->publicKey;
    }
}
