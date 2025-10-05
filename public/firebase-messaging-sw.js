importScripts(
    "https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"
);
importScripts(
    "https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js"
);

firebase.initializeApp({
    apiKey: "AIzaSyBeGc52irf9PIRUP62plxCqNmss1Bpe2ew",
    authDomain: "ninamotor-53934.firebaseapp.com",
    projectId: "ninamotor-53934",
    storageBucket: "ninamotor-53934.firebasestorage.app",
    messagingSenderId: "453165515440",
    appId: "1:453165515440:web:d5539cb05061c34feb7175",
    measurementId: "G-2H6951H8J5",
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
    console.log(
        "[firebase-messaging-sw.js] Received background message ",
        payload
    );
    self.registration.showNotification(payload.notification.title, {
        body: payload.notification.body,
        icon: "/icon.png",
    });
});
