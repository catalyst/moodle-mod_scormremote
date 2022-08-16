"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.SCORMAdapter = void 0;
var SCORMAdapter = /** @class */ (function () {
    function SCORMAdapter(errorCallback) {
        if (errorCallback === void 0) { errorCallback = function () { }; }
        this._ignorableErrorCodes = [
            { code: 0 },
            { code: 403, scope: "2004" },
        ];
        this._API = null;
        this._isSCORM2004 = false;
        this._errorCallback = errorCallback;
        this._findAndSetAPI();
    }
    Object.defineProperty(SCORMAdapter.prototype, "foundAPI", {
        get: function () {
            return !!this._API;
        },
        enumerable: false,
        configurable: true
    });
    SCORMAdapter.prototype._findAndSetAPI = function () {
        var _this = this;
        if (typeof window === "undefined") {
            console.error("Unable to find an API adapter");
        }
        else {
            var theAPI = this._findAPIInWindow(window);
            if (theAPI == null &&
                window.opener != null &&
                typeof window.opener != "undefined") {
                theAPI = this._findAPIInWindow(window.opener);
            }
            if (theAPI == null) {
                console.error("Unable to find an API adapter");
            }
            else {
                this._API = theAPI["API"];
                this._isSCORM2004 = theAPI["isSCORM2004"];
                this._ignorableErrorCodes = this._ignorableErrorCodes.filter(function (_a) {
                    var scope = _a.scope;
                    return !scope || (_this._isSCORM2004 ? scope === "2004" : scope === "1.2");
                });
            }
            if (this._API == null) {
                console.error("Couldn't find the API!");
            }
        }
    };
    SCORMAdapter.prototype._findAPIInWindow = function (win) {
        var findAPITries = 0;
        while (win.API == null &&
            win.API_1484_11 == null &&
            win.parent != null &&
            win.parent != win) {
            findAPITries++;
            if (findAPITries > 7) {
                console.error("Error finding API -- too deeply nested.");
                return null;
            }
            win = win.parent;
        }
        if (win.API) {
            return {
                API: win.API,
                isSCORM2004: false,
            };
        }
        else if (win.API_1484_11) {
            return {
                API: win.API_1484_11,
                isSCORM2004: true,
            };
        }
        return null;
    };
    SCORMAdapter.prototype._callAPIFunction = function (fun, args) {
        if (args === void 0) { args = [""]; }
        if (this._API == null) {
            this._warnNOAPI();
            return;
        }
        if (this._isSCORM2004 && fun.indexOf("LMS") == 0) {
            fun = fun.substr(3);
        }
        else if (!this._isSCORM2004 && !(fun.indexOf("LMS") == 0)) {
            fun = "LMS" + fun;
        }
        console.info("[SCOL-R] Calling a scorm api function", { fun: fun, args: args });
        return this._API[fun].apply(this._API, args);
    };
    SCORMAdapter.prototype._handleError = function (functionName) {
        var lastErrorCode = this.LMSGetLastError();
        var lastErrorString = this.LMSGetErrorString(lastErrorCode);
        var lastErrorDiagnostic = this.LMSGetDiagnostic(lastErrorCode);
        if (!this._ignorableErrorCodes.some(function (_a) {
            var code = _a.code;
            return code === lastErrorCode;
        })) {
            console.warn(functionName, "An error occured on the SCORM API: code " + lastErrorCode + ", message: " + lastErrorString, lastErrorDiagnostic);
            this._errorCallback(lastErrorString, lastErrorDiagnostic && lastErrorDiagnostic != lastErrorCode
                ? lastErrorDiagnostic
                : null);
        }
    };
    SCORMAdapter.prototype._warnNOAPI = function () {
        console.warn("Cannot execute this function because the SCORM API is not available.");
        this._errorCallback("apiNotFound");
    };
    SCORMAdapter.prototype.LMSInitialize = function () {
        var functionName = "Initialize";
        var result = this._callAPIFunction(functionName);
        var lastErrorCode = this.LMSGetLastError();
        var success = eval(result.toString()) ||
            (this._isSCORM2004
                ? lastErrorCode === 103 // 103 in 2004.* = already initialized
                : lastErrorCode === 101); // 101 in 1.2 = already initialized
        return success || this._handleError(functionName);
    };
    SCORMAdapter.prototype.LMSTerminate = function () {
        var functionName = this._isSCORM2004 ? "Terminate" : "Finish";
        var result = this._callAPIFunction(functionName);
        var success = eval(result.toString());
        return success || this._handleError(functionName);
    };
    SCORMAdapter.prototype.LMSGetValue = function (name) {
        var functionName = "GetValue";
        var value = this._callAPIFunction(functionName, [name]);
        var success = this.LMSGetLastError() === 0;
        return success ? value : this._handleError(functionName + ": " + name);
    };
    SCORMAdapter.prototype.LMSSetValue = function (name, value) {
        var functionName = "SetValue";
        var result = this._callAPIFunction(functionName, [name, value]);
        var success = eval(result.toString());
        return success || this._handleError(functionName + ": {" + name + ": " + value + "}");
    };
    SCORMAdapter.prototype.LMSCommit = function () {
        var result = this._callAPIFunction("Commit");
        var success = eval(result.toString());
        return success || this._errorCallback("commitFailed");
    };
    SCORMAdapter.prototype.LMSGetLastError = function () {
        return parseInt(this._callAPIFunction("GetLastError"));
    };
    SCORMAdapter.prototype.LMSGetErrorString = function (errorCode) {
        return this._callAPIFunction("GetErrorString", [errorCode]);
    };
    SCORMAdapter.prototype.LMSGetDiagnostic = function (errorCode) {
        return this._callAPIFunction("GetDiagnostic", [errorCode]);
    };
    SCORMAdapter.prototype.getDataFromLMS = function () {
        return this.LMSGetValue("cmi.launch_data");
    };
    SCORMAdapter.prototype.getLearnerId = function () {
        var CMIVariableName = this._isSCORM2004
            ? "cmi.learner_id"
            : "cmi.core.student_id";
        return this.LMSGetValue(CMIVariableName);
    };
    SCORMAdapter.prototype.setScore = function (score) {
        var CMIVariableName = this._isSCORM2004
            ? "cmi.score.raw"
            : "cmi.core.score.raw";
        this.LMSSetValue(CMIVariableName, score);
    };
    SCORMAdapter.prototype.getScore = function () {
        var CMIVariableName = this._isSCORM2004
            ? "cmi.score.raw"
            : "cmi.core.score.raw";
        var score = this.LMSGetValue(CMIVariableName);
        return score;
    };
    SCORMAdapter.prototype.getLessonStatus = function () {
        var CMIVariableName = this._isSCORM2004
            ? "cmi.completion_status"
            : "cmi.core.lesson_status";
        return this.LMSGetValue(CMIVariableName);
    };
    SCORMAdapter.prototype.setLessonStatus = function (lessonStatus) {
        if (this._isSCORM2004) {
            var successStatus = "unknown";
            if (lessonStatus === "passed" || lessonStatus === "failed")
                successStatus = lessonStatus;
            this.LMSSetValue("cmi.success_status", successStatus);
            var completionStatus = "unknown";
            if (lessonStatus === "passed" || lessonStatus === "completed") {
                completionStatus = "completed";
            }
            else if (lessonStatus === "incomplete") {
                completionStatus = "incomplete";
            }
            else if (lessonStatus === "not attempted" ||
                lessonStatus === "browsed") {
                completionStatus = "not attempted";
            }
            this.LMSSetValue("cmi.completion_status", completionStatus);
        }
        else {
            this.LMSSetValue("cmi.core.lesson_status", lessonStatus);
        }
    };
    SCORMAdapter.prototype.setSessionTime = function (msSessionTime) {
        var CMIVariableName = this._isSCORM2004
            ? "cmi.session_time"
            : "cmi.core.session_time";
        var duration;
        if (this._isSCORM2004) {
            duration = Math.round(msSessionTime / 1000);
        }
        else {
            var hours = Math.floor(msSessionTime / 1000 / 60 / 60);
            msSessionTime -= hours * 1000 * 60 * 60;
            var minutes = Math.floor(msSessionTime / 1000 / 60);
            msSessionTime -= minutes * 1000 * 60;
            var seconds = Math.floor(msSessionTime / 1000);
            var formattedSeconds = seconds < 10 ? "0" + seconds : seconds;
            var formattedMinutes = minutes < 10 ? "0" + minutes : minutes;
            var formattedHours = hours < 10 ? "0" + hours : hours;
            duration =
                formattedHours + ":" + formattedMinutes + ":" + formattedSeconds;
        }
        this.LMSSetValue(CMIVariableName, duration);
    };
    Object.defineProperty(SCORMAdapter.prototype, "objectivesAreAvailable", {
        get: function () {
            return this.LMSGetValue("cmi.objectives._children") !== null;
        },
        enumerable: false,
        configurable: true
    });
    SCORMAdapter.prototype.setObjectives = function (objectivesIds) {
        var _this = this;
        objectivesIds.forEach(function (objectiveId, index) {
            _this.LMSSetValue("cmi.objectives." + index + ".id", objectiveId);
        });
    };
    Object.defineProperty(SCORMAdapter.prototype, "objectives", {
        get: function () {
            var objectives = [];
            var objectivesNbr = this.LMSGetValue("cmi.objectives._count");
            for (var index = 0; index < objectivesNbr; index++) {
                objectives.push(this.LMSGetValue("cmi.objectives." + index + ".id"));
            }
            return objectives;
        },
        enumerable: false,
        configurable: true
    });
    SCORMAdapter.prototype.setObjectiveScore = function (objectiveId, score) {
        var objectivesNbr = this.LMSGetValue("cmi.objectives._count");
        for (var index = 0; index < objectivesNbr; index++) {
            var storedObjectiveId = this.LMSGetValue("cmi.objectives." + index + ".id");
            if (objectiveId === storedObjectiveId) {
                this.LMSSetValue("cmi.objectives." + index + ".score.raw", score);
                return;
            }
        }
    };
    SCORMAdapter.prototype.setObjectiveStatus = function (objectiveId, status) {
        var objectivesNbr = this.LMSGetValue("cmi.objectives._count");
        for (var index = 0; index < objectivesNbr; index++) {
            var storedObjectiveId = this.LMSGetValue("cmi.objectives." + index + ".id");
            if (objectiveId === storedObjectiveId) {
                if (this._isSCORM2004) {
                    this.LMSSetValue("cmi.objectives." + index + ".success_status", status === "completed" ? "passed" : "unknown");
                    this.LMSSetValue("cmi.objectives." + index + ".completion_status", status === "completed" ? "completed" : "incomplete");
                }
                else {
                    this.LMSSetValue("cmi.objectives." + index + ".status", status === "completed" ? "passed" : "incomplete");
                }
                return;
            }
        }
    };
    SCORMAdapter.prototype.getObjectiveScore = function (objectiveId) {
        var objectivesNbr = this.LMSGetValue("cmi.objectives._count");
        for (var index = 0; index < objectivesNbr; index++) {
            var storedObjectiveId = this.LMSGetValue("cmi.objectives." + index + ".id");
            if (objectiveId === storedObjectiveId) {
                return this.LMSGetValue("cmi.objectives." + index + ".score.raw");
            }
        }
    };
    SCORMAdapter.prototype.setSuspendData = function (data) {
        this.LMSSetValue("cmi.suspend_data", data);
    };
    Object.defineProperty(SCORMAdapter.prototype, "suspendData", {
        get: function () {
            return this.LMSGetValue("cmi.suspend_data");
        },
        enumerable: false,
        configurable: true
    });
    return SCORMAdapter;
}());
exports.SCORMAdapter = SCORMAdapter;
