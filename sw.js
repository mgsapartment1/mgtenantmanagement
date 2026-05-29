self.addEventListener('install', (e) => {
  console.log('Service Worker: Installed');
});

self.addEventListener('fetch', (e) => {
  // Dito pwedeng i-cache ang files para gumana kahit offline
  e.respondWith(fetch(e.request));
});