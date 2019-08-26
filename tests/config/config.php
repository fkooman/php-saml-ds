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

return [
    // set secureCookie flag, so browser only sends them over HTTPS
    'secureCookie' => false,

    // list of SPs for which this service performs discovery
    'spList' => [
        // the entityID of the SP
        'https://sp.example.org/saml' => [
            // the "displayName" for this SP
            'displayName' => 'My SAML SP',

            // list of entityIDs of the IdPs that are shown in the discovery
            // page. Their information is extracted from the SAML metadata
            'idpList' => [
                'https://idp.tuxed.net/simplesamlphp/saml2/idp/metadata.php',
                'https://engine.surfconext.nl/authentication/idp/metadata',
            ],
        ],
        'https://oneidp.example.org/saml' => [
            // the "displayName" for this SP
            'displayName' => 'My SAML SP',

            // list of entityIDs of the IdPs that are shown in the discovery
            // page. Their information is extracted from the SAML metadata
            'idpList' => [
                'https://idp.tuxed.net/simplesamlphp/saml2/idp/metadata.php',
            ],
        ],

        'https://noidp.example.org/saml' => [
            // the "displayName" for this SP
            'displayName' => 'My SAML SP',

            // list of entityIDs of the IdPs that are shown in the discovery
            // page. Their information is extracted from the SAML metadata
            'idpList' => [
            ],
        ],
    ],
];
