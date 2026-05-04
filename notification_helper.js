/**
 * GIITChat Notification Helper
 * Handles Browser Notifications and Sounds
 */

const GIITNotification = {
    audio: null,
    soundUrl: 'https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3', // Loud modern digital alert
    
    init: function() {
        // Pre-load audio
        this.audio = new Audio(this.soundUrl);
        this.audio.volume = 1.0; 



        // Request permission on first interaction or page load
        if ("Notification" in window) {
            if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                console.log("Requesting notification permission...");
            }
        }
    },

    requestPermission: function() {
        if ("Notification" in window) {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    console.log("Notification permission granted.");
                }
            });
        }
    },

    playNotificationSound: function() {
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
            this.audio.play().catch(e => {});
        }
    },

    show: function(title, message, icon = 'images/agt_announcements.png', clickCallback = null) {
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
                notification.onclick = function(event) {
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
    
    // Request permission when user clicks anywhere for the first time (to satisfy browser policies)
    const requestHandler = () => {
        GIITNotification.requestPermission();
        
        // Unlock audio by playing a brief silent sound
        if (GIITNotification.audio) {
            GIITNotification.audio.volume = 0;
            GIITNotification.audio.play().then(() => {
                GIITNotification.audio.pause();
                GIITNotification.audio.volume = 1.0;
            }).catch(e => {});
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



