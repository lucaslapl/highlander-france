document.addEventListener("DOMContentLoaded", () => {
    // 1. Si une position de scroll a été sauvegardée, on y replace l'utilisateur
    const savedScrollTop = localStorage.getItem("profile_scroll_pos");
    if (savedScrollTop !== null) {
        window.scrollTo(0, parseInt(savedScrollTop, 10));
        localStorage.removeItem("profile_scroll_pos"); // On nettoie après coup
    }

    // 2. Au clic sur un onglet, on sauvegarde la position actuelle du scroll
    const tabButtons = document.querySelectorAll(".js-profile-tab");
    tabButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            localStorage.setItem("profile_scroll_pos", window.scrollY);
        });
    });
});