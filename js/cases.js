﻿//debugger;
// To manage submits to cases.front.php
var loc_split = window.location.href.split('/');
var GLPI_HTTP_CASE_FORM = window.location.href.split('/', loc_split.length-2 ).join('/') + '/plugins/processmaker/front/cases.front.php'; // http://hostname/glpi/...
// to manage reloads
var GLPI_RELOAD_PARENT = window; //.location;
var GLPI_DURING_RELOAD = false;

// used to find an element in a list and to hide it!
function bGLPIHideElement(eltList, attribute, value) {
    var ret = false;
    for (var i = 0; i < eltList.length && !ret; i++) {
        var node = eltList[i];
        if (node.getAttribute(attribute) == value) {
            // hide the link
            node.style.display = 'none';
            ret = true;
        }
    }
    return ret;
}

function showMask(elt) {
    if( !elt.defaultPrevented ) {
        Ext.getBody().moveTo(0, 0);
        var myMask = new Ext.LoadMask(Ext.getBody(), { removeMask: false });
        myMask.show();
    }
};


function onTaskFrameLoad(delIndex) {
    //alert("Loaded frame " + delIndex);
    var taskFrameId = "caseiframe-" + delIndex;
    var bShowHideNextStep = false ; // not done yet!
    var bHideClaimCancelButton = false; // To manage 'Claim' button
    var taskFrameTimerCounter = 0;
    var redimIFrame = false;

    var taskFrameTimer = window.setInterval(function () {
        try {
            var locContentDocument;
            var taskFrame = document.getElementById(taskFrameId);

            if (taskFrame != undefined && taskFrame.contentDocument != undefined) {
                // here we've caught the content of the iframe

                // then look if btnGLPISendRequest exists,
                locContentDocument = taskFrame.contentDocument;
                var locElt = locContentDocument.getElementById('form[btnGLPISendRequest]');
                if (!bShowHideNextStep && locElt != undefined ) {
                    var linkList = locContentDocument.getElementsByTagName('a');
                    if (bGLPIHideElement(linkList, 'href', 'cases_Step?TYPE=ASSIGN_TASK&UID=-1&POSITION=10000&ACTION=ASSIGN')) {
                        // the next step link is hidden

                        // if yes then change the link behind the button itself
                        locElt.type = 'submit';
                        locElt.onclick = null; // in order to force use of the action of form POST
                        var formList = locContentDocument.getElementsByTagName('form');

                        // if yes then change the action of the form POST
                        var node = formList[0]; // must have one element in list: in a dynaform there is one and only one HTML form
                        var action = node.action.split('?');
                        node.action = GLPI_HTTP_CASE_FORM + '?' + action[1] + '&DEL_INDEX=' + delIndex;

                        // try to add showMask function to submit event
                        //locElt.addEventListener( 'click', showMask ); // it is not good if a validation error occurs
                        node.addEventListener('submit', showMask, true);
                    } else {
                        // then hide the button itself
                        locElt.style.display = 'none';
                    }
                    
                    bShowHideNextStep = true;
                }

                // Hide 'Cancel' button on 'Claim' form
                var cancelButton = locContentDocument.getElementById('form[BTN_CANCEL]');
                if (cancelButton != undefined && !bHideClaimCancelButton) {
                    cancelButton.style.display = 'none';
                    // to manage Claim
                    var formList = locContentDocument.getElementsByTagName('form');
                    var node = formList[0]; // must have one element in list: in a dynaform there is one and only one HTML form
                    var action = node.action.split('?');
                    node.action = GLPI_HTTP_CASE_FORM + '?' + action[1] + '&DEL_INDEX=' + delIndex;
                    bHideClaimCancelButton = true;
                    node.addEventListener('submit', showMask);
                }

                // to force immediat reload of GLPI item form
                var forcedReload = locContentDocument.getElementById('GLPI_FORCE_RELOAD');
                if (forcedReload != undefined && !GLPI_DURING_RELOAD) {
                    //showMask();
                    GLPI_DURING_RELOAD = true; // to prevent double reload
                    window.clearInterval(taskFrameTimer); // stop timer                
                    GLPI_RELOAD_PARENT.location.reload();
                }

                // try to redim caseIFrame
                if (!redimIFrame) {
                    var locElt = locContentDocument.getElementsByTagName("table")[0];
                    var newHeight = (locElt.clientHeight < 400 ? 400 : locElt.clientHeight) + locElt.offsetParent.offsetTop ;
                    //locElt.clientHeight = newHeight; // don't know if this is neccessary!!! --> bugs on IE8
                    tabs.getItem('task-' + delIndex).setHeight(newHeight);
                    taskFrame.height = newHeight ;
                    redimIFrame = true;
                }
            }

            taskFrameTimerCounter = taskFrameTimerCounter + 1;

            if (taskFrameTimerCounter > 3000 || bShowHideNextStep || bHideClaimCancelButton) // either timeout or hiding is done
                window.clearInterval(taskFrameTimer);

        } catch (evt) {
            // nothing to do here for the moment
        }

    }, 10);

}

function clearClass(lociFrame) {

    var otherFrameTimerCounter = 0;
    var otherFrameTimer = window.setInterval(function () {
        try {
            var locElt = lociFrame.contentDocument.getElementsByTagName('body')[0];
            if (locElt != undefined && locElt.className != '') {
                //debugger;
                locElt.className = '';
                window.clearInterval(otherFrameTimer);
            } else {
                otherFrameTimerCounter = otherFrameTimerCounter + 1;
                if (otherFrameTimerCounter > 3000 )
                    window.clearInterval(otherFrameTimer);
            }
        } catch (ev) {
            
        }
    }, 10);
}

function onOtherFrameLoad(tabPanelName, frameName, eltTagName) {
    var otherFrameId = frameName; //tabPanelName ; //+ 'Frame';
    var otherFrameTimerCounter = 0;
    var redimIFrame = false;
    //debugger;
    var otherFrameTimer = window.setInterval(function () {
        try {

            var locContentDocument;
            var otherFrame = document.getElementById(otherFrameId);

            if (otherFrame != undefined && otherFrame.contentDocument != undefined) {
                // here we've caught the content of the iframe
                clearClass(otherFrame);

                locContentDocument = otherFrame.contentDocument;
                
                // try to redim otherFrame
                // and tabPanel
                if (!redimIFrame) {
                    var locElt = locContentDocument.getElementsByTagName(eltTagName)[0];
                    if (locElt != undefined) {
                        var newHeight ;
                        if (locElt.offsetParent)
                            newHeight = (locElt.clientHeight < 400 ? 400 : locElt.clientHeight) + locElt.offsetParent.offsetTop ;
                        else
                            newHeight = (locElt.clientHeight < 400 ? 400 : locElt.clientHeight) ;
                        if (locElt.scrollHeight && locElt.scrollHeight > newHeight )
                            newHeight = locElt.scrollHeight ;
                        tabs.getItem(tabPanelName).setHeight(newHeight);
                        otherFrame.height = newHeight;
                        redimIFrame = true;
                    }
                }
            }

            otherFrameTimerCounter = otherFrameTimerCounter + 1;

            if (otherFrameTimerCounter > 3000 || redimIFrame)
                window.clearInterval(otherFrameTimer);

        } catch (ev) {
            // nothing to do here for the moment
        }
    }, 10);

}

