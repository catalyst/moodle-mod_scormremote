# Description of importing scol-r into mod_scormremote:

1. Install scol-r via

$ npm i @didask/scol-r

2. Copy the files we need from node_modules

$ cp ../../node_modules/@didask/scol-r/lib/*.js scol-r/lib
$ cp ../../node_modules/@didask/scol-r/index.html scol-r

3. Then compare the imsmanifest.xml file. What matters here is the <resources> tag. This should contain all the files we've copied
over. Compare what is currently in imsmanifest.xml file against the imsmanifest.xml file in https://github.com/Didask/scol-r