const settings = {
    // Determines whether the API schedules an autocommit to the LMS after setting a value. |
    autocommit:            true,
    // Number of seconds to wait before autocommiting. Timer is restarted if another value is set. |
    autocommitSeconds:     5,
    logLevel:              2, // 1 => DEBUG,
                              // 2 => INFO,
                              // 3 => WARN,
                              // 4 => ERROR, // Default.
                              // 5 => NONE
    // Function to transform the commit object before sending it to lmsCommitUrl.
    //requestHandler:        () => {},
    // Function to be called whenever a message is logged.
    //onLogMessage:          () => {},
};

var initialized = false;
const debug = settings.logLevel <= 2;
const output = window.console;

// TODO: Move constants to their own file.
const EMBEDDED_WINDOW_ID = 'embedded-fourth-layer';
const ORIGIN = "*"; //TODO: issue 27


/**
 * Initialize communication with the LMS
 *
 * @returns {null}
 */
// eslint-disable-next-line no-unused-vars
function init() {
    // Create event listener.
    initMessageReciever();

    // Setup the API.
    // TODO: issue 23.
    // eslint-disable-next-line no-undef
    window.API = new Scorm12API(settings);
    window.API.on("LMSCommit", () => {
        if (!initialized) {
            return;
        }
        postMessageToParent('LMSCommit');
    });
    window.API.on("LMSFinish", () => {
        if (!initialized) {
            return;
        }
        postMessageToParent('LMSFinish');
    });
    window.API.on("LMSSetValue", (CMIElement, value) => {
        if (!initialized) {
            return;
        }
        onLMSSetValue(CMIElement, value);
    });

    // Ask for the data model to be sent.
    postMessageToParent('postLMSDataModel', []);

    // TODO: Set took to long to connect timeout.
}

/**
 * This function creates the event handler for incoming postMessage. We expect the parent window (which is on a different domain) to
 * send a single message. This message contains the data model object. We keep listening for errors.
 * properties:
 *  - {string} function: The method that should be called [ErrorHandler, LMSSetDataModel].
 *  - {object Array} arguments: The arguments that should be passed.
 *
 * Depends on:
 *  - {string} ORIGIN to check if event origin is coming from expected path
 *
 * @returns {null}
 */
 function initMessageReciever() {
    window.addEventListener('message', (e) => {
        const ALLOWED_METHODS = ['ErrorHandler', 'LMSSetDataModel', 'message'];

        const functionName = e.data['function'];
        const functionArgs = e.data['arguments'];

        // Can't run unknown function.
        if (
            !functionName ||
            !ALLOWED_METHODS.includes(functionName) ||
            typeof window[functionName] !== 'function'
        ) {
            message('Recieved message contains unexpected data for param function, recieved "' + functionName + '"');
            return;
        }

        // Can't run function with no arguments passed.
        // Even when the desired function has no argument, the passed param MUST be a empty array.
        if (!functionArgs || typeof functionArgs !== 'object' || !Array.isArray(functionArgs)) {
            message('Recieved message contains unexpected data for param arguments, expected array (recieved "' +
                Object.prototype.toString.call(functionArgs) + '")');
            return;
        }

        message('Message recieved. Calling function: "' + functionName + '"');
        window[functionName].apply(null, functionArgs);
    });
}

/**
 * This function outputs messages to a specified output. You can define your own output object. It will just need to implement a
 * log(string) function. This interface was used so that the output could be assigned the window.console object.
 *
 * Depends on:
 *  - {boolean} debug to indicate if output is wanted
 *  - {object} output to handle the messages. This object must implement a function log(string).
 *
 * @param {string} str
 * @returns {null}
 */
 function message(str) {
    if (debug) {
        output.log("[LAYER 3]: " + str);
    }
}

/**
 * This function allows for the data model to be set prior to LMSInitialize. After setting the data model we call loadContent().
 * This must be run before SCO content in loaded.
 *
 * @param {object} cmi
 */
// eslint-disable-next-line no-unused-vars
function LMSSetDataModel(cmi) {
    window.API.loadFromJSON(cmi);
    initialized = true;
    loadContent();
}

/**
 * Load the requested SCO content.
 *
 * @returns {null}
 */
 function loadContent() {
    const parameters = document.location.search;
    const datasource  =  new URL(document.body.dataset.source + parameters);

    var iframe = document.createElement("iframe");
    iframe.setAttribute("id", EMBEDDED_WINDOW_ID);
    iframe.setAttribute("src", datasource);
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("height", "100%");
    iframe.setAttribute("width", "100%");
    document.body.insertBefore(iframe, document.getElementById("wrapper"));
}

/**
 * Handle LMSSetValue call.
 *
 * @param {string} name
 * @param {string} value
 */
function onLMSSetValue(name, value) {
    message('Setting "' + name + '" to value: "' + value + '"');
    postMessageToParent('LMSSetValue', [name, value]);

    // On submit of lesson status we log completion.
    if (
        window.API.cmi.core.lesson_mode !== 'review'
        && name === 'cmi.core.lesson_status'
        && ['completed', 'failed', 'passed'].includes(value)
    ) {
        postCompletion();
    }
}

/**
 * Post to host that activity has been completed.
 *
 * @returns {null}
 */
function postCompletion() {
    // Fetch the src of the iframe. This already contains the needed search parameters.
    const submitsource  =  new URL(document.getElementById(EMBEDDED_WINDOW_ID).src);

    // We need to append the search params with the context id.
    const contextid = getContextIDFromPathname(submitsource.pathname);
    submitsource.search += '&contextid=' + contextid;

    // Replace old pathname with submit complete.
    submitsource.pathname = '/mod/scormremote/submit_completion.php';

    var xhr = new XMLHttpRequest();
    xhr.open("POST", submitsource.href, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send();
}

/**
 * Get context id from pathname.
 *
 * Example pathname /pluginfile.php/123/content/0/index.html then the functions returns 123.
 *
 * @param {string} pathname
 * @returns {number}
 */
function getContextIDFromPathname(pathname) {
    const items = pathname.split('/');

    // Return the first element which is a number in items.
    for (let index = 0; index < items.length; index++) {
        if (items[index] !== '' && !isNaN(items[index])) {
            return parseInt(items[index]);
        }
    }

    return 0;
}

/**
 * Send postMessage to parent window in correct format.
 *
 * @param {string} functionName
 * @param {*} params
 * @returns {null}
 */
function postMessageToParent(functionName, params = []) {
    message('send a message to parent calling function "' + functionName + '"');
    window.parent.postMessage(
        {function: functionName, params},
        ORIGIN
    );
}