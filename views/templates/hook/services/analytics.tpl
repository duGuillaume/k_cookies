tarteaucitron.user.analyticsMore = function () {
{if $analyticsCrossDomain}
  ga('create', '{$analyticsUa|escape:'htmlall':'UTF-8'}', 'auto', {literal}{'allowLinker': true}{/literal});
  ga('require', 'linker');
  ga('linker:autoLink', [
  {foreach from=$shops item=shop}
    {if $shop.id_shop != $currentShopId}
      {if $useSecureMode}'{$shop.domain_ssl|escape:'htmlall':'UTF-8'}'{else}'{$shop.domain|escape:'htmlall':'UTF-8'}'{/if},
    {/if}
  {/foreach}
  ]);
{else}
  ga('create', '{$analyticsUa|escape:'htmlall':'UTF-8'}', 'auto');
{/if}
{if $analyticsUserID}
  ga('set', 'userId', '{$analyticsUserID|escape:'htmlall':'UTF-8'}');
{/if}
{if $analyticsAnonymize}
  ga('set', 'anonymizeIp', true);
{/if}
ga('require', 'ec');
};
(tarteaucitron.job = tarteaucitron.job || []).push('analytics');
