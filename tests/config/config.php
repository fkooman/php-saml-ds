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

return [
    // set secureCookie flag, so browser only sends them over HTTPS
    'secureCookie' => false,

    // enable the Twig template cache
    'enableTemplateCache' => true,

    // whether or not to display logos on the discovery page, these are
    // extracted from the (IdP) metadata if available
    'useLogos' => false,

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
