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

"use strict";

document.addEventListener("DOMContentLoaded", function () {
    // disable standard form submit when JS is enabled
    if(null !== document.querySelector("form.filter")) {
        document.querySelector("form.filter").addEventListener("submit", function (e) {
            e.preventDefault();
        });

        document.querySelector("form.filter input#filter").addEventListener("keyup", function () {
            var filter = this.value.toUpperCase();
            var entries = document.querySelectorAll("ul#disco li");
            var visibleCount = 0;
            var keywords;
            var i;
            for (i = 0; i < entries.length; i += 1) {
                // look through the keywords
                keywords = entries[i].querySelector("form.entity button").dataset.keywords;
                if (keywords.toUpperCase().indexOf(filter) !== -1) {
                    entries[i].style.display = "list-item";
                    visibleCount += 1;
                } else {
                    entries[i].style.display = "none";
                }
            }

            if (0 === visibleCount) {
                // hide the accessList, as there are no entries matching the search
                document.getElementById("accessList").style.display = "none";
            } else {
                // show the accessList (again)
                document.getElementById("accessList").style.display = "block";
            }
        });
    }
});
