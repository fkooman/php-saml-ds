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

use DOMElement;
use fkooman\SAML\DS\Exception\MetadataParserException;
use RuntimeException;

class MetadataParser
{
    /** @var XmlDocument */
    private $xmlDocument;

    /**
     * @param string $metadataFile
     */
    public function __construct($metadataFile)
    {
        if (false === $xmlData = \file_get_contents($metadataFile)) {
            throw new RuntimeException(\sprintf('unable to read file "%s"', $metadataFile));
        }

        $this->xmlDocument = XmlDocument::fromMetadata(
            $xmlData
        );
    }

    /**
     * @param string $entityId
     *
     * @return false|IdpInfo
     */
    public function get($entityId)
    {
        $xPathQuery = \sprintf('//md:EntityDescriptor[@entityID="%s"]/md:IDPSSODescriptor', $entityId);
        $domNodeList = $this->xmlDocument->domXPath->query($xPathQuery);
        if (0 === $domNodeList->length) {
            // IdP not found
            return false;
        }
        if (1 !== $domNodeList->length) {
            // IdP found more than once?
            throw new MetadataParserException(\sprintf('IdP "%s" found more than once', $entityId));
        }
        $domElement = $domNodeList->item(0);
        if (!($domElement instanceof DOMElement)) {
            throw new MetadataParserException(\sprintf('element "%s" is not an element', $xPathQuery));
        }

        return new IdpInfo(
            $entityId,
            $this->getSingleSignOnService($domElement),
            $this->getPublicKey($domElement),
            $this->getKeywords($domElement),
            $this->getDisplayName($domElement),
            $this->getLogo($domElement)
        );
    }

    /**
     * @param \DOMElement $domElement
     *
     * @return string
     */
    private function getSingleSignOnService(DOMElement $domElement)
    {
        // what happens if there is more than one element that matches this?
        return $this->xmlDocument->domXPath->evaluate('string(md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]/@Location)', $domElement);
    }

    /**
     * @param \DOMElement $domElement
     *
     * @return array<PublicKey>
     */
    private function getPublicKey(DOMElement $domElement)
    {
        $publicKeys = [];
        $domNodeList = $this->xmlDocument->domXPath->query('md:KeyDescriptor[not(@use) or @use="signing"]/ds:KeyInfo/ds:X509Data/ds:X509Certificate', $domElement);
        if (0 === $domNodeList->length) {
            throw new MetadataParserException('entry MUST have at least one X509Certificate');
        }
        for ($i = 0; $i < $domNodeList->length; ++$i) {
            $certificateNode = $domNodeList->item($i);
            if (null !== $certificateNode) {
                $publicKeys[] = PublicKey::fromEncodedString($certificateNode->textContent);
            }
        }

        return $publicKeys;
    }

    /**
     * @param \DOMElement $domElement
     *
     * @return array<string>
     */
    private function getKeywords(DOMElement $domElement)
    {
        $keyWordsList = [];
        $domNodeList = $this->xmlDocument->domXPath->query('md:Extensions/mdui:UIInfo/mdui:Keywords[@xml:lang="en"]', $domElement);
        for ($i = 0; $i < $domNodeList->length; ++$i) {
            $keywordsNode = $domNodeList->item($i);
            if (null !== $keywordsNode) {
                $keyWordsList = \array_merge(\explode(' ', $keywordsNode->textContent));
            }
        }

        return \array_unique($keyWordsList);
    }

    /**
     * @param \DOMElement $domElement
     *
     * @return string|null
     */
    private function getDisplayName(DOMElement $domElement)
    {
        $domNodeList = $this->xmlDocument->domXPath->query('md:Extensions/mdui:UIInfo/mdui:DisplayName[@xml:lang="en"]', $domElement);
        if (0 === $domNodeList->length) {
            return null;
        }

        if (null === $displayNameNode = $domNodeList->item(0)) {
            return null;
        }

        return $displayNameNode->textContent;
    }

    /**
     * @param \DOMElement $domElement
     *
     * @return array<LogoInfo>
     */
    private function getLogo(DOMElement $domElement)
    {
        $logoList = [];
        $domNodeList = $this->xmlDocument->domXPath->query('md:Extensions/mdui:UIInfo/mdui:Logo', $domElement);
        for ($i = 0; $i < $domNodeList->length; ++$i) {
            $logoNode = $domNodeList->item($i);
            if (!($logoNode instanceof \DOMElement)) {
                continue;
            }

            $logoList[] = new LogoInfo(
                (int) $logoNode->getAttribute('width'),
                (int) $logoNode->getAttribute('height'),
                $logoNode->textContent
            );
        }

        return $logoList;
    }
}
