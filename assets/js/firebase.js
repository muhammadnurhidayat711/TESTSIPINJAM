console.log("🔥 firebase.js LOADED");

// ✅ GUARD: Cegah multiple initialization
if (window.fcmInitialized) {
    console.warn('⚠️ FCM already initialized, skipping...');
}

/* ===============================
   FIREBASE CONFIG
================================ */

const firebaseConfig = {
    apiKey: "AIzaSyBObUwqTbu0cwwvZ6IL6Mny-9UUKRmzlZI",
    authDomain: "sipinjam-934c5.firebaseapp.com",
    projectId: "sipinjam-934c5",
    storageBucket: "sipinjam-934c5.firebasestorage.app",
    messagingSenderId: "201444095041",
    appId: "1:201444095041:web:f9567a9630dadbda6f82c0",
    measurementId: "G-JH44M4SKSY"
};

// Init Firebase (hanya jika belum diinit)
if (!firebase.apps.length) {
    firebase.initializeApp(firebaseConfig);
}
const messaging = firebase.messaging();

/* ===============================
   HELPER: Deteksi Base Path
================================ */

function getBasePath() {
    const path = window.location.pathname;
    // Jika di folder admin
    if (path.includes('/admin/')) {
        return '../';
    }
    // ✅ Jika di folder user
    if (path.includes('/user/')) {
        return '../';
    }
    // Jika di root
    return '';
}

/* ===============================
   INIT FCM (GLOBAL FUNCTION)
================================ */

async function initFCM(userId) {
    // ✅ GUARD: Cegah double call
    if (window.fcmInitialized) {
        console.warn('⚠️ initFCM already called, skipping...');
        return Promise.resolve();
    }
    
    window.fcmInitialized = true; // ✅ Set flag
    
    try {
        const basePath = getBasePath();
        
        // 1. Request permission
        const permission = await Notification.requestPermission();
        console.log("🔔 Notification permission:", permission);
        
        if (permission !== "granted") {
            console.warn("❌ Notification permission denied");
            return;
        }
        
        // 2. Register Service Worker (dengan path dinamis)
        const swPath = basePath + "firebase-messaging-sw.js";
        console.log("📍 SW Path:", swPath);
        
        const registration = await navigator.serviceWorker.register(swPath);
        console.log("✅ SW registered:", registration.scope);
        
        // 3. Get FCM Token
        const token = await messaging.getToken({
            vapidKey: "BBOUB1exV2Z7RP4M8IHaA2dYWQC6AnyS1lhr7hBUTNT-Ahm6S8CrFb16dVYVbQ02npiJ4eKb5bh7UsCnx6ZO1uo",
            serviceWorkerRegistration: registration
        });
        
        if (!token) {
            console.warn("❌ Token not generated");
            return;
        }
        
        console.log("✅ FCM TOKEN:", token);
        
        // 4. Send token to backend (dengan path dinamis)
        const saveTokenUrl = basePath + "save_token.php";
        console.log("📍 Save Token URL:", saveTokenUrl);
        
        const response = await fetch(saveTokenUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                user_id: userId,
                token: token
            })
        });
        
        if (response.ok) {
            console.log("📡 Token sent to server successfully");
        } else {
            console.error("❌ Failed to save token:", response.status);
        }
        
        // ✅ 5. SETUP FOREGROUND MESSAGE HANDLER
        setupForegroundHandler(basePath);
        
    } catch (error) {
        window.fcmInitialized = false; // ✅ Reset flag jika error
        console.error("🔥 initFCM ERROR:", error);
        console.error("Error details:", error.message);
    }
}

/* ===============================
   FOREGROUND MESSAGE HANDLER
================================ */

function setupForegroundHandler(basePath) {
    messaging.onMessage((payload) => {
        console.log("📩 FCM Message received (foreground):", payload);
        
        // Ambil data notifikasi
        const title = payload.notification?.title || 'Notifikasi Baru';
        const body = payload.notification?.body || 'Ada peminjaman baru';
        const icon = payload.notification?.icon || basePath + 'assets/img/icon.ico';
        
        console.log("🔔 Showing notification:", { title, body, icon });
        
        // Tampilkan notifikasi browser
        if (Notification.permission === "granted") {
            const notificationOptions = {
                body: body,
                icon: icon,
                badge: basePath + 'assets/img/icon.ico',
                tag: 'sipinjam-notification',
                requireInteraction: true,
                vibrate: [200, 100, 200],
                data: {
                    url: payload.data?.url || window.location.href
                }
            };
            
            const notification = new Notification(title, notificationOptions);
            
            // Handle klik notifikasi
            notification.onclick = function(event) {
                event.preventDefault();
                window.focus();
                notification.close();
                
                // Redirect jika ada URL
                if (payload.data?.url) {
                    window.location.href = payload.data.url;
                }
            };
            
            console.log("✅ Notification displayed");
        } else {
            console.warn("⚠️ Notification permission not granted");
        }
    });
    
    console.log("✅ Foreground message handler setup complete");
}
