document.addEventListener("DOMContentLoaded", function() {
    function adjustNav() {
        var screenWidth = window.innerWidth;
        var topNav = document.querySelector('.top-navbar');
        var sidebarNav = document.querySelector('#sidebar');

        // Adjust these widths based on your design's breakpoint
        if (screenWidth >= 768) {
            // Window is wide enough for sidebar to be shown
            if (topNav) topNav.classList.add('d-none');
            if (sidebarNav) sidebarNav.classList.remove('d-none');
        } else {
            // Window is too narrow, show top nav and hide sidebar
            if (topNav) topNav.classList.remove('d-none');
            if (sidebarNav) sidebarNav.classList.add('d-none');
        }
    }

    // Run on load
    adjustNav();

    // Adjust on window resize
    window.addEventListener('resize', adjustNav);

    function logout() {
        window.location.href = 'logout.php';
    }
    var logoutBtn = document.getElementById('logoutBtn');
    if(logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
});

function showNotification(message, duration = 3000) {
    const container = document.getElementById('notification-container');
    const notification = document.createElement('div');
    notification.classList.add('notification');
    notification.textContent = message;
    function speakText(text) {
        if ('speechSynthesis' in window) {
            var utterance = new SpeechSynthesisUtterance(text);
            window.speechSynthesis.speak(utterance);
        } else {
            console.error('Speech synthesis not supported.');
        }
    }

    // Call the speakText function to read the notification message aloud
    speakText(message);

    // Initially hidden
    requestAnimationFrame(() => {
        notification.style.opacity = 1;
        notification.style.transform = 'translateY(0)';
    });

    container.appendChild(notification);

    // Hide and remove after 'duration' milliseconds
    setTimeout(() => {
        notification.style.opacity = 0;
        notification.style.transform = 'translateY(-20px)';
        notification.addEventListener('transitionend', () => notification.remove());
    }, duration);
}
