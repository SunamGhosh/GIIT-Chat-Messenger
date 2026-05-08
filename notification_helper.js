/**
 * GIITChat Notification Helper
 * Handles Browser Notifications and Sounds
 */

// Firebase Config Placeholder (User should update this)
const firebaseConfig = {
    apiKey: "AIzaSyAVAkoe3xsLWm_yVaxNr6BR9nl7OHt7WQI",
    authDomain: "sunam-giit.firebaseapp.com",
    projectId: "sunam-giit",
    storageBucket: "sunam-giit.firebasestorage.app",
    messagingSenderId: "417792257596",
    appId: "1:417792257596:web:d29cfb283d1c8ad64e9d39"
};

const GIITNotification = {
    audio: null,
    soundUrl: 'https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3', // Loud modern digital alert

    init: function () {
        // Pre-load audio
        this.audio = new Audio(this.soundUrl);
        this.audio.volume = 1.0;

        // Request permission on first interaction or page load
        if ("Notification" in window) {
            if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                console.log("Requesting notification permission...");
            }
        }

        // Initialize Firebase
        this.initFirebase();
    },

    initFirebase: function () {
        if (typeof firebase === 'undefined') {
            // Dynamically load Firebase scripts if not present
            const scripts = [
                'https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js',
                'https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js'
            ];

            let loaded = 0;
            scripts.forEach(src => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = () => {
                    loaded++;
                    if (loaded === scripts.length) {
                        this.setupMessaging();
                    }
                };
                document.head.appendChild(script);
            });
        } else {
            this.setupMessaging();
        }
    },

    setupMessaging: function () {
        if (!firebase.apps.length) {
            firebase.initializeApp(firebaseConfig);
        }
        const messaging = firebase.messaging();

        // Get Token
        messaging.getToken({ vapidKey: 'BMdh68hpi_UcCvpFpRHKZrFYQf2TeruBzqxzHwr3wGmePmquXDQugJNriTpPIIYKGXAxLH_syVJ_dNyXmPxE3Cw' }).then((currentToken) => {
            if (currentToken) {
                console.log("FCM Token obtained.");
                this.saveTokenToServer(currentToken);
            } else {
                console.log('No registration token available. Request permission to generate one.');
            }
        }).catch((err) => {
            console.log('An error occurred while retrieving token. ', err);
        });

        // Handle foreground messages
        messaging.onMessage((payload) => {
            console.log('Message received. ', payload);
            this.show(payload.notification.title, payload.notification.body);
        });
    },

    saveTokenToServer: function (token) {
        // Only save if it's different from the one we last saved
        const lastToken = localStorage.getItem('giit_fcm_token');
        if (lastToken === token) return;

        const formData = new FormData();
        formData.append('token', token);

        fetch('student_message.php?ajax=save_fcm_token', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(data => {
            if (data.success) {
                localStorage.setItem('giit_fcm_token', token);
            }
        });
    },

    requestPermission: function () {
        if ("Notification" in window) {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    console.log("Notification permission granted.");
                }
            });
        }
    },

    playNotificationSound: function () {
        if (this.audio) {
            // Reset to beginning in case it was already played
            this.audio.pause();
            this.audio.currentTime = 0;

            this.audio.play().catch(e => {
                console.warn("Notification sound blocked: User must interact with the page first.", e);
            });
        } else {
            // Re-initialize if for some reason audio is null
            this.audio = new Audio(this.soundUrl);
            this.audio.play().catch(e => { });
        }
    },

    show: function (title, message, icon = 'images/agt_announcements.png', clickCallback = null) {
        // 1. Play Sound
        this.playNotificationSound();

        // 2. Show Native Notification if permission granted
        if ("Notification" in window && Notification.permission === "granted") {
            const notification = new Notification(title, {
                body: message,
                icon: icon,
                badge: icon,
                silent: true // We play our own sound
            });

            if (clickCallback && typeof clickCallback === 'function') {
                notification.onclick = function (event) {
                    event.preventDefault();
                    window.focus();
                    clickCallback();
                    notification.close();
                };
            }
        }
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    GIITNotification.init();

    // FCM Support: Register Service Worker and Request Token
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('firebase-messaging-sw.js')
            .then(function (registration) {
                console.log('Registration successful, scope is:', registration.scope);
            }).catch(function (err) {
                console.log('Service worker registration failed, error:', err);
            });
    }

    // Request permission when user clicks anywhere for the first time (to satisfy browser policies)
    const requestHandler = () => {
        GIITNotification.requestPermission();

        // Unlock audio by playing a brief silent sound
        if (GIITNotification.audio) {
            GIITNotification.audio.volume = 0;
            GIITNotification.audio.play().then(() => {
                GIITNotification.audio.pause();
                GIITNotification.audio.volume = 1.0;
            }).catch(e => { });
        }

        document.removeEventListener('click', requestHandler);
    };
    document.addEventListener('click', requestHandler);
});

/**
 * Utility to update badges in the UI
 * @param {string} selector - CSS selector for the badge element
 * @param {number} count - The number to display (0 hides it)
 */
/**
 * Utility to update badges in the UI
 * @param {string} selector - CSS selector for the badge element
 * @param {any} count - The number to display (0 hides it)
 * @param {boolean} playSound - Whether to play notification sound
 */
function updateUINotificationBadge(selector, count, playSound = false) {
    const badges = document.querySelectorAll(selector);
    badges.forEach(badge => {
        if (count !== 0 && count !== '0' && count !== null) {
            badge.innerText = count;
            badge.style.display = 'inline-block';
            badge.classList.add('blinking');

            if (playSound) {
                GIITNotification.playNotificationSound();
            }
        } else {
            badge.style.display = 'none';
            badge.classList.remove('blinking');
        }
    });
}

// Add animations and badge style
const style = document.createElement('style');
style.innerHTML = `
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.3); }
    }
    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.4; }
        100% { opacity: 1; }
    }
    .giit-badge {
        background: #ff4757 !important;
        color: white !important;
        font-size: 11px !important;
        font-weight: bold !important;
        padding: 2px 7px !important;
        border-radius: 10px !important;
        position: absolute !important;
        top: -8px !important;
        right: -8px !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important;
        display: none;
        z-index: 9999 !important;
        border: 1.5px solid white !important;
    }
    .giit-badge.blinking {
        animation: blink 0.8s infinite ease-in-out !important;
    }
`;
document.head.appendChild(style);



