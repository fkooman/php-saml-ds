#!/usr/bin/env php
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
$baseDir = dirname(__DIR__);

// find the autoloader (package installs, composer)
foreach (['src', 'vendor'] as $autoloadDir) {
    if (@file_exists(sprintf('%s/%s/autoload.php', $baseDir, $autoloadDir))) {
        require_once sprintf('%s/%s/autoload.php', $baseDir, $autoloadDir);
        break;
    }
}
use fkooman\SAML\DS\Config;
use fkooman\SAML\DS\HttpClient\CurlHttpClient;
use fkooman\SAML\DS\Logo;
use fkooman\SAML\DS\Parser;
use fkooman\SAML\DS\TwigTpl;

$logoDir = sprintf('%s/data/logo/idp', dirname(__DIR__));

try {
    $config = Config::fromFile(sprintf('%s/config/config.php', dirname(__DIR__)));
    $metadataFiles = glob(sprintf('%s/config/metadata/*.xml', dirname(__DIR__)));
    $parser = new Parser($metadataFiles);

    foreach ($config->get('spList')->keys() as $entityID) {
        // convert all special characters in entityID to _ (same method as mod_auth_mellon)
        $encodedEntityID = preg_replace('/__*/', '_', preg_replace('/[^A-Za-z.]/', '_', $entityID));
        $entityDescriptors = $parser->getEntitiesInfo($config->get('spList')->get($entityID)->get('idpList'));
        $twigTpl = new TwigTpl(
            [
                sprintf('%s/views', dirname(__DIR__)),
            ]
        );
        $metadataContent = $twigTpl->render(
            'metadata',
            [
                'entityDescriptors' => $entityDescriptors,
            ]
        );

        // write a minimal SAML IdP file for every IdP for use by mod_auth_mellon
        $metadataFile = sprintf('%s/data/%s.xml', dirname(__DIR__), $encodedEntityID);
        if (false === @file_put_contents($metadataFile, $metadataContent)) {
            throw new RuntimeException(sprintf('unable to write "%s"', $metadataFile));
        }

        // (optionally) download and convert the logos from the IdP metadata
        if ($config->get('useLogos')) {
            $httpClient = new CurlHttpClient(['httpsOnly' => false]);
            $logo = new Logo($logoDir, $httpClient);
            foreach ($entityDescriptors as $k => $v) {
                $logo->prepare(
                    preg_replace('/__*/', '_', preg_replace('/[^A-Za-z.]/', '_', $k)),
                    $v['logoList']
                );
            }

            foreach ($entityDescriptors as $k => $v) {
                $entityDescriptors[$k]['encodedEntityID'] = preg_replace('/__*/', '_', preg_replace('/[^A-Za-z.]/', '_', $k));
                $entityDescriptors[$k]['cssEncodedEntityID'] = preg_replace('/\./', '\.', $entityDescriptors[$k]['encodedEntityID']);
            }

            $logoCss = $twigTpl->render(
                'logo-css',
                [
                    'entityDescriptors' => $entityDescriptors,
                ]
            );
            $logoCssFile = sprintf('%s/%s.css', $logoDir, $encodedEntityID);
            if (false === @file_put_contents($logoCssFile, $logoCss)) {
                throw new RuntimeException(sprintf('unable to write "%s"', $logoCssFile));
            }

            foreach ($logo->getErrorLog() as $logEntry) {
                echo sprintf('LOGO: %s', $logEntry).PHP_EOL;
            }
        }

        // add/remove data we (don't) need for displaying the discovery page
        foreach ($entityDescriptors as $k => $v) {
            $entityDescriptors[$k]['encodedEntityID'] = preg_replace('/__*/', '_', preg_replace('/[^A-Za-z.]/', '_', $k));
            // add the displayName also to the keywords
            $entityDescriptors[$k]['keywords'][] = $entityDescriptors[$k]['displayName'];
            unset($entityDescriptors[$k]['signingCert']);
            unset($entityDescriptors[$k]['SSO']);
            unset($entityDescriptors[$k]['logoList']);
        }

        $idpListFile = sprintf('%s/data/%s.json', dirname(__DIR__), $encodedEntityID);
        if (false === @file_put_contents($idpListFile, json_encode($entityDescriptors))) {
            throw new RuntimeException(sprintf('unable to write "%s"', $idpListFile));
        }
    }

    foreach ($parser->getErrorLog() as $logEntry) {
        echo sprintf('PARSER: %s', $logEntry).PHP_EOL;
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
