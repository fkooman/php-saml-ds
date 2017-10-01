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

use fkooman\SAML\DS\Exception\LogoException;
use fkooman\SAML\DS\HttpClient\HttpClientInterface;
use Imagick;
use ImagickException;
use RuntimeException;

class Logo
{
    const PLACEHOLDER_IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAAEAAAAAwAQMAAACbhe5cAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA
B3RJTUUH4QYBFQY30jqTUAAAAB1pVFh0Q29tbWVudAAAAAAAQ3JlYXRlZCB3aXRoIEdJTVBkLmUH
AAAAA1BMVEWqqqoRfvv5AAAADUlEQVQYGWMYBUMKAAABsAABgx2r6QAAAABJRU5ErkJggg==';

    /** @var string */
    private $logoDir;

    /** @var \fkooman\SAML\DS\HttpClient\HttpClientInterface */
    private $httpClient;

    /** @var array */
    private $errorLog = [];

    /**
     * @param string                                          $logoDir
     * @param \fkooman\SAML\DS\HttpClient\HttpClientInterface $httpClient
     */
    public function __construct($logoDir, HttpClientInterface $httpClient)
    {
        if (!@file_exists($logoDir)) {
            if (false === @mkdir($logoDir, 0711, true)) {
                throw new RuntimeException(sprintf('unable to create folder "%s"', $logoDir));
            }
        }
        $this->logoDir = $logoDir;
        $this->httpClient = $httpClient;
    }

    /**
     * @return array
     */
    public function getErrorLog()
    {
        return $this->errorLog;
    }

    /**
     * @param string $encodedEntityID
     * @param array  $logoList
     */
    public function prepare($encodedEntityID, array $logoList)
    {
        // placeholder if retrieving logo fails
        $logoData = base64_decode(self::PLACEHOLDER_IMAGE);
        $fileExtension = 'png';

        if (0 !== count($logoList)) {
            $logoUri = self::getBestLogoUri($logoList);
            try {
                list($logoData, $mediaType) = $this->obtainLogo($logoUri);
                $fileExtension = self::mediaTypeToExtension($encodedEntityID, $mediaType);

                $originalFileName = sprintf('%s/%s.orig.%s', $this->logoDir, $encodedEntityID, $fileExtension);
                // store the original logo
                if (false === @file_put_contents($originalFileName, $logoData)) {
                    throw new RuntimeException(sprintf('unable to write to "%s"', $originalFileName));
                }

                // optimize the logo
                $logoData = self::optimize($originalFileName);
            } catch (LogoException $e) {
                $this->errorLog[] = sprintf(
                    'unable to obtain logo for "%s": %s',
                    $encodedEntityID,
                    $e->getMessage()
                );
            }
        }

        $optimizedFileName = sprintf('%s/%s.png', $this->logoDir, $encodedEntityID);
        if (false === @file_put_contents($optimizedFileName, $logoData)) {
            throw new RuntimeException(sprintf('unable to write to "%s"', $optimizedFileName));
        }
    }

    /**
     * @param string $originalFileName
     *
     * @return string
     */
    private static function optimize($originalFileName)
    {
        try {
            $imagick = new Imagick($originalFileName);
            $imagick->setimagebackgroundcolor('transparent');
            $imagick->thumbnailimage(64, 48, true, true);
            $imagick->setimageformat('png');
            $optimizedFileName = $imagick->getimageblob();
            $imagick->destroy();

            return $optimizedFileName;
        } catch (ImagickException $e) {
            throw new LogoException(sprintf('unable to convert logo (%s)', $e->getMessage()));
        }
    }

    /**
     * @param string $logoUri
     *
     * @return array
     */
    private function obtainLogo($logoUri)
    {
        if (self::isDataUri($logoUri)) {
            return self::extractDataUriLogo($logoUri);
        }

        // logoUri MUST be a valid URL now
        if (false === filter_var($logoUri, FILTER_VALIDATE_URL)) {
            throw new LogoException(sprintf('"%s" is an invalid URI', $logoUri));
        }

        // try to get the logo and content-type
        try {
            $clientResponse = $this->httpClient->get($logoUri);
            if (!$clientResponse->isOkay()) {
                throw new LogoException(sprintf('got a HTTP %d response from HTTP request to "%s" ', $clientResponse->getStatusCode(), $logoUri));
            }

            if (null === $contentType = $clientResponse->getHeader('Content-Type')) {
                throw new LogoException(sprintf('unable to determine Content-Type for "%s"', $logoUri));
            }

            return [$clientResponse->getBody(), $contentType];
        } catch (RuntimeException $e) {
            throw new LogoException(sprintf('unable to retrieve logo: "%s"', $e->getMessage()));
        }
    }

    /**
     * @param string $logoUri
     *
     * @return array
     */
    private static function extractDataUriLogo($logoUri)
    {
        // XXX do some better error checking to protect against broken dataUris
        $mediaType = substr($logoUri, 5, strpos($logoUri, ';') - 5);
        $encodedLogoData = substr($logoUri, strpos($logoUri, ','));

        if (false === $logoData = base64_decode($encodedLogoData)) {
            throw new LogoException('unable to decode data URI logo');
        }

        return [$logoData, $mediaType];
    }

    /**
     * @param string $uri
     *
     * @return bool
     */
    private static function isDataUri($uri)
    {
        return 0 === strpos($uri, 'data:');
    }

    /**
     * @param array $logoList
     *
     * @return string
     */
    private static function getBestLogoUri(array $logoList)
    {
        // we keep the logo where the highest width is indicated assuming it
        // will be the best quality
        usort($logoList, function ($a, $b) {
            return $a['width'] < $b['width'] ? -1 : ($a['width'] > $b['width'] ? 1 : 0);
        });

        // trim URL as some metadata files contain extra whitespaces
        return trim($logoList[count($logoList) - 1]['uri']);
    }

    /**
     * @param string $encodedEntityID
     * @param string $mediaType
     *
     * @return string
     */
    private function mediaTypeToExtension($encodedEntityID, $mediaType)
    {
        // strip crap behind the media type
        // "image/png;charset=UTF-8" is NOT a valid image media type...
        if (false !== $colonPos = strpos($mediaType, ';')) {
            // XXX we should add this to error log
            $this->errorLog[] = sprintf(
                'needed to strip media type "%s" for "%s"',
                $mediaType,
                $encodedEntityID
            );

            $mediaType = trim(substr($mediaType, 0, $colonPos));
        }

        switch ($mediaType) {
            case 'image/gif':
                return 'gif';
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            // some non-standard "ico" media types are used in eduGAIN metadata
            // data URIs
            case 'image/ico':
            case 'image/vnd.microsoft.icon':
            case 'image/x-icon':
                return 'ico';
            case 'image/svg+xml':
                return 'svg';
            default:
                throw new LogoException(sprintf('"%s" is an unsupported media type', $mediaType));
        }
    }
}
