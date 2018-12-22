<?xml version='1.0' encoding='UTF-8'?>
<md:EntitiesDescriptor xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata">
<?php foreach ($entityDescriptors as $entityDescriptor): ?>
    <md:EntityDescriptor entityID="<?=$this->e($entityDescriptor['entityID']); ?>">
        <md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
          <md:KeyDescriptor use="signing">
            <ds:KeyInfo>
              <ds:X509Data>
                <ds:X509Certificate>
                    <?=$this->e($entityDescriptor['signingCert']); ?>
                </ds:X509Certificate>
              </ds:X509Data>
            </ds:KeyInfo>
          </md:KeyDescriptor>
          <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="<?=$this->e($entityDescriptor['SSO']); ?>"/>
<?php if (null !== $entityDescriptor['SLO']): ?>
          <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="<?=$this->e($entityDescriptor['SLO']); ?>"/>
<?php endif; ?>
        </md:IDPSSODescriptor>
    </md:EntityDescriptor>
<?php endforeach; ?>
</md:EntitiesDescriptor>
