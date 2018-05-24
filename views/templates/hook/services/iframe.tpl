var tarteaucitron_interval = setInterval(function() {
  if (typeof tarteaucitron.services.iframe.name == 'undefined') {
    return;
  }
  clearInterval(tarteaucitron_interval);

  tarteaucitron.services.iframe.name = '{$name}';
  tarteaucitron.services.iframe.uri = '{$uri}';
  tarteaucitron.services.iframe.cookies = {$cookies};
}, 10);
(tarteaucitron.job = tarteaucitron.job || []).push('iframe');
