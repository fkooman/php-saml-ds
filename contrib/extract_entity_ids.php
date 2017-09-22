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
try {
    if (false === $md = @simplexml_load_file($argv[1])) {
        throw new RuntimeException(sprintf('unable to read file "%s"', $argv[1]));
    }

    $idpEntityIDs = [];
    $entityDescriptors = $md->xpath('//md:EntityDescriptor');

    foreach ($entityDescriptors as $entityDescriptor) {
        $ssoCount = count($entityDescriptor->xpath('md:IDPSSODescriptor'));
        if (0 !== $ssoCount) {
            $idpEntityIDs[] = (string) $entityDescriptor['entityID'];
        }
    }

    foreach ($idpEntityIDs as $entityID) {
        echo sprintf("'%s',", $entityID).PHP_EOL;
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage());
    exit(1);
}
