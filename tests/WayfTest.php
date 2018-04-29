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

namespace fkooman\SAML\DS\Tests;

use fkooman\SAML\DS\Config;
use fkooman\SAML\DS\Http\Request;
use fkooman\SAML\DS\Tests\Http\TestCookie;
use fkooman\SAML\DS\Wayf;
use PHPUnit\Framework\TestCase;

class WayfTest extends TestCase
{
    /** @var Wayf */
    private $w;

    public function setUp()
    {
        $config = Config::fromFile(\sprintf('%s/config/config.php', __DIR__));
        $tpl = new TestTpl();
        $cookie = new TestCookie();
        $this->w = new Wayf(\sprintf('%s/data', __DIR__), $config, $tpl, $cookie);
    }

    public function testShowDiscovery()
    {
        $request = new Request(
            [
                'REQUEST_METHOD' => 'GET',
            ],
            [
                'entityID' => 'https://sp.example.org/saml',
                'returnIDParam' => 'IdP',
                'return' => 'https://foo.example.org/callback?foo=bar',
            ],
            []
        );

        $response = $this->w->run($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"discovery":{"useLogos":false,"filter":false,"entityID":"https:\/\/sp.example.org\/saml","encodedEntityID":"https_sp.example.org_saml","mTime":0,"returnIDParam":"IdP","return":"https:\/\/foo.example.org\/callback?foo=bar","displayName":"My SAML SP","lastChosenList":[],"idpList":[{"entityID":"https:\/\/idp.tuxed.net\/simplesamlphp\/saml2\/idp\/metadata.php","displayName":"FrKoIdP","keywords":["FrKoIdP"],"encodedEntityID":"https_idp.tuxed.net_simplesamlphp_saml_idp_metadata.php"},{"entityID":"https:\/\/engine.surfconext.nl\/authentication\/idp\/metadata","displayName":"SURFconext | SURFnet","keywords":["SURFconext","engine","SURFconext | SURFnet"],"encodedEntityID":"https_engine.surfconext.nl_authentication_idp_metadata"}]}}', $response->getBody());
    }

    public function testShowDiscoveryFilter()
    {
        $request = new Request(
            [
                'REQUEST_METHOD' => 'GET',
            ],
            [
                'filter' => 'engine',
                'entityID' => 'https://sp.example.org/saml',
                'returnIDParam' => 'IdP',
                'return' => 'https://foo.example.org/callback?foo=bar',
            ],
            []
        );

        $response = $this->w->run($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"discovery":{"useLogos":false,"filter":"engine","entityID":"https:\/\/sp.example.org\/saml","encodedEntityID":"https_sp.example.org_saml","mTime":0,"returnIDParam":"IdP","return":"https:\/\/foo.example.org\/callback?foo=bar","displayName":"My SAML SP","lastChosenList":[],"idpList":[{"entityID":"https:\/\/engine.surfconext.nl\/authentication\/idp\/metadata","displayName":"SURFconext | SURFnet","keywords":["SURFconext","engine","SURFconext | SURFnet"],"encodedEntityID":"https_engine.surfconext.nl_authentication_idp_metadata"}]}}', $response->getBody());
    }

    public function testOneIdP()
    {
        // should immediately redirect
        $request = new Request(
            [
                'REQUEST_METHOD' => 'GET',
            ],
            [
                'entityID' => 'https://oneidp.example.org/saml',
                'returnIDParam' => 'IdP',
                'return' => 'https://foo.example.org/callback?foo=bar',
            ],
            []
        );

        $response = $this->w->run($request);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://foo.example.org/callback?foo=bar&IdP=https%3A%2F%2Fidp.tuxed.net%2Fsimplesamlphp%2Fsaml2%2Fidp%2Fmetadata.php', $response->getHeader('Location'));
    }

    public function testNoIdP()
    {
        $request = new Request(
            [
                'REQUEST_METHOD' => 'GET',
            ],
            [
                'filter' => 'engine',
                'entityID' => 'https://noidp.example.org/saml',
                'returnIDParam' => 'IdP',
                'return' => 'https://foo.example.org/callback?foo=bar',
            ],
            []
        );

        $response = $this->w->run($request);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('{"error":{"errorCode":500,"errorMessage":"the SP \"https:\/\/noidp.example.org\/saml\" has no IdPs configured"}}', $response->getBody());
    }

    public function testChooseIdP()
    {
        $request = new Request(
            [
                'REQUEST_METHOD' => 'POST',
            ],
            [
                'entityID' => 'https://sp.example.org/saml',
                'returnIDParam' => 'IdP',
                'return' => 'https://foo.example.org/callback?foo=bar',
            ],
            [
                'idpEntityID' => 'https://idp.tuxed.net/simplesamlphp/saml2/idp/metadata.php',
            ]
        );

        $response = $this->w->run($request);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://foo.example.org/callback?foo=bar&IdP=https%3A%2F%2Fidp.tuxed.net%2Fsimplesamlphp%2Fsaml2%2Fidp%2Fmetadata.php', $response->getHeader('Location'));
    }
}
