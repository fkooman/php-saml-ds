<?php

/*
 * Copyright (c) 2019 François Kooman <fkooman@tuxed.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace fkooman\SAML\DS;

class IdpInfo
{
    /** @var string */
    private $entityId;

    /** @var string */
    private $ssoUrl;

    /** @var array<PublicKey> */
    private $publicKeys = [];

    /** @var array<string> */
    private $keywords;

    /** @var string|null */
    private $displayName;

    /** @var array<LogoInfo> */
    private $logos;

    /**
     * @param string           $entityId
     * @param string           $ssoUrl
     * @param array<PublicKey> $publicKeys
     * @param array<string>    $keywords
     * @param string|null      $displayName
     * @param array<LogoInfo>  $logos
     */
    public function __construct($entityId, $ssoUrl, array $publicKeys, array $keywords, $displayName, array $logos)
    {
        $this->entityId = $entityId;
        $this->ssoUrl = $ssoUrl;
        $this->publicKeys = $publicKeys;
        $this->keywords = $keywords;
        $this->displayName = $displayName;
        $this->logos = $logos;
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return string
     */
    public function getEncodedEntityId()
    {
        return \preg_replace('/__*/', '_', \preg_replace('/[^A-Za-z.]/', '_', $this->getEntityId()));
    }

    /**
     * @return string
     */
    public function getCssEncodedEntityId()
    {
        return \preg_replace('/\./', '\.', $this->getEncodedEntityId());
    }

    /**
     * @return string
     */
    public function getSsoUrl()
    {
        return $this->ssoUrl;
    }

    /**
     * @return array<PublicKey>
     */
    public function getPublicKeys()
    {
        return $this->publicKeys;
    }

    /**
     * @return array<string>
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return string|null
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @return array<LogoInfo>
     */
    public function getLogos()
    {
        return $this->logos;
    }
}
