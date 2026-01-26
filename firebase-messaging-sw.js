importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "AIzaSyBObUwqTbu0cwwvZ6IL6Mny-9UUKRmzlZI",
  authDomain: "sipinjam-934c5.firebaseapp.com",
  projectId: "sipinjam-934c5",
  storageBucket: "sipinjam-934c5.appspot.com",
  messagingSenderId: "201444095041",
  appId: "1:201444095041:web:f9567a9630dadbda6f82c0",
  measurementId: "G-JH44M4SKSY"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
  self.registration.showNotification(payload.notification.title, {
    body: payload.notification.body,
    icon: '/testsipinjam/assets/icon.png'
  });
});
