<?php

/*
 * Copyright 2017,2018  François Kooman <fkooman@tuxed.net>
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

class MetadataLogos
{
    /** @var SimpleXMLElement */
    private $md;

    public function __construct($metadataFile)
    {
        if (false === $md = @simplexml_load_file($metadataFile)) {
            throw new RuntimeException(sprintf('unable to read file "%s"', $metadataFile));
        }
        $this->md = $md;
    }

    public function writeLogos($outputDir)
    {
        $logoUrls = $this->getLogoUrls();
        foreach ($logoUrls as $entityID => $logoInfo) {
            echo $entityID.PHP_EOL;
            $encodedEntityID = preg_replace('/__*/', '_', preg_replace('/[^A-Za-z.]/', '_', $entityID));

            if (false === $logoData = $this->fetchLogo($logoInfo['location'])) {
                echo sprintf(' [FAIL] unable to fetch logo for "%s"', $entityID).PHP_EOL;
                continue;
            }
            list($logoData, $ext) = $logoData;

            // store original
            $origFile = sprintf('%s/%s.orig.%s', $outputDir, $encodedEntityID, $ext);
            if (false === @file_put_contents($origFile, $logoData)) {
                echo sprintf(' [FAIL] unable to write "%s"', $origFile).PHP_EOL;
                continue;
            }
            $outFile = sprintf('%s/%s.png', $outputDir, $encodedEntityID);

            try {
                $i = new Imagick($origFile);
                $i->setImageBackgroundColor('transparent');
                $i->thumbnailImage(64, 48, true, true);
                $i->setImageFormat('png');
                $i->writeImage($outFile);
                $i->destroy();
            } catch (ImagickException $e) {
                echo sprintf(' [FAIL] unable to convert logo for IdP "%s"', $entityID).PHP_EOL;
            }
            echo ' [OK]'.PHP_EOL;
        }
    }

    public static function getExtension($mimeType)
    {
        // for base64 encoded logos
        switch ($mimeType) {
            case 'image/gif':
                return 'gif';
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/ico':
            case 'image/vnd.microsoft.icon':
            case 'image/x-icon':
                return 'ico';
            default:
                return false;
        }
    }

    private function getLogoUrls()
    {
        $entityLogoList = [];
        $entityDescriptors = $this->md->xpath('//md:EntityDescriptor');

        foreach ($entityDescriptors as $entityDescriptor) {
            $entityID = (string) $entityDescriptor['entityID'];
            $logoResult = $entityDescriptor->xpath('md:IDPSSODescriptor/md:Extensions/mdui:UIInfo/mdui:Logo');
            if (0 === count($logoResult)) {
                // no logo found, ignore this one
                continue;
            }

            // add all logos to the list
            $logoList = [];
            foreach ($logoResult as $logo) {
                $logoList[] = ['width' => (int) $logo['width'], 'height' => (int) $logo['height'], 'location' => (string) $logo];
            }
            // we keep the logo where the highest width is indicated (assuming it will be the best quality)
            usort($logoList, function ($a, $b) {
                return $a['width'] < $b['width'] ? -1 : ($a['width'] > $b['width'] ? 1 : 0);
            });
            $entityLogoList[$entityID] = $logoList[count($logoList) - 1];
        }

        return $entityLogoList;
    }

    private function fetchLogo($logoUrl)
    {
        // check if it is actually a URL or maybe a base64 encoded string?
        if (false === filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            // not a URL
            // is it a DATA URL?
            if (0 !== strpos($logoUrl, 'data:')) {
                return false;
            }
            $mimeType = explode(':', explode(';', $logoUrl, 2)[0], 2)[1];
            $encodedLogo = explode(',', $logoUrl, 2)[1];
            $ext = self::getExtension($mimeType);

            return [base64_decode($encodedLogo, true), $ext];
        }

        $ctx = stream_context_create(
            [
                'http' => ['timeout' => 5],
                'https' => ['timeout' => 5],
            ]
        );

        // XXX this does not work for e.g. Google Drive hosted logos as they
        // don't have an extension. Maybe we should use Content-Type from
        // HTTP response to determine mimeType?
        $ext = substr($logoUrl, strrpos($logoUrl, '.') + 1);
        // remove query parameters to determine ext
        if (false !== $qPos = strpos($ext, '?')) {
            $ext = substr($ext, 0, $qPos);
        }

        if (false === $logoData = @file_get_contents($logoUrl, false, $ctx)) {
            return false;
        }

        return [$logoData, $ext];
    }
}

if (3 !== $argc) {
    echo sprintf('%s [metadata.xml] [outputDir]', $argv[0]).PHP_EOL;
    exit(1);
}

$m = new MetadataLogos($argv[1]);
$m->writeLogos($argv[2]);
