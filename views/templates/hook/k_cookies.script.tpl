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
    "cookieDomain": "{$cookieDomain}", /* Nom de domaine sur lequel sera posé le cookie - pour les multisites / sous-domaines - Facultatif */
    "btnDisabledColor": "{$btnDisabledColor}",
    "btnAllowColor": "{$btnAllowColor}",
    "btnDenyColor": "{$btnDenyColor}",
    "btnAllDisabledColor": "{$btnAllDisabledColor}",
    "btnAllAllowedColor": "{$btnAllAllowedColor}",
    "btnAllDeniedColor": "{$btnAllDeniedColor}"
  });
</script>
