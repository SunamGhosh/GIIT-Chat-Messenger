// Import and configure the Firebase SDK
importScripts('https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js');

// Initialize the Firebase app in the service worker
firebase.initializeApp({
  apiKey: "AIzaSyAVAkoe3xsLWm_yVaxNr6BR9nl7OHt7WQI",
  authDomain: "sunam-giit.firebaseapp.com",
  projectId: "sunam-giit",
  storageBucket: "sunam-giit.firebasestorage.app",
  messagingSenderId: "417792257596",
  appId: "1:417792257596:web:d29cfb283d1c8ad64e9d39"
});

const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Received background message ', payload);
  
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/images/message.png',
    data: payload.data // Pass data to use in click handler if needed
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('student_message.php')
  );
});
