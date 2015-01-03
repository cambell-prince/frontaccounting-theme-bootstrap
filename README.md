# Bootstrap Theme for FrontAccounting

This project provides a [Bootstrap 3](http://getbootstrap.com/) theme for [FrontAccounting](http://frontaccounting.com/).

The theme is intentionally minimalist, following the default Bootstrap 3 theme.  This provides a nice base for developing a range of more appealing themes.

## Demo Site 

The easiest way to have a look at the theme is to browse the [demo site](http://demo.saygoweb.com/frontaccounting/).

Have a look, its mostly functional - though not completely.  Check out the known issues documented below and file a bug if you think you've found something we should know about.

## Current Status
Its new, and largely funcitonal.  However, its not 100% complete, so you can expect to find a few rough edges.  Have a look at the 'known issues' documented below.

Up to the minute status can be found on our [Trello board](https://trello.com/b/FualXuOQ/frontaccounting).

### Known Issues

* Drill down reports don't work.

## Installation

Currently, er, you can't.  Try the [demo site](http://demo.saygoweb.com/frontaccounting/) instead. 

Not happy with that?  Well obviously it *can* be installed.  The demo site is working. So if you're a developer...

### Developer Installation via git

* Install my [feature/theme](https://github.com/cambell-prince/frontaccounting/tree/feature/theme) branch of FrontAccounting via git.
    * `git clone https://github.com/cambell-prince/frontaccounting-theme-bootstrap.git`
    * `git checkout feature/theme` or `git checkout master-cp`
* Install [this theme](https://github.com/cambell-prince/frontaccounting-theme-bootstrap) in the themes/bootstrap folder.
    * `cd themes`
    * `git clone https://github.com/cambell-prince/frontaccounting-theme-bootstrap.git bootstrap`
* Run [composer](https://getcomposer.org/) install to install the dependencies.
    * `cd bootstrap`
    * `composer install`

### User Installation via tar or zip packages

* Download one of the [all in one packages](https://github.com/cambell-prince/frontaccounting-theme-bootstrap/releases/tag/v0.8.0-alpha.1), either the tgz or zip format.
* Extract the contents of the package into your web root directory.
* Run the FA installer as usual.
* Select the bootstrap theme from the Setup | Display Setup page.

## Filing Bugs

If you've found an issue that isn't already known do file an issue in the issue tracker.

But first...

* Check that this is not a known issue.
* Check that the issue has [not already been filed](https://github.com/cambell-prince/frontaccounting-theme-bootstrap/issues) by someone else.  Otherwise we have to mark it as a duplicate, point you to the duplicate.  Its a pain for you a pain for us, so do check first :-)

And then...

* You've got a bonafide issue.  [Add it to the issue tracker](https://github.com/cambell-prince/frontaccounting-theme-bootstrap/issues/new).

Telling us...

* The url that you accessed (copy it from the address bar)
* The error message if any.  Include a screen shot would be nice.
* A brief statement of what you expected to see.