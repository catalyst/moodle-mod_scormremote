const debug = true;
const output = window.console;

// Define exception/error codes
var _NoError = { "code": "0", "string": "No Error", "diagnostic": "No Error" };
var _GeneralException = { "code": "101", "string": "General Exception", "diagnostic": "General Exception" };

var initialized = false;

// local variable definitions
var apiHandle = null;
var embeddedWindow = null;
var CMI = null;
var ORIGIN = null;

const EMBEDDED_WINDOW_ID = 'embedded-third-layer';
const ALLOWED_TO_LMSGETVALUE = [
    'cmi.core._children',
    'cmi.core.student_id',
    'cmi.core.student_name',
    'cmi.core.lesson_location',
    'cmi.core.credit',
    'cmi.core.lesson_status',
    'cmi.core.entry',
    'cmi.core.score_children',
    'cmi.core.score.raw',
    'cmi.core.score.max',
    'cmi.core.score.min',
    'cmi.core.total_time',
    'cmi.core.lesson_mode',
    'cmi.suspend_data',
    'cmi.launch_data',
    'cmi.comments',
    'cmi.comments_from_lms',
    'cmi.objectives._children',
    'cmi.objectives._count',
    'cmi.objectives.*.id',
    'cmi.objectives.*.score._children ',
    'cmi.objectives.*.score.raw',
    'cmi.objectives.*.score.max',
    'cmi.objectives.*.score.min',
    'cmi.objectives.*.status',
    'cmi.student_data._children',
    'cmi.student_data.mastery_score',
    'cmi.student_data.max_time_allowed',
    'cmi.student_data.time_limit_action',
    'cmi.student_preference._children',
    'cmi.student_preference.audio',
    'cmi.student_preference.language',
    'cmi.student_preference.speed',
    'cmi.student_preference.text',
    'cmi.interactions._children',
    'cmi.interactions._count',
    'cmi.interactions.*.objectives._count',
    'cmi.interactions.*.time',
    'cmi.interactions.*.correct_responses._count',
];

/**
 * Initialize communication with the LMS
 *
 * @returns {null}
 */
// eslint-disable-next-line no-unused-vars
function init() {
    LMSGetDataModel();

    const datasource = new URL(document.body.dataset.source);
    datasource.search = document.location.search;
    datasource.search += ( document.location.search.indexOf('?') === -1 ? '?' : '&' ); // if ?param=1 then & else ?.
    datasource.search += 'lms_origin=' + document.location.host;
    datasource.search += '&student_id=' + (CMI.core ? CMI.core.student_id : '');
    datasource.search += '&student_name=' + (CMI.core ? CMI.core.student_name : '');
    datasource.search += '&client_id=' + document.body.dataset.clientid;
    ORIGIN = datasource.origin;

    // Add event listener.
    initMessageReciever();

    // Add third layer iframe.
    var iframe = document.createElement("iframe");
    iframe.setAttribute("id", EMBEDDED_WINDOW_ID);
    iframe.setAttribute("src", datasource.href);
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("height", "100%");
    iframe.setAttribute("width", "100%");
    document.body.insertBefore(iframe, document.getElementById("wrapper"));
    embeddedWindow = iframe.contentWindow;
}

/**
 * Initialize communication with LMS by calling the LMSInitialize function which will be implemented by the LMS.
 *
 * @returns {string} true|false depending wheter successful.
 */
function LMSInitialize() {
    if (initialized) {
        message("LMSInitialize succeeded, already initialized.");
        return "true";
    }

    var api = getAPIHandle();
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nLMSInitialize was not successful.");
        return "false";
    }

    var result = api.LMSInitialize("");
    if (result.toString() != "true") {
        var err = ErrorHandler();
        message("LMSInitialize failed with error code: " + err.code);
    }
    else {
        initialized = true;
        message("LMSInitialized succeeded.");
    }

    return result.toString();
}

/**
 * Close communication with LMS by calling the LMSFinish function which will be implemented by the LMS.
 *
 * @returns {string} true|false depending wheter successful.
 */
