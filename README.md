# Moodle Mod Scormremote

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

## Configuration

To allow iframes to load on the other site this needs to be set:

allowframembedding = 1
