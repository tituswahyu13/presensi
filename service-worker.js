const CACHE_NAME = "cache-v1";
const urlsToCache = [
  "/",
  "/auth/login.php",
  "/admin/home/home.php",  // Pastikan halaman home ada di cache
  "/pegawai/home/home.php", // Pastikan halaman pegawai home ada di cache
  "/css/style.css",        
  "/js/script.js",         
  "/icons/load.png",
  "/icons/icon.png"
];


self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        return response || fetch(event.request);
      })
  );
});

self.addEventListener("activate", (event) => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (!cacheWhitelist.includes(cacheName)) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim()) // Mengaktifkan service worker di seluruh halaman dalam scope
  );
});
