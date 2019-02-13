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

try {
    if (false === $md = @\simplexml_load_file($argv[1])) {
        throw new RuntimeException(\sprintf('unable to read file "%s"', $argv[1]));
    }

    $idpEntityIDs = [];
    $entityDescriptors = $md->xpath('//md:EntityDescriptor');

    foreach ($entityDescriptors as $entityDescriptor) {
        $ssoCount = \count($entityDescriptor->xpath('md:IDPSSODescriptor'));
        if (0 !== $ssoCount) {
            $idpEntityIDs[] = (string) $entityDescriptor['entityID'];
        }
    }

    foreach ($idpEntityIDs as $entityID) {
        echo \sprintf("'%s',", $entityID).PHP_EOL;
    }
} catch (Exception $e) {
    echo \sprintf('ERROR: %s', $e->getMessage());
    exit(1);
}
