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
            entries.forEach(function(entry) {
                // look through the keywords
                keywords = entry.querySelector("form.entity button").dataset.keywords;
                if (keywords.toUpperCase().indexOf(filter) !== -1) {
                    entry.style.display = "list-item";
                    visibleCount += 1;
                } else {
                    entry.style.display = "none";
                }
            });

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
