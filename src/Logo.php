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
     * @param string $logoDir
     */
    public function __construct($logoDir, HttpClientInterface $httpClient)
    {
        if (!@\file_exists($logoDir)) {
            if (false === @\mkdir($logoDir, 0711, true)) {
                throw new RuntimeException(\sprintf('unable to create folder "%s"', $logoDir));
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
     * @return void
     */
    public function prepare(IdpInfo $idpInfo)
    {
        // placeholder if retrieving logo fails
        $logoData = \base64_decode(self::PLACEHOLDER_IMAGE, true);
        $fileExtension = 'png';

        $logoList = $idpInfo->getLogos();
        $encodedEntityID = $idpInfo->getEncodedEntityId();
        if (0 !== \count($logoList)) {
            $logoInfo = self::getBestLogoUri($logoList);
            if (null !== $logoInfo) {
                try {
                    list($logoData, $mediaType) = $this->obtainLogo($logoInfo->getUri());
                    $fileExtension = $this->mediaTypeToExtension($encodedEntityID, $mediaType);

                    $originalFileName = \sprintf('%s/%s.orig.%s', $this->logoDir, $encodedEntityID, $fileExtension);
                    // store the original logo
                    if (false === @\file_put_contents($originalFileName, $logoData)) {
                        throw new RuntimeException(\sprintf('unable to write to "%s"', $originalFileName));
                    }

                    // optimize the logo
                    $logoData = self::optimize($originalFileName);
                } catch (LogoException $e) {
                    $this->errorLog[] = \sprintf(
                        'unable to obtain logo for "%s": %s',
                        $encodedEntityID,
                        $e->getMessage()
                    );
                }
            }
        }

        $optimizedFileName = \sprintf('%s/%s.png', $this->logoDir, $encodedEntityID);
        if (false === @\file_put_contents($optimizedFileName, $logoData)) {
            throw new RuntimeException(\sprintf('unable to write to "%s"', $optimizedFileName));
        }
    }

    /**
     * @param string $logoUri
     *
     * @return array<int, string>
     */
    public static function extractDataUriLogo($logoUri)
    {
        if (0 !== \strpos($logoUri, 'data:')) {
            throw new LogoException('invalid data URI');
        }
        $logoUri = \substr($logoUri, 5);
        if (false === $commaPos = \strpos($logoUri, ',')) {
            throw new LogoException('invalid data URI: no data');
        }
        $dataHeader = \substr($logoUri, 0, $commaPos);
        $headerParts = \explode(';', $dataHeader);
        $headerCount = \count($headerParts);
        if (2 > $headerCount) {
            throw new LogoException('invalid data URI: not enough parts');
        }
        // first part MUST be content type for image
        if (0 !== \strpos($headerParts[0], 'image/')) {
            throw new LogoException('invalid data URI: no image media type');
        }
        $mediaType = $headerParts[0];
        if ('base64' !== $headerParts[$headerCount - 1]) {
            throw new LogoException('invalid data URI: media MUST be base64 encoded');
        }

        if (false === $logoData = \base64_decode(\substr($logoUri, $commaPos + 1), true)) {
            throw new LogoException('invalida data URI: unable to decode logo');
        }

        return [$logoData, $mediaType];
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
            throw new LogoException(\sprintf('unable to convert logo (%s)', $e->getMessage()));
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
        if (false === \filter_var($logoUri, FILTER_VALIDATE_URL)) {
            throw new LogoException(\sprintf('"%s" is an invalid URI', $logoUri));
        }

        // try to get the logo and content-type
        try {
            $clientResponse = $this->httpClient->get($logoUri);
            if (!$clientResponse->isOkay()) {
                throw new LogoException(\sprintf('got a HTTP %d response from HTTP request to "%s" ', $clientResponse->getStatusCode(), $logoUri));
            }

            if (null === $contentType = $clientResponse->getHeader('Content-Type')) {
                throw new LogoException(\sprintf('unable to determine Content-Type for "%s"', $logoUri));
            }

            return [$clientResponse->getBody(), $contentType];
        } catch (RuntimeException $e) {
            throw new LogoException(\sprintf('unable to retrieve logo: "%s"', $e->getMessage()));
        }
    }

    /**
     * @param string $uri
     *
     * @return bool
     */
    private static function isDataUri($uri)
    {
        return 0 === \strpos($uri, 'data:');
    }

    /**
     * @param array<LogoInfo> $logoList
     *
     * @return LogoInfo|null
     */
    private static function getBestLogoUri(array $logoList)
    {
        // we keep the logo where the highest width is indicated assuming it
        // will be the best quality
        $maxWidth = 0;
        $maxLogo = null;
        foreach ($logoList as $logoInfo) {
            if ($logoInfo->getWidth() > $maxWidth) {
                $maxWidth = $logoInfo->getWidth();
                $maxLogo = $logoInfo;
            }
        }

        return $maxLogo;
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
        if (false !== $colonPos = \strpos($mediaType, ';')) {
            // XXX we should add this to error log
            $this->errorLog[] = \sprintf(
                'needed to strip media type "%s" for "%s"',
                $mediaType,
                $encodedEntityID
            );

            $mediaType = \trim(\substr($mediaType, 0, $colonPos));
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
                throw new LogoException(\sprintf('"%s" is an unsupported media type', $mediaType));
        }
    }
}
