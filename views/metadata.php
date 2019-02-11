<?xml version='1.0' encoding='UTF-8'?>
<md:EntitiesDescriptor xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata">
<?php foreach ($idpInfoList as $idpInfo): ?>
    <md:EntityDescriptor entityID="<?=$this->e($idpInfo->getEntityId()); ?>">
        <md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
<?php foreach ($idpInfo->getPublicKeys() as $publicKey): ?>
          <md:KeyDescriptor use="signing">
            <ds:KeyInfo>
              <ds:X509Data>
                <ds:X509Certificate><?=$this->e($publicKey->toEncodedString()); ?></ds:X509Certificate>
              </ds:X509Data>
            </ds:KeyInfo>
          </md:KeyDescriptor>
<?php endforeach; ?>
          <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="<?=$this->e($idpInfo->getSsoUrl()); ?>"/>
        </md:IDPSSODescriptor>
    </md:EntityDescriptor>
<?php endforeach; ?>
</md:EntitiesDescriptor>
