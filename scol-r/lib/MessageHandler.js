"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.MessageEmitter = exports.MessageReceiver = void 0;
function MessageReceiver(win, sourceOrigin, adapter) {
    this.timeoutId = null;
    win.addEventListener("message", function (e) {
        var _this = this;
        if (e.origin !== sourceOrigin)
            return;
        var functionName = e.data["function"];
        var functionArgs = e.data["arguments"];
        if (functionName &&
            functionArgs &&
            typeof this[functionName] === "function") {
            this[functionName].apply(this, functionArgs);
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
            }
            this.timeoutId = setTimeout(function () {
                _this.commit();
                _this.timeoutId = null;
            }, 500);
        }
    }.bind(this));
    this.commit = function () {
        adapter.LMSCommit();
    };
    this.setTitle = function (title) {
        document.title = title;
    };
    this.setScore = function (score) {
        adapter.setScore(score);
    };
    this.setLessonStatus = function (lessonStatus) {
        adapter.setLessonStatus(lessonStatus);
    };
    this.setObjectives = function (objectivesIds) {
        if (adapter.objectivesAreAvailable) {
            adapter.setObjectives(objectivesIds);
        }
    };
    this.setObjectiveScore = function (objectiveId, score) {
        if (adapter.objectivesAreAvailable) {
            adapter.setObjectiveScore(objectiveId, score);
        }
    };
    this.setObjectiveStatus = function (objectiveId, status) {
        if (adapter.objectivesAreAvailable) {
            adapter.setObjectiveStatus(objectiveId, status);
        }
    };
}
exports.MessageReceiver = MessageReceiver;
var MessageEmitter = /** @class */ (function () {
    function MessageEmitter(lmsOrigin) {
        this.currentWindow = window.parent || window.opener;
        this.lmsOrigin = lmsOrigin;
    }
    MessageEmitter.prototype.sendMessage = function (name, values) {
        this.currentWindow.postMessage({
            function: name,
            arguments: values,
        }, this.lmsOrigin);
    };
    MessageEmitter.prototype.setLessonStatus = function (status) {
        this.sendMessage("setLessonStatus", [status]);
    };
    MessageEmitter.prototype.setScore = function (score) {
        this.sendMessage("setScore", [score]);
    };
    MessageEmitter.prototype.setObjectives = function (objectives) {
        this.sendMessage("setObjectives", [objectives]);
    };
    MessageEmitter.prototype.setObjectiveScore = function (objectiveId, score) {
        this.sendMessage("setObjectiveScore", [objectiveId, score]);
    };
    MessageEmitter.prototype.setObjectiveStatus = function (objectiveId, status) {
        this.sendMessage("setObjectiveStatus", [objectiveId, status]);
    };
    return MessageEmitter;
}());
exports.MessageEmitter = MessageEmitter;
