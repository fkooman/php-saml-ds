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

        // add the display name also to the keywords to help with search
        $keywords = $this->getKeywords($domElement);
        $displayName = $this->getDisplayName($domElement);
        if (null !== $displayName) {
            $keywords = \array_unique(\array_merge($keywords, \explode(' ', $displayName)));
        }

        return new IdpInfo(
            $entityId,
            $this->getSingleSignOnService($domElement),
            $this->getSingleLogoutService($domElement),
            $this->getPublicKey($domElement),
            $keywords,
            $displayName,
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
        $domNodeList = $this->xmlDocument->domXPath->query('md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]/@Location', $domElement);
        // return the first one, also if multiple are available
        if (null === $firstNode = $domNodeList->item(0)) {
            throw new MetadataParserException('no "md:SingleSignOnService" available');
        }

        return $firstNode->textContent;
    }

    /**
     * @param \DOMElement $domElement
     *
     * @return string|null
     */
    private function getSingleLogoutService(DOMElement $domElement)
    {
        $domNodeList = $this->xmlDocument->domXPath->query('md:SingleLogoutService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]/@Location', $domElement);
        // return the first one, also if multiple are available
        if (null === $firstNode = $domNodeList->item(0)) {
            return null;
        }

        return $firstNode->textContent;
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
