#!/bin/sh

curl -L -o config/eduGAIN.xml https://wayf.surfnet.nl/metadata/edugain/downstream.xml && \
xmlsec1 --verify --id-attr:ID urn:oasis:names:tc:SAML:2.0:metadata:EntitiesDescriptor --trusted-pem contrib/surfnet.cer config/eduGAIN.xml
