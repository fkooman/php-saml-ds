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

use fkooman\SAML\DS\MetadataParser;
use PHPUnit\Framework\TestCase;

class MetadataParserTest extends TestCase
{
    public function testSURFconextOld()
    {
        $metadataParser = new MetadataParser(__DIR__.'/data/SURFconext_old.xml');
        $idpInfo = $metadataParser->get('https://idp.surfnet.nl');
        $this->assertSame('https://idp.surfnet.nl', $idpInfo->getEntityId());
        $this->assertSame('SURFnet bv', $idpInfo->getDisplayName());
        $this->assertSame(
            [
                'SURFnet',
                'bv',
                'SURF',
                'konijn',
                'surf',
                'surfnet',
                'powered',
                'by',
            ],
            $idpInfo->getKeywords()
        );
    }

    public function testSURFconextNew()
    {
        $metadataParser = new MetadataParser(__DIR__.'/data/SURFconext_new.xml');
        $idpInfo = $metadataParser->get('https://idp.surfnet.nl');
        $this->assertSame('https://idp.surfnet.nl', $idpInfo->getEntityId());
        $this->assertSame('SURFnet bv', $idpInfo->getDisplayName());
    }

    public function testNoDisplayName()
    {
        $metadataParser = new MetadataParser(__DIR__.'/data/eva-saml-idp.eduroam.nl.xml');
        $idpInfo = $metadataParser->get('https://eva-saml-idp.eduroam.nl/simplesamlphp/saml2/idp/metadata.php');
        $this->assertSame('https://eva-saml-idp.eduroam.nl/simplesamlphp/saml2/idp/metadata.php', $idpInfo->getEntityId());
        $this->assertNull($idpInfo->getDisplayName());
    }
}
