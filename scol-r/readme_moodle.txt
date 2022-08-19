# Description of importing scol-r into mod_scormremote:

1. Install scol-r via

$ npm i @didask/scol-r

2. Copy the files we need from node_modules

$ cp ../../node_modules/@didask/scol-r/lib/*.js scol-r/lib
$ cp ../../node_modules/@didask/scol-r/index.html scol-r

3. Then compare the imsmanifest.xml file. What matters here is the <resources> tag. This should contain all the files we've copied
over. Compare what is currently in imsmanifest.xml file against the imsmanifest.xml file in https://github.com/Didask/scol-r

3. Search for "lms_origin" in the .js files. You should find it in loadContent.js. lms_origin is on of the parameters passed to the
host. The paramater that should be passed is location.hostname.
diff --git a/scol-r/lib/loadContent.js b/scol-r/lib/loadContent.js
index b079e5a..3e05e7d 100644
--- a/scol-r/lib/loadContent.js
+++ b/scol-r/lib/loadContent.js
@@ -98,7 +98,7 @@ function loadContent() {
             "scorm&learner_id=" +
             learnerId +
             "&lms_origin=" +
-            encodeURIComponent(location.origin) +
+            encodeURIComponent(location.hostname) + // Customisation: Need hostname name here.
             "&data_from_lms=" +
             ADAPTER.getDataFromLMS();
     var iframe = document.createElement("iframe");