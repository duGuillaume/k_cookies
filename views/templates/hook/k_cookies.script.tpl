<script>
  tarteaucitron.init({
    "cookieCMSLink": "{$cookieCMSLink}", /* Page explicative sur les cookies */
    "hashtag": "#{$hashtag}", /* Ouverture automatique du panel avec le hashtag */
    "highPrivacy": {if $highPrivacy}true{else}false{/if}, /* désactiver le consentement implicite (en naviguant) ? */
    "orientation": "{$orientation}", /* le bandeau doit être en haut (top) ou en bas (bottom) ? */
    "adblocker": {if $adblocker}true{else}false{/if}, /* Afficher un message si un adblocker est détecté */
    "showAlertSmall": {if $showAlertSmall}true{else}false{/if}, /* afficher le petit bandeau en bas à droite ? */
    "cookieslist": {if $cookieslist}true{else}false{/if}, /* Afficher la liste des cookies installés ? */
    "removeCredit": {if $removeCredit}true{else}false{/if}, /* supprimer le lien vers la source ? */
    "handleBrowserDNTRequest": {if $handleBrowserDNTRequest}true{else}false{/if}, /* Répondre au DoNotTrack du navigateur ?*/
    "cookieDomain": "{$cookieDomain}", /* Nom de domaine sur lequel sera posé le cookie - pour les multisites / sous-domaines - Facultatif */
    "btnDisabledColor": "{$btnDisabledColor}",
    "btnAllowColor": "{$btnAllowColor}",
    "btnDenyColor": "{$btnDenyColor}",
    "btnAllDisabledColor": "{$btnAllDisabledColor}",
    "btnAllAllowedColor": "{$btnAllAllowedColor}",
    "btnAllDeniedColor": "{$btnAllDeniedColor}"
  });

  tarteaucitron.lang = {
    "adblock": "{l s='Hello! This site is transparent and lets you chose the 3rd party services you want to allow.' mod='k_cookies'}",
    "adblock_call" : "{l s='Please disable your adblocker to start customizing.' mod='k_cookies'}",
    "reload": "{l s='Refresh the page' mod='k_cookies'}",
    "alertBigScroll": "{l s='By continuing to scroll,' mod='k_cookies'}",
    "alertBigClick": "{l s='If you continue to browse this website,' mod='k_cookies'}",
    "alertBig": "{l s='you are allowing all third-party services' mod='k_cookies'}",
    "alertBigPrivacy": "{l s='This site uses cookies and gives you control over what you want to activate' mod='k_cookies'}",
    "alertSmall": "{l s='Manage services' mod='k_cookies'}",
    "personalize": "{l s='Personalize' mod='k_cookies'}",
    "acceptAll": "{l s='OK, accept all' mod='k_cookies'}",
    "close": "{l s='Close' mod='k_cookies'}",
    "all": "{l s='Preference for all services' mod='k_cookies'}",
    "info": "{l s='Protecting your privacy' mod='k_cookies'}",
    "disclaimer": "{l s='By allowing these third party services, you accept their cookies and the use of tracking technologies necessary for their proper functioning.' mod='k_cookies'}",
    "allow": "{l s='Allow' mod='k_cookies'}",
    "deny": "{l s='Deny' mod='k_cookies'}",
    "noCookie": "{l s='This service does not use cookie.' mod='k_cookies'}",
    "useCookie": "{l s='This service can install' mod='k_cookies'}",
    "useCookieCurrent": "{l s='This service has installed' mod='k_cookies'}",
    "useNoCookie": "{l s='This service has not installed any cookie.' mod='k_cookies'}",
    "more": "{l s='Read more' mod='k_cookies'}",
    "source": "{l s='View the official website' mod='k_cookies'}",
    "credit": "{l s='Cookies manager by tarteaucitron.js' mod='k_cookies'}",
    "fallback": "{l s='is disabled.' mod='k_cookies'}",
    "ads": {
      "title": "{l s='Advertising network' mod='k_cookies'}",
      "details": "{l s='Ad networks can generate revenue by selling advertising space on the site.' mod='k_cookies'}"
    },
    "analytic": {
      "title": "{l s='Audience measurement' mod='k_cookies'}",
      "details": "{l s='The audience measurement services used to generate useful statistics attendance to improve the site.' mod='k_cookies'}"
    },
    "social": {
      "title": "{l s='Social networks' mod='k_cookies'}",
      "details": "{l s='Social networks can improve the usability of the site and help to promote it via the shares.' mod='k_cookies'}"
    },
    "video": {
      "title": "{l s='Videos' mod='k_cookies'}",
      "details": "{l s='Video sharing services help to add rich media on the site and increase its visibility.' mod='k_cookies'}"
    },
    "comment": {
      "title": "{l s='Comments' mod='k_cookies'}",
      "details": "{l s='Comments managers facilitate the filing of comments and fight against spam.' mod='k_cookies'}"
    },
    "support": {
      "title": "{l s='Support' mod='k_cookies'}",
      "details": "{l s='Support services allow you to get in touch with the site team and help to improve it.' mod='k_cookies'}"
    },
    "api": {
      "title": "{l s='APIs' mod='k_cookies'}",
      "details": "{l s='APIs are used to load scripts: geolocation, search engines, translations, ...' mod='k_cookies'}"
    },
    "other": {
      "title": "{l s='Other' mod='k_cookies'}",
      "details": "{l s='Services to display web content.' mod='k_cookies'}"
    }
  };
</script>
