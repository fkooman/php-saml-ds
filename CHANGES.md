# Changelog

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
