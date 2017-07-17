<?php

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
