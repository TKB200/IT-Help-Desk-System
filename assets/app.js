const tabLinks = document.querySelectorAll('[data-tab-target]');
const tabPanels = document.querySelectorAll('[data-tab-panel]');

tabLinks.forEach((button) => {
    button.addEventListener('click', () => {
        const target = button.dataset.tabTarget;

        tabLinks.forEach((link) => link.classList.toggle('active', link === button));
        tabPanels.forEach((panel) => panel.classList.toggle('active', panel.dataset.tabPanel === target));
    });
});

const searchInput = document.querySelector('[data-ticket-search]');
const ticketCards = document.querySelectorAll('[data-ticket-card]');

if (searchInput) {
    searchInput.addEventListener('input', (event) => {
        const query = event.target.value.trim().toLowerCase();

        ticketCards.forEach((card) => {
            const haystack = card.dataset.ticketText || '';
            card.style.display = haystack.includes(query) ? '' : 'none';
        });
    });
}

const flash = document.querySelector('.flash');
if (flash) {
    window.setTimeout(() => {
        flash.style.opacity = '0';
        flash.style.transition = 'opacity 0.3s ease';
    }, 3500);
}
