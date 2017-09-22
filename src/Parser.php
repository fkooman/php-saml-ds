<?php
/**
 * Copyright 2017 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace fkooman\SAML\DS;

use RuntimeException;
use SimpleXMLElement;

class Parser
{
    /** @var array */
    private $metadata = [];

    /** @var array */
    private $errorLog = [];

    public function __construct(array $metadataFiles)
    {
        foreach ($metadataFiles as $metadataFile) {
            if (false === $xml = @simplexml_load_file($metadataFile)) {
                throw new RuntimeException(sprintf('unable to read file "%s"', $metadataFile));
            }
            // $xml->registerXPathNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
            // $xml->registerXPathNamespace('mdui', 'urn:oasis:names:tc:SAML:metadata:ui');
            $this->metadata[] = $xml;
        }
    }

    /**
     * @return array
     */
    public function getErrorLog()
    {
        return $this->errorLog;
    }

    public function getEntitiesInfo(array $entityIDList)
    {
        $entityDescriptors = [];
        foreach ($entityIDList as $entityID) {
            $entityDescriptors[$entityID] = $this->extractEntityInfo($entityID);
        }

        return $entityDescriptors;
    }

    private function extractEntityLogo(SimpleXMLElement $xml)
    {
        $logoList = [];
        $result = $xml->xpath('md:IDPSSODescriptor/md:Extensions/mdui:UIInfo/mdui:Logo');
        foreach ($result as $logoEntry) {
            $logoList[] = [
                'width' => (int) $logoEntry['width'],
                'height' => (int) $logoEntry['height'],
                'uri' => (string) $logoEntry,
            ];
        }

        return $logoList;
    }

    /**
     * @return array
     */
    private function extractEntityInfo($entityID)
    {
        // support both metadata files with one entry and files with a
        // collection of entries wrapped in EntitiesDescriptor

        foreach ($this->metadata as $xml) {
            $entityInfo = $xml->xpath(sprintf('//md:EntityDescriptor[@entityID="%s"]', $entityID));
            if (0 === count($entityInfo)) {
                // entityID not found, try next metadata file
                continue;
            }
            $idpDescriptor = $entityInfo[0]->xpath('md:IDPSSODescriptor');

            if (null === $displayName = $this->getDisplayName($entityInfo[0])) {
                $displayName = $entityID;
                $this->errorLog[] = sprintf('no DisplayName or OrganizationDisplayName for "%s", using entityID', $entityID);
            }

            return [
                'entityID' => $entityID,
                'displayName' => $displayName,
                'SSO' => $this->getSSO($entityID, $idpDescriptor[0]),
                'signingCert' => $this->getSigningCert($idpDescriptor[0]),
                'keywords' => $this->getKeywords($entityInfo[0]),
                'logoList' => $this->extractEntityLogo($entityInfo[0]),
            ];
        }

        throw new ParserException(sprintf('entity "%s" not found in any of the metadata files', $entityID));
    }

    /**
     * Get the HTTP-Redirect binding.
     *
     * @return string
     */
    private function getSSO($entityID, SimpleXMLElement $xml)
    {
        $result = $xml->xpath('md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]');
        if (0 === count($result)) {
            throw new ParserException(sprintf('no SingleSignOnService HTTP-Redirect binding for "%s"', $entityID));
        }

        return (string) $result[0]['Location'];
    }

    /**
     * Get the signing certificate.
     *
     * @return string|null
     */
    private function getSigningCert(SimpleXMLElement $xml)
    {
        $xml->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $result = $xml->xpath('md:KeyDescriptor[@use="signing"]/ds:KeyInfo/ds:X509Data/ds:X509Certificate');
        if (0 === count($result)) {
            // no explicit entry found for "signing", assume the one specified
            // is *also* used for signing, e.g. in the case no explicit "use"
            // is provided
            return $this->getGenericCert($xml);
        }

        return self::trimCert((string) $result[0]);
    }

    /**
     * Get the generic certificate.
     *
     * @return string|null
     */
    private function getGenericCert(SimpleXMLElement $xml)
    {
        $result = $xml->xpath('md:KeyDescriptor/ds:KeyInfo/ds:X509Data/ds:X509Certificate');
        if (0 === count($result)) {
            return null;
        }

        return self::trimCert((string) $result[0]);
    }

    private static function trimCert($certData)
    {
        return str_replace(
            [' ', "\t", "\n", "\r", "\0", "\x0B"],
            '',
            $certData
        );
    }

    private function getDisplayName(SimpleXMLElement $xml)
    {
        $result = $xml->xpath('md:IDPSSODescriptor/md:Extensions/mdui:UIInfo/mdui:DisplayName[@xml:lang="en"]');
        if (0 === count($result)) {
            // try OrganizationDisplayName
            return $this->getOrganizationDisplayName($xml);
        }

        return trim((string) $result[0]);
    }

    /**
     * @return array
     */
    private function getKeywords(SimpleXMLElement $xml)
    {
        $result = $xml->xpath('md:IDPSSODescriptor/md:Extensions/mdui:UIInfo/mdui:Keywords[@xml:lang="en"]');
        if (0 === count($result)) {
            return [];
        }

        return explode(' ', $result[0]);
    }

    private function getOrganizationDisplayName(SimpleXMLElement $xml)
    {
        $result = $xml->xpath('md:Organization/md:OrganizationDisplayName[@xml:lang="en"]');
        if (0 === count($result)) {
            return null;
        }

        return trim((string) $result[0]);
    }
}
