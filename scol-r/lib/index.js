"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    Object.defineProperty(o, k2, { enumerable: true, get: function() { return m[k]; } });
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __exportStar = (this && this.__exportStar) || function(m, exports) {
    for (var p in m) if (p !== "default" && !Object.prototype.hasOwnProperty.call(exports, p)) __createBinding(exports, m, p);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.LessonStatus = exports.scormVersions = exports.libFiles = exports.MessageEmitter = void 0;
__exportStar(require("./ManifestGenerator"), exports);
__exportStar(require("./SCORMAdapter"), exports);
__exportStar(require("./HTMLGenerator"), exports);
var MessageHandler_1 = require("./MessageHandler");
Object.defineProperty(exports, "MessageEmitter", { enumerable: true, get: function () { return MessageHandler_1.MessageEmitter; } });
exports.libFiles = [
    "loadContent.js",
    "MessageHandler.js",
    "SCORMAdapter.js",
];
exports.scormVersions = [
    "1.2",
    "2004 3rd Edition",
    "2004 4th Edition",
];
var LessonStatus;
(function (LessonStatus) {
    LessonStatus["Passed"] = "passed";
    LessonStatus["Failed"] = "failed";
    LessonStatus["Completed"] = "completed";
    LessonStatus["Incomplete"] = "incomplete";
    LessonStatus["NotAttempted"] = "not attempted";
    LessonStatus["Browsed"] = "browsed";
})(LessonStatus = exports.LessonStatus || (exports.LessonStatus = {}));