// eslint-disable-next-line no-unused-vars
function LMSFinish() {
    if (!initialized) {return "true";}

    var api = getAPIHandle();
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nLMSFinish was not successful.");
        return "false";
    }
    else {
        // call the LMSFinish function that should be implemented by the API
        var result = api.LMSFinish("");
        if (result.toString() != "true") {
            var err = ErrorHandler();
            message("LMSFinish failed with error code: " + err.code);
        }
    }

    initialized = false;

    return result.toString();
}

/**
 * Wraps the call to the LMS LMSGetValue function.
 *
 * @param {string} name string representing the cmi data model defined category or element (e.g. cmi_core.student_name).
 * @returns {string}
 */
function LMSGetValue(name) {
    var api = getAPIHandle();
    var result = "";
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nLMSGetValue was not successful.");
    }
    else if (!initialized && !LMSInitialize()) {
        var err = ErrorHandler(); // get why LMSInitialize() returned false
        message("LMSGetValue failed - Could not initialize communication with the LMS - error code: " + err.code);
    }
    else if (LMSGetValueAllowed(name)) {
        result = api.LMSGetValue(name);

        var error = ErrorHandler();
        if (error.code != _NoError.code) {
            // an error was encountered so display the error description
            message("LMSGetValue(" + name + ") failed. \n" + error.code + ": " + error.string);
            result = "";
        }
    }

    if (result === null) {
        return "";
    }
    return result.toString();
}

/**
 * Wraps the call to the LMS LMSGetValue function.
 *
 * @param {string} name string representing the cmi data model defined category or element (e.g. cmi_core.student_name).
 * @param {mixed} value the value that the named element or category will be assigned.
 * @returns {string} true|false depending wheter successful.
 */
// eslint-disable-next-line no-unused-vars
function LMSSetValue(name, value) {
    var api = getAPIHandle();
    var result = "false";
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nLMSSetValue was not successful.");
    }
    else if (!initialized && !LMSInitialize()) {
        var err = ErrorHandler(); // get why LMSInitialize() returned false
        message("LMSSetValue failed - Could not initialize communication with the LMS - error code: " + err.code);
    }
    else {
        result = api.LMSSetValue(name, value);
        if (result.toString() != "true") {
            var err = ErrorHandler();
            message("LMSSetValue(" + name + ", " + value + ") failed. \n" + err.code + ": " + err.string);
        }
    }

    return result.toString();
}

/**
 * Commits the data to the LMS.
 *
 * @returns {string} true|false depending wheter successful.
 */
// eslint-disable-next-line no-unused-vars
function LMSCommit() {
    var api = getAPIHandle();
    var result = "false";
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nLMSCommit was not successful.");
    }
    else if (!initialized && !LMSInitialize()) {
        var err = ErrorHandler(); // get why LMSInitialize() returned false
        message("LMSCommit failed - Could not initialize communication with the LMS - error code: " + err.code);
    }
    else {
        result = api.LMSCommit("");
        if (result != "true") {
            var err = ErrorHandler();
            message("LMSCommit failed - error code: " + err.code);
        }
    }

    return result.toString();
}

/**
 * Call the LMSGetLastError function.
 *
 * @returns {string} The error code that was set by the LMS function call.
 */
// eslint-disable-next-line no-unused-vars
function LMSGetLastError() {
    var api = getAPIHandle();
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nLMSGetLastError was not successful.");
        //since we can't get the error code from the LMS, return a general error
        return _GeneralException.code; //General Exception
    }

    return api.LMSGetLastError().toString();
}

/**
 * Call the LMSGetErrorString function
 *
 * @param {number|string|null} errorCode
 * @returns {string} The textual description that corresponds to the input error code
 */
// eslint-disable-next-line no-unused-vars
function LMSGetErrorString(errorCode) {
    var api = getAPIHandle();
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nLMSGetErrorString was not successful.");
        return _GeneralException.string;
    }

    return api.LMSGetErrorString(errorCode).toString();
}

