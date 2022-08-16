"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __rest = (this && this.__rest) || function (s, e) {
    var t = {};
    for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
        t[p] = s[p];
    if (s != null && typeof Object.getOwnPropertySymbols === "function")
        for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
            if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
                t[p[i]] = s[p[i]];
        }
    return t;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.ManifestGenerator = exports.Sco = void 0;
var Sco = /** @class */ (function () {
    function Sco(scoID, scoTitle, author, learningTime, resources) {
        this.scoID = scoID;
        this.scoTitle = scoTitle;
        this.author = author;
        this.learningTime = learningTime;
        this.resources = resources || [];
    }
    return Sco;
}());
exports.Sco = Sco;
var formatLearningTime = function (learningTime) {
    var intHours = Math.floor(learningTime / 60);
    var hours = intHours > 10 ? intHours : "0" + intHours;
    var intMinutes = intHours > 0 ? learningTime - intHours * 60 : learningTime;
    var minutes = intMinutes > 10 ? intMinutes : "0" + intMinutes;
    return hours + ":" + minutes + ":00";
};
var removeSpecialChars = function (obj) {
    return Object.entries(obj).reduce(function (acc, _a) {
        var _b;
        var key = _a[0], value = _a[1];
        return (__assign(__assign({}, acc), (_b = {}, _b[key] = value.replace(/&/g, "-"), _b)));
    }, {});
};
function ManifestGenerator(_a) {
    var courseId = _a.courseId, _b = _a.scoList, scoList = _b === void 0 ? [] : _b, _c = _a.sharedResources, sharedResources = _c === void 0 ? [] : _c, _d = _a.totalLearningTime, totalLearningTime = _d === void 0 ? 0 : _d, dataFromLms = _a.dataFromLms, _e = _a.scormVersion, scormVersion = _e === void 0 ? "1.2" : _e, props = __rest(_a, ["courseId", "scoList", "sharedResources", "totalLearningTime", "dataFromLms", "scormVersion"]);
    var _f = removeSpecialChars(props), courseTitle = _f.courseTitle, courseAuthor = _f.courseAuthor;
    var courseGlobalLearningTime = scoList.length
        ? scoList.reduce(function (acc, sco) { return acc + sco.learningTime; }, 0)
        : totalLearningTime;
    return "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n    <manifest xmlns=\"http://www.imsproject.org/xsd/imscp_rootv1p1p2\" identifier=\"" + courseId + "\" version=\"1.0\" xmlns:imsmd=\"http://www.imsglobal.org/xsd/imsmd_rootv1p2p1\" xmlns:adlcp=\"http://www.adlnet.org/xsd/adlcp_rootv1p2\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.imsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd\">\n      <metadata>\n        <schema>ADL SCORM</schema>\n        <schemaversion>" + scormVersion + "</schemaversion>\n        <imsmd:lom xmlns=\"http://ltsc.ieee.org/xsd/LOM\">\n          <imsmd:general>\n            <imsmd:identifier>" + courseId + "</imsmd:identifier>\n          </imsmd:general>\n          <imsmd:lifecycle>\n            <imsmd:contribute>\n              <imsmd:role>\n                <imsmd:source>\n                  <imsmd:langstring xml:lang=\"fr\">LOMv1.0</imsmd:langstring>\n                </imsmd:source>\n                <imsmd:value>\n                  <imsmd:langstring xml:lang=\"fr\">Author</imsmd:langstring>\n                </imsmd:value>\n              </imsmd:role>\n              <imsmd:centity>\n                <imsmd:vcard>\n                  begin:vcard\n                  fn:" + courseAuthor + "\n                  end:vcard\n                </imsmd:vcard>\n              </imsmd:centity>\n            </imsmd:contribute>\n          </imsmd:lifecycle>\n          <imsmd:educational>\n            <imsmd:typicallearningtime>\n              <imsmd:datetime>" + formatLearningTime(courseGlobalLearningTime) + "</imsmd:datetime>\n            </imsmd:typicallearningtime>\n          </imsmd:educational>\n        </imsmd:lom>\n      </metadata>\n      <organizations default=\"Org1\">\n        <organization identifier=\"Org1\">\n          <title>" + courseTitle + "</title>\n          " + scoList
        .map(function (_a) {
        var scoID = _a.scoID, learningTime = _a.learningTime, resources = _a.resources, props = __rest(_a, ["scoID", "learningTime", "resources"]);
        var _b = removeSpecialChars(props), scoTitle = _b.scoTitle, author = _b.author;
        return "<item identifier=\"" + scoTitle + "\" identifierref=\"resource_" + scoID + "\" isvisible=\"true\">\n                <title>" + scoTitle + "</title>\n                <adlcp:dataFromLMS>" + (dataFromLms !== null && dataFromLms !== void 0 ? dataFromLms : courseId + ":" + scoID) + "</adlcp:dataFromLMS>\n                <metadata>\n                  <imsmd:lom xmlns=\"http://ltsc.ieee.org/xsd/LOM\">\n                    <imsmd:general>\n                    <imsmd:identifier>" + scoID + "</imsmd:identifier>\n                    </imsmd:general>\n                    <imsmd:lifecycle>\n                    <imsmd:contribute>\n                      <imsmd:role>\n                      <imsmd:source>\n                        <imsmd:langstring xml:lang=\"fr\">LOMv1.0</imsmd:langstring>\n                      </imsmd:source>\n                      <imsmd:value>\n                        <imsmd:langstring xml:lang=\"fr\">Author</imsmd:langstring>\n                      </imsmd:value>\n                      </imsmd:role>\n                      <imsmd:centity>\n                        <imsmd:vcard>\n                          begin:vcard\n                          fn:" + author + "\n                          end:vcard\n                        </imsmd:vcard>\n                      </imsmd:centity>\n                    </imsmd:contribute>\n                    </imsmd:lifecycle>\n                    <imsmd:educational>\n                    <imsmd:typicallearningtime>\n                      <imsmd:datetime>" + formatLearningTime(learningTime) + "</imsmd:datetime>\n                    </imsmd:typicallearningtime>\n                    </imsmd:educational>\n                  </imsmd:lom>\n                </metadata>\n              </item>";
    })
        .join("\n") + "\n        </organization>\n      </organizations>\n      <resources>\n      " + ((sharedResources === null || sharedResources === void 0 ? void 0 : sharedResources.length)
        ? "<resource adlcp:scormtype=\"asset\" type=\"webcontent\" identifier=\"shared_resources\">\n            " + sharedResources
            .map(function (resource) {
            return "<file href=\"" + resource + "\"/>";
        })
            .join("\n") + "\n          </resource>"
        : "") + "\n      " + scoList
        .map(function (sco) {
        return "<resource adlcp:scormtype=\"sco\" type=\"webcontent\" identifier=\"resource_" + sco.scoID + "\" href=\"./" + sco.scoID + "/index.html\">\n            " + ((sharedResources === null || sharedResources === void 0 ? void 0 : sharedResources.length)
            ? '<dependency identifierref="shared_resources"/>'
            : "") + "\n            " + sco.resources
            .map(function (resource) {
            return "<file href=\"" + resource + "\"/>";
        })
            .join("\n") + "\n          </resource>";
    })
        .join("\n") + "\n      </resources>\n    </manifest>";
}
exports.ManifestGenerator = ManifestGenerator;
