# Changelog

## 2.1.0 (...)
- update to `fkooman/secookie` >= 3

## 2.0.2 (2019-08-09)
- make sure keywords is a flat array
- remove use of deprecated filter flags

## 2.0.1 (2019-03-28)
- show IdP entity ID instead of empty string when metadata does not contain 
  "display name"
- support SLO again
- also add IdP's DisplayName to the keywords to improve search results

## 2.0.0 (2019-02-13)
- switch to new template engine, drop `twig/twig` dependency
- rewrite parsing of SAML IdP metadata and make it more robust

## 1.0.12 (2018-08-03)
- add `psalm.xml` for static code analysis
- fix coding issues found by Psalm
- fix and rewrite data URI handling and add test for it

## 1.0.11 (2018-04-29)
- fix bug when POST submit misses a parameter

## 1.0.10 (2018-01-22)
- support installed style templates

## 1.0.9 (2017-12-20)
- source formatting
- add support for PHPUnit 6
- update `phpunit.xml.dist`
- cleanup autoloading

## 1.0.8 (2017-10-01)
- follow 301/302 redirects (issue #28)
- trim whitespaces at start and end of logo URL from metadata (issue #29)

## 1.0.7 (2017-09-29)
- cleanup autoloader finder
- cleanup JS:
  - move "use strict" to top
  - only bind event to form in DOM when it is actually there, i.e. not on error
    pages
- more robust logo downloader (tested on eduGAIN metadata)
- show entityID of IdP that does not have HTTP-Redirect binding which we need

## 1.0.6 (2017-09-10)
- update `fkooman/secookie`

## 1.0.5 (2017-08-17)
- clean the CSS to not show outline on Chrome/Epiphany of the search box and 
  IdP buttons

## 1.0.4 (2017-08-14)
- fix "autofocus" on search box and previously selected IdPs (#24)

## 1.0.3 (2017-08-07)
- support memory for multiple favorite IdPs, automatically migrating old 
  "entityID" cookie to new "favoriteIdPs"
- cleanup CSS
- change default footer text

## 1.0.2 (2017-07-28)
- implement simple cache busting for WAYF CSS (#19)
- add documentation on how to override templates

## 1.0.1 (2017-07-19)
- better input validation on query and post parameters
- handle no configured IdPs
- automatically find autoloader

## 1.0.0 (2017-06-30)
- initial release
