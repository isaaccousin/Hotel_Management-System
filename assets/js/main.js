// main.js - Global JS
document.addEventListener('DOMContentLoaded', function () {
    console.log("Hotel Management System loaded.");
    
    // Example: highlight active nav link
    const navLinks = document.querySelectorAll('.navbar a');
    const path = window.location.pathname;

    navLinks.forEach(link => {
        if (link.href.includes(path)) {
            link.classList.add('active');
        }
    });
});