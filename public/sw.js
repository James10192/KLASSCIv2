const CACHE_NAME = "esbtp-attendance-v1";
const OFFLINE_URL = "/offline.html";

const urlsToCache = [
    "/",
    "/offline.html",
    "/css/app.css",
    "/js/app.js",
    "/js/attendance.js",
    "/images/logo.png",
];

// Install Service Worker
self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log("Opened cache");
            return cache.addAll(urlsToCache);
        })
    );
});

// Fetch Event
self.addEventListener("fetch", (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            // Cache hit - return response
            if (response) {
                return response;
            }

            return fetch(event.request)
                .then((response) => {
                    // Check if we received a valid response
                    if (
                        !response ||
                        response.status !== 200 ||
                        response.type !== "basic"
                    ) {
                        return response;
                    }

                    // Clone the response
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });

                    return response;
                })
                .catch(() => {
                    // If the network request fails, return the offline page
                    if (event.request.mode === "navigate") {
                        return caches.match(OFFLINE_URL);
                    }
                });
        })
    );
});

// Activate Event
self.addEventListener("activate", (event) => {
    const cacheWhitelist = [CACHE_NAME];

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Handle Sync Event
self.addEventListener("sync", (event) => {
    if (event.tag === "sync-attendance") {
        event.waitUntil(syncAttendance());
    }
});

// Function to sync attendance data
async function syncAttendance() {
    try {
        const db = await openIndexedDB();
        const pendingAttendance = await db.getAll("pendingAttendance");

        for (const attendance of pendingAttendance) {
            try {
                const response = await fetch("/api/attendance/sync", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(attendance),
                });

                if (response.ok) {
                    await db.delete("pendingAttendance", attendance.id);
                }
            } catch (error) {
                console.error("Error syncing attendance:", error);
            }
        }
    } catch (error) {
        console.error("Error in syncAttendance:", error);
    }
}

// IndexedDB helper functions
function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open("ESBTPAttendanceDB", 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains("pendingAttendance")) {
                db.createObjectStore("pendingAttendance", {
                    keyPath: "id",
                    autoIncrement: true,
                });
            }
        };
    });
}