/**
 * Call the LMSGetDiagnostic function
 *
 * @param {number|string|null} errorCode
 * @returns {string} The vendor specific textual description that corresponds to the input error code.
 */
// eslint-disable-next-line no-unused-vars
function LMSGetDiagnostic(errorCode) {
    var api = getAPIHandle();
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nLMSGetDiagnostic was not successful.");
        return "Unable to locate the LMS's API Implementation. LMSGetDiagnostic was not successful.";
    }

    return api.LMSGetDiagnostic(errorCode).toString();
}

/**
 * Determines if an error was encountered by the previous API call and if so, returns the error.
 *
 * Usage:
 * var last_error = ErrorHandler();
 * if (last_error.code != _NoError.code)
 * {
 *     message("Encountered an error. Code: " + last_error.code +
 *                                          "\nMessage: " + last_error.string +
 *                                          "\nDiagnostics: " + last_error.diagnostic);
 * }
 *
 * @returns {object}
 */
function ErrorHandler() {
    var error = { "code": _NoError.code, "string": _NoError.string, "diagnostic": _NoError.diagnostic };
    var api = getAPIHandle();
    if (api === null || api === undefined) {
        message("Unable to locate the LMS's API Implementation.\nCannot determine LMS error code.");
        error.code = _GeneralException.code;
        error.string = _GeneralException.string;
        error.diagnostic = "Unable to locate the LMS's API Implementation. Cannot determine LMS error code.";
        return error;
    }

    // check for errors caused by or from the LMS
    error.code = api.LMSGetLastError().toString();
    if (error.code != _NoError.code) {
        // an error was encountered so display the error description
        error.string = api.LMSGetErrorString(error.code);
        error.diagnostic = api.LMSGetDiagnostic("");
    }

    return error;
}

/**
 * Returns the handle to API object if it was previously set, otherwise it returns null
 *
 * @returns {*}
 */
function getAPIHandle() {
    if (apiHandle === null || apiHandle === undefined) {
        apiHandle = getAPI();
    }

    return apiHandle;
}

/**
 * This function looks for an object names API in parent and opener window.
 *
 * @param {Window} win
 * @returns {*}
 */
function findAPI(win) {
    var findAPITries = 0;
    while ((win.API === null || win.API === undefined) && (win.parent !== null && win.parent !== undefined) &&
        (win.parent !== win)) {
        findAPITries++;
        // Note: 7 is an arbitrary number, but should be more than sufficient
        if (findAPITries > 7) {
            message("Error finding API -- too deeply nested.");
            return null;
        }

        win = win.parent;
    }
    return win.API;
}

/**
 * This function looks for an object named API, first in the current window's frame hierarchy and then, if necessary, in the current
 * window's opener window hierarchy (if there is an opener window).
 *
 * @returns
 */
function getAPI() {
    var theAPI = findAPI(window);
    if ((theAPI === null || theAPI === undefined) && (window.opener !== null && window.opener !== undefined) &&
        (typeof (window.opener) !== "undefined")) {
        theAPI = findAPI(window.opener);
    }
    if (theAPI === null || theAPI === undefined) {
        message("Unable to find an API adapter");
    }
    return theAPI;
}

// TODO: Move this to UTILS.
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
        output.log("[LAYER 2]: " + str);
    }
}

/**
 * This function creates the event handles for incoming postMessage. This method depends on the ORIGIN variable, if not set the
 * function will immediatly return as a security measure. The function expects that the data passed is a object which contains two
 * properties:
 *  - {string} function: The method that should be called.
 *  - {object Array} arguments: The arguments that should be passed.
 *
 * Depends on:
 *  - {string} ORIGIN to check if event origin is coming from expected path
 *
 * @returns {null}
 */
