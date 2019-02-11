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

require_once \dirname(__DIR__).'/vendor/autoload.php';
$baseDir = \dirname(__DIR__);

use fkooman\SAML\DS\Config;
use fkooman\SAML\DS\HttpClient\CurlHttpClient;
use fkooman\SAML\DS\Logo;
use fkooman\SAML\DS\TemplateEngine;
use fkooman\SAML\DS\XmlDocument;
use fkooman\SAML\DS\XmlIdpInfoSource;

$dataDir = \sprintf('%s/data', $baseDir);
$logoDir = \sprintf('%s/logo/idp', $dataDir);

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
                $idpInfoSource = new XmlIdPInfoSource($metadataFile);
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
                'encodedEntityID' => $idpInfo->getEncodedEntityId(),
                'keywords' => $idpInfo->getKeywords(),
                'cssEncodedEntityID' => $idpInfo->getCssEncodedEntityId(),
            ];
        }
        if (false === \file_put_contents($jsonFile, \json_encode($jsonData))) {
            throw new RuntimeException(\sprintf('unable to write "%s"', $jsonFile));
        }

        // in case we want logos...
        if ($config->get('useLogos')) {
            $httpClient = new CurlHttpClient(['httpsOnly' => false]);
            $logo = new Logo($logoDir, $httpClient);
            foreach ($idpInfoList as $idpInfo) {
                $logo->prepare($idpInfo);
            }

            // write the CSS file
            $logoCss = $templateEngine->render('logo-css', ['idpInfoList' => $idpInfoList]);
            $logoCssFile = \sprintf('%s/%s.css', $logoDir, $encodedSpEntityID);
            if (false === \file_put_contents($logoCssFile, $logoCss)) {
                throw new RuntimeException(\sprintf('unable to write "%s"', $logoCssFile));
            }
        }
    }

//    // for each of these SPs we write out a new IdP metadata file for use by
//    // the SAML SP software the user may be using containing ONLY the
//    // relevant IdPs and information we use

//    // loop over all metadata files

//    $parser = new XmlIdPInfoSource(XmlDocument::fromMetadata($x, true));

//    foreach ($config->get('spList')->keys() as $entityID) {
//        // convert all special characters in entityID to _ (same method as mod_auth_mellon)
//        $encodedEntityID = \preg_replace('/__*/', '_', \preg_replace('/[^A-Za-z.]/', '_', $entityID));
//        $entityDescriptors = $parser->getEntitiesInfo($config->get('spList')->get($entityID)->get('idpList'));
//        $templateEngine = new TemplateEngine(
//            [
//                \sprintf('%s/views', $baseDir),
//            ]
//        );
//        $metadataContent = $templateEngine->render(
//            'metadata',
//            [
//                'entityDescriptors' => $entityDescriptors,
//            ]
//        );

//        // write a minimal SAML IdP file for every IdP for use by mod_auth_mellon
//        $metadataFile = \sprintf('%s/data/%s.xml', $baseDir, $encodedEntityID);
//        if (false === @\file_put_contents($metadataFile, $metadataContent)) {
//            throw new RuntimeException(\sprintf('unable to write "%s"', $metadataFile));
//        }

//        // (optionally) download and convert the logos from the IdP metadata
//        if ($config->get('useLogos')) {
//            $httpClient = new CurlHttpClient(['httpsOnly' => false]);
//            $logo = new Logo($logoDir, $httpClient);
//            foreach ($entityDescriptors as $k => $v) {
//                $logo->prepare(
//                    \preg_replace('/__*/', '_', \preg_replace('/[^A-Za-z.]/', '_', $k)),
//                    $v['logoList']
//                );
//            }

//            foreach ($entityDescriptors as $k => $v) {
//                $entityDescriptors[$k]['encodedEntityID'] = \preg_replace('/__*/', '_', \preg_replace('/[^A-Za-z.]/', '_', $k));
//                $entityDescriptors[$k]['cssEncodedEntityID'] = \preg_replace('/\./', '\.', $entityDescriptors[$k]['encodedEntityID']);
//            }

//            $logoCss = $templateEngine->render(
//                'logo-css',
//                [
//                    'entityDescriptors' => $entityDescriptors,
//                ]
//            );
//            $logoCssFile = \sprintf('%s/%s.css', $logoDir, $encodedEntityID);
//            if (false === @\file_put_contents($logoCssFile, $logoCss)) {
//                throw new RuntimeException(\sprintf('unable to write "%s"', $logoCssFile));
//            }

//            foreach ($logo->getErrorLog() as $logEntry) {
//                echo \sprintf('LOGO: %s', $logEntry).PHP_EOL;
//            }
//        }

//        // add/remove data we (don't) need for displaying the discovery page
//        foreach ($entityDescriptors as $k => $v) {
//            $entityDescriptors[$k]['encodedEntityID'] = \preg_replace('/__*/', '_', \preg_replace('/[^A-Za-z.]/', '_', $k));
//            // add the displayName also to the keywords
//            $entityDescriptors[$k]['keywords'][] = $entityDescriptors[$k]['displayName'];
//            unset($entityDescriptors[$k]['signingCert']);
//            unset($entityDescriptors[$k]['SSO']);
//            unset($entityDescriptors[$k]['logoList']);
//        }

//        $idpListFile = \sprintf('%s/data/%s.json', $baseDir, $encodedEntityID);
//        if (false === @\file_put_contents($idpListFile, \json_encode($entityDescriptors))) {
//            throw new RuntimeException(\sprintf('unable to write "%s"', $idpListFile));
//        }
//    }

//    foreach ($parser->getErrorLog() as $logEntry) {
//        echo \sprintf('PARSER: %s', $logEntry).PHP_EOL;
//    }
} catch (Exception $e) {
    echo \sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
