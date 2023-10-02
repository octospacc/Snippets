// ==UserScript==
// @name        Vodafone Station Revolution Web Hacks
// @description Let's hack some useful features into the Vodafone Station Revolution WebUI!
// @version     1.0
// @author      OctoSpacc
// @namespace   https://octt.eu.org
// @match       http://192.168.*.*/*
// @match       http://vodafone.station/*
// @grant       GM_getValue
// @grant       GM_setValue
// @grant       window.close
// ==/UserScript==

(function(){
GM_setValue('Password', GM_getValue('Password', 'admin'));
GM_setValue('IPs',      GM_getValue('IPs',      []));

var Ips = GM_getValue('IPs');
var IsPageAllowed = (Ips.length == 0 || (Ips.length > 0 && Ips.includes(location.hostname)));

if (IsPageAllowed) {
  // Note: localStorage is reset on new login and isn't reliable, we must use sessionStorage
  var Opts = new URLSearchParams(location.hash.toLowerCase()).get('vodafonestationwebhacks');
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

  function WaitTill(Cond, Fun) {
    var Intv = setInterval(function(){
      if (Cond()) {
        clearInterval(Intv);
        Fun();
      };
    }, 100);
  };

  function WaitElClick(Query, Fun) {
    WaitTill(function(){ return qs(Query); }, function(){
      qs(Query).click();
      if (Fun) Fun();
    });
  };

  function AfterCmd() {
    console.log('[Vodafone Station Revolution Web Hacks] Command completed.');
    if (Opts.closeafter) {
      var Wait = 7500;
      console.log(`[Vodafone Station Revolution Web Hacks] CloseAfter was specified. The page will close itself in ${Wait/1000} seconds.`);
      setTimeout(function(){ window.close(); }, Wait);
    };
  };

  function ReLogin() {
    WaitTill(function(){ return qs('input#login_Password'); }, function(){
      qs('input#login_Password').value = GM_getValue('Password');
      WaitElClick('#btn_login');
    });
  };

  function ExpertMode() {
    WaitElClick('#modeSelectorOptions *[data-val="Expert"]');
  };

  function Reboot() {
    setTimeout(function(){
      location.hash = '#cat=status-and-support_restart';
      WaitElClick('#btn_restart', function(){
      WaitElClick('#popUp_RestartConfirmationMessage_Button_Apply', function(){
        AfterCmd();
      }); });
    }, 1500);
  };

  function OpenLogs() {
    setTimeout(function(){
      location.hash = '#cat=status-and-support_event-log';
      AfterCmd();
    }, 1500);
  };

  function Main() {
    if (Opts && Opts.cmd) {
      console.log(`[Vodafone Station Revolution Web Hacks] Trying to call command: ${Opts.cmd}.`);
      ReLogin();
      ExpertMode();
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
  console.log('[Vodafone Station Revolution Web Hacks] Script matched this page but the IP whitelist check is negative. Stopping.');
};
})();
