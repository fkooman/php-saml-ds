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
