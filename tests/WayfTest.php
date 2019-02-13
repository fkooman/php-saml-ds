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
