Description of import into mod_scormremote:
// Install via npm i @didask/scol-r
// Then we copy from node_modules into this library.
// We want to copy all the lib/*.js and the index.html
// we can copy by doing:
cp ../../node_modules/@didask/scol-r/lib/*.js scol-r/lib
cp ../../node_modules/@didask/scol-r/index.html scol-r