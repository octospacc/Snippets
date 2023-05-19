// ==UserScript==
// @name        TP-Link Hacks
// @description Let's hack some useful features into the TP-Link WebUI!
// @version     1.0
// @author      OctoSpacc
// @namespace   https://octt.eu.org
// @match       http://192.168.*.*/*
// @match       http://tplinkrepeater.net/*
// @grant       GM_getValue
// @grant       GM_setValue
// @grant       window.close
// ==/UserScript==

(function(){
GM_setValue('Password', GM_getValue('Password', 'admin'));
GM_setValue('IPs',      GM_getValue('IPs', []));

var Ips = GM_getValue('IPs');
var IsPageAllowed = (Ips.length == 0 || (Ips.length > 0 && Ips.includes(location.hostname)));

if (IsPageAllowed) {

//var Intvs = {};
//var Cmd = new URLSearchParams(window.location.hash.toLowerCase()).get('hackcmd');
//if (Cmd) {
//  localStorage.setItem('PrevCmd', Cmd);
//} else {
//  Cmd = localStorage.getItem('PrevCmd');
//  localStorage.removeItem('PrevCmd');
//};

// Note: localStorage is reset on new login and isn't reliable, we must use sessionStorage
var Opts = new URLSearchParams(location.hash.toLowerCase()).get('tplinkhacks');
if (Opts !== null) {
  sessionStorage.setItem('HacksPrevOpts', Opts);
} else {
  Opts = sessionStorage.getItem('HacksPrevOpts');
  sessionStorage.removeItem('HacksPrevOpts');
};
Opts = JSON.parse(Opts);

function qs(Query) {
  return document.querySelector(Query);
};

//function IntvKey(Name) {
//  return `${Date.now()}-${Name}`;
//};

function WaitTill(Cond, Fun) {
  /*Intvs[Key]*/
  var Intv = setInterval(function(){
    if (Cond()) {
      //console.log(Key);
      clearInterval(Intv/*Intvs[Key]*/);
      Fun();
    };
  }, 100);
  //function Call() {
  //  if (Cond) Fun();
  //  else { Rept(); console.log(0); }
  //};
  //function Rept() { setTimeout(Call, 50); };
  //Rept();
};

function WaitElClick(Query, Fun) {
  WaitTill(function(){ return qs(Query); }, function(){
    qs(Query).click();
    if (Fun) Fun();
  });
};

function AfterCmd() {
  console.log('[TP-Link Hacks] Command completed.');
  if (Opts.closeafter) {
    var Wait = 5000;
    console.log(`[TP-Link Hacks] CloseAfter was specified. The page will close itself in ${Wait/1000} seconds.`);
    setTimeout(function(){ window.close(); }, 5000);
  };
};

function ReLogin() {
  // Attempt to logout if needed
  //qs('#logout-button a').click();
  //qs('#global-confirm #global-confirm-btn-ok a').click();

  // Attempt to login if needed
  //try {
  WaitTill(function(){ return qs('#local-login-pwd input'); }, function(){
    qs('#local-login-pwd input').value = GM_getValue('Password');
    WaitElClick('#local-login-button a');
  });
    //qs('#local-login-pwd input').value = GM_getValue('Password', 'admin');
    //qs('#local-login-button a').click();
  //} catch(Ex) {};
};

function Reboot() {
  WaitElClick('#main-menu *[navi-value="advanced"] a', function(){
  WaitElClick('#navigator *[navi-value="reboot"] a', function(){
  WaitElClick('#reboot-button a', function(){
  WaitElClick('#global-confirm #global-confirm-btn-ok a', function(){
    AfterCmd();
  }); }); }); });
};

function OpenLogs() {
  WaitElClick('#main-menu *[navi-value="advanced"] a', function(){
  WaitElClick('#navigator *[navi-value="sysLog"] a', function(){
    AfterCmd();
  }); });
};

function Main() {
  if (Opts && Opts.cmd) {
    console.log(`[TP-Link Hacks] Trying to call command: ${Opts.cmd}.`);
    ReLogin();
    switch (Opts.cmd.toLowerCase()) {
      case 'reboot': Reboot(); break;
      case 'openlogs': OpenLogs(); break;
    };
  };
};

window.addEventListener('load', function(){
  // Wait a bit for the WebUI to settle down, accounting for slow machines/connections
  setTimeout(Main, 1500);
});

} else {
  console.log('[TP-Link Hacks] Script matched this page but the IP whitelist check is negative. Stopping.');
};
})();
