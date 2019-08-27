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

require_once \dirname(__DIR__).'/vendor/autoload.php';
$baseDir = \dirname(__DIR__);

use fkooman\SAML\DS\Config;
use fkooman\SAML\DS\Json;
use fkooman\SAML\DS\MetadataParser;
use fkooman\SAML\DS\TemplateEngine;

$dataDir = \sprintf('%s/data', $baseDir);

try {
    $config = Config::fromFile(\sprintf('%s/config/config.php', $baseDir));
    $metadataFiles = \glob(\sprintf('%s/config/metadata/*.xml', $baseDir));

    $templateEngine = new TemplateEngine(
        [
            \sprintf('%s/views', $baseDir),
        ]
    );

    // we need to get information for all SPs this service is going to be the
    // WAYF for...
    $spEntityIdList = $config->get('spList')->keys();

    // each of these defined SPs has a list of IdPs for which we need to
    // extract the information from the available metadata files
    foreach ($spEntityIdList as $spEntityID) {
        $idpInfoList = [];
        $idpEntityIdList = $config->get('spList')->get($spEntityID)->get('idpList');
        foreach ($idpEntityIdList as $idpEntityId) {
            // find the IdP entityID in any of the metadata files...
            foreach ($metadataFiles as $metadataFile) {
                $idpInfoSource = new MetadataParser($metadataFile);
                if (false !== $idpInfo = $idpInfoSource->get($idpEntityId)) {
                    $idpInfoList[] = $idpInfo;
                }
            }
        }

        $encodedSpEntityID = \preg_replace('/__*/', '_', \preg_replace('/[^A-Za-z.]/', '_', $spEntityID));

        // write the XML file
        $xmlFile = \sprintf('%s/%s.xml', $dataDir, $encodedSpEntityID);
        $metadataContent = $templateEngine->render('metadata', ['idpInfoList' => $idpInfoList]);
        if (false === \file_put_contents($xmlFile, $metadataContent)) {
            throw new RuntimeException(\sprintf('unable to write "%s"', $xmlFile));
        }

        // write the JSON file
        $jsonFile = \sprintf('%s/%s.json', $dataDir, $encodedSpEntityID);
        $jsonData = [];
        foreach ($idpInfoList as $idpInfo) {
            $entityId = $idpInfo->getEntityId();
            $jsonData[$entityId] = [
                'entityID' => $entityId,
                'displayName' => $idpInfo->getDisplayName(),
                'keywords' => $idpInfo->getKeywords(),
            ];
        }
        if (false === \file_put_contents($jsonFile, Json::encode($jsonData))) {
            throw new RuntimeException(\sprintf('unable to write "%s"', $jsonFile));
        }
    }
} catch (Exception $e) {
    echo \sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
