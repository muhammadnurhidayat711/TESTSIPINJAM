console.log("🔥 firebase.js LOADED");

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

// Init Firebase
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

/* ===============================
   INIT FCM (GLOBAL FUNCTION)
================================ */

async function initFCM(userId) {
    try {
        // 1. Request permission
        const permission = await Notification.requestPermission();
        console.log("🔔 Notification permission:", permission);
        
        if (permission !== "granted") {
            console.warn("❌ Notification permission denied");
            return;
        } // ✅ PERBAIKAN: Kurung kurawal penutup ditambahkan
        
        // 2. Register Service Worker
        const registration = await navigator.serviceWorker.register(
            "testsipinjam/firebase-messaging-sw.js"
        );
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
        
        // 4. Send token to backend
        await fetch("testsipinjam/save_token.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                user_id: userId,
                token: token
            })
        });
        
        console.log("📡 Token sent to server");
        
    } catch (error) {
        console.error("🔥 initFCM ERROR:", error);
    }
}

/* ===============================
   FOREGROUND MESSAGE
================================ */

messaging.onMessage((payload) => {
    console.log("📩 FCM Message received:", payload);
    
    if (Notification.permission === "granted") {
        new Notification(payload.notification.title, {
            body: payload.notification.body,
            icon: payload.notification.icon || "/icon.png"
        });
    }
});
