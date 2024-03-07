<a  href="https://github.com/catalyst/moodle-mod_scormremote/actions/workflows/ci.yml?query=branch%3AMOODLE_39_STABLE">
<img src="https://github.com/catalyst/moodle-mod_scormremote/workflows/ci/badge.svg?branch=MOODLE_39_STABLE">
</a>

# Moodle Mod Scormremote

* [What is this?](#what-is-this)
* [How does it work?](#how-does-it-work)
* [Branches](#branches)
* [Installation](#installation)
* [Configuration](#configuration)
* [Support](#support)
* [Credits](#credits)

## What is this?

This is a new Moodle activity module completely independant from mod_scorm
which allows you to upload a scorm and then serve it remotely to other
learning management systems on other domains.

It works by generating a thin wrapper scorm which is uploaded into the
other lms and passes events across the iframe barrier using window.postMessage

This has a few advantages as you can:

1) outsource the learning similar to how LTI works reducing the
    burden on the remote LMS's admins
2) sell your scorm packages without giving them away
3) add seat restriction at the domain level
4) swap the scorm packages in place and not require the remote LMS
   to do anything they automatically get the latest version of the package

## How does it work?

This library embeds and serves the Scorm file using the Scorm again library:

https://github.com/jcputney/scorm-again

The wrapper file which is running on the remote site loads the real scorm file
inside a sandboxed iframe and then creates a Scorm API communication bridge 
between the two sites using window.postMessage:

https://developer.mozilla.org/en-US/docs/Web/API/Window/postMessage


## Branches

| Moodle version    | Branch             |
| ----------------- | ------------------ |
| Moodle 3.9+       | `MOODLE_39_STABLE` |


## Installation

1. You can use git to clone it into your source:

```sh
git clone git@github.com:catalyst/moodle-mod_scormremote.git mod/scormremote
```

2. Then run the Moodle upgrade

## Configuration

### General config

To allow iframes to load on the remote site this admin setting needs to be set:

```
allowframembedding = 1
```

### Making a tier

/mod/scormremote/tiers.php

A tier is a level of a subsription which you can use to limit the number of seats
that a client can use. eg you could make a tier called 'Basic plan' and allocate
a maximum of 50 seats to that tier.

### Making a client

/mod/scormremote/clients.php

A client is the remote site which will be embedded your Scorm packages. The main
things a client needs besides a name is the list of domains related to this client.

### Make a remote scorm activity

This is very similar to a normal activity, you add a new activity of type 'SCORM Remote'
and then upload your scorm file to it. 

### Download the wrapper

It will then product a 'wrapper scorm' file which you can download and distribute to
the administrators of the remote site what wants to use the scorm packages. The scorm
wrappers auto detects which client is using the wrapper based on the list of domains
against each client, so the same wrapper can be used with different clients.

### Embed the wrapper on the remote site

This wrapper scorm file can then be imported into the remote site in the same way a
normal scorm file would be.

### Monitor usage

As learners on the remote site use the scorm, you will see accounts created in your
Moodle and enrolments and completion status for those accounts. You can also see
reports showing how many seats are used against the configured tiers.

## Support

If you have issues please log them in
[GitHub](https://github.com/catalyst/moodle-mod_scormremote/issues).

Please note our time is limited, so if you need urgent support or want to
sponsor a new feature then please contact
[Catalyst IT Australia](https://www.catalyst-au.net/contact-us).

## Credits

This development of this plugin was sponsored by [Early Childhood Australia](http://www.earlychildhoodaustralia.org.au/).

http://www.earlychildhoodaustralia.org.au/

[<img src="https://user-images.githubusercontent.com/187449/213033404-75ea1cca-eb44-48b0-acad-7d39a4dcc0bf.png">](http://www.earlychildhoodaustralia.org.au/)

This plugin uses the excellent SCORM Again library:

https://github.com/jcputney/scorm-again

This plugin was developed by [Catalyst IT Australia](https://www.catalyst-au.net/).

<img alt="Catalyst IT" src="https://cdn.rawgit.com/CatalystIT-AU/moodle-auth_saml2/MOODLE_39_STABLE/pix/catalyst-logo.svg" width="400">