function initMessageReciever() {
    window.addEventListener('message', (e) => {
        // Don't run anything if message is not coming from expected host.
        if (e.origin !== ORIGIN) {
            message('Recieved message from unknown origin "' + e.origin + '", (expected: "' + ORIGIN + '")');
            return;
        }

        const functionName = e.data['function'];
        const functionArgs = e.data['params'];

        // Can't run unknown function.
        if (!functionName || typeof window[functionName] !== 'function') {
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
 * This function looks for an iframe element by id EMBEDDED_WINDOW_ID and sets the window object to embeddedWindow. It returns the
 * value that it finds or null if it fails to find it.
 *
 * Depends on:
 *  - {string} EMBEDDED_WINDOW_ID the id to look for
 *  - {Window|null} embeddedWindow the variable to set to.
 *
 * @returns {Window|null} value contained by embeddedWindow
 */
// eslint-disable-next-line no-unused-vars
 function getEmbeddedWindow() {
    if (embeddedWindow === null) {
        const element = document.findElementById(EMBEDDED_WINDOW_ID);

        if (element === null) {
            message('Could not find embedded window object, searched for element by id: "'+ EMBEDDED_WINDOW_ID +'"');
            return null;
        }

        if (element.tagName !== 'IFRAME') {
            message('Unexpected tagName for embedded window object, expected "IFRAME" got: "'+ element.tagName +'"');
            return null;
        }

        embeddedWindow = element.contentWindow;
    }

    return embeddedWindow;
}

/**
 * This function returns the data model in object form. It will also store it to the global CMI variable.
 *
 * @returns {object}
 */
function LMSGetDataModel() {
    if (CMI === null || CMI === undefined) {
        const result = 'core,suspend_data,launch_data,comments,comments_from_lms,objectives,student_data' +
            ',student_preference,interactions';
        CMI = LMSGetChildren('cmi', result.split(','));
    }
    return CMI;
}

/**
 * This function returns the children of a data model defined category or element inside a object.
 *
 * @param {str} parent
 * @param {str[]} children
 * @returns {object}
 */
function LMSGetChildren(parent, children) {
    let child = {};
    const count = LMSGetNumberStored(parent);
    if (count !== false) {
        // Multiple childern.

        for (let i = 0; i < count; i++) {
            const key = parent + '.' + i;
            child[i] = LMSGetChildren(key, children);
        }
    } else {
        // Singular children only.
        for (let i = 0; i < children.length; i++) {
            const key = parent + '.' + children[i];
            const supChildren = LMSGetSupportedChildren(key);

            if (supChildren !== false) {
                child[children[i]] = LMSGetChildren(key, supChildren);
                continue;
            }

            child[children[i]] = LMSGetValue(key);
        }
    }

    return child;
}

/**
 * This function returns the supported data model elements of the a given data model defined category or element. If the given name
 * does not have any supported children, then a boolean of false will be returned.
 *
 * @param {str} name data model defined category or element
 * @returns {str[]|bool}
 */
function LMSGetSupportedChildren(name) {
    const result = LMSGetValue(name + '._children');
    if (result === "") {
        return false;
    }
    return result.split(',');
}

/**
 * This function return the number of stored elements to this data model element or category. If the element does not support
 * multiple stored elements then it will return false.
 *
 * @param {str} name data model defined category or element
 * @returns {number|bool}
 */
function LMSGetNumberStored(name) {
    const result = LMSGetValue(name + "._count");
    if (result === "") {
        return false;
    }
    return parseInt(result);
}

/**
 * This function send the CMI aka DataModel to the third layer.
 *
 * @returns {null}
 */
// eslint-disable-next-line no-unused-vars
function postLMSDataModel() {
    const datamodel = LMSGetDataModel();
    embeddedWindow.postMessage(
        { function: 'LMSSetDataModel', arguments: [datamodel] },
        ORIGIN
    );
}

/**
 * Method returns a boolean value if getting varaible through LMSGetValue is allowed.
 *
 * @param {str} name data model defined category or element.
 * @return {bool}
 */
function LMSGetValueAllowed(name) {
    // The name might contain a number, which is used as a index.
    // We'll a group of numbers with a *.
    return ALLOWED_TO_LMSGETVALUE.includes(name.replace('/\d+/g', '*'));
}
