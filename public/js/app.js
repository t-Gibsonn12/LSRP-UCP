document.querySelectorAll('.flash').forEach((item) => {
    setTimeout(() => item.classList.add('fade'), 4500);
});

const accountMenu = document.querySelector('[data-account-menu]');
const accountTrigger = document.querySelector('[data-account-trigger]');
const notificationMenu = document.querySelector('[data-notification-menu]');
const notificationTrigger = document.querySelector('[data-notification-trigger]');

function closeAccountMenu() {
    if (!accountMenu || !accountTrigger) return;
    accountMenu.classList.remove('open');
    accountTrigger.setAttribute('aria-expanded', 'false');
}

function closeNotificationMenu() {
    if (!notificationMenu || !notificationTrigger) return;
    notificationMenu.classList.remove('open');
    notificationTrigger.setAttribute('aria-expanded', 'false');
}

if (accountMenu && accountTrigger) {
    accountTrigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = !accountMenu.classList.contains('open');
        closeNotificationMenu();
        accountMenu.classList.toggle('open', willOpen);
        accountTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
}

if (notificationMenu && notificationTrigger) {
    notificationTrigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = !notificationMenu.classList.contains('open');
        closeAccountMenu();
        notificationMenu.classList.toggle('open', willOpen);
        notificationTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
}

document.addEventListener('click', (event) => {
    if (accountMenu && !accountMenu.contains(event.target)) closeAccountMenu();
    if (notificationMenu && !notificationMenu.contains(event.target)) closeNotificationMenu();
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeAccountMenu();
        closeNotificationMenu();
    }
});

const mobileToggle = document.querySelector('[data-mobile-nav]');
const mainNav = document.querySelector('[data-main-nav]');
if (mobileToggle && mainNav) {
    mobileToggle.addEventListener('click', () => {
        const open = mainNav.classList.toggle('mobile-open');
        mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}


const skinSearch = document.querySelector('[data-skin-search]');
const skinOptions = Array.from(document.querySelectorAll('[data-skin-option]'));
const skinCount = document.querySelector('[data-skin-count]');
const skinEmpty = document.querySelector('[data-skin-empty]');

if (skinSearch && skinOptions.length) {
    const updateSkinFilter = () => {
        const query = skinSearch.value.trim().toLocaleLowerCase();
        let visibleCount = 0;

        skinOptions.forEach((option) => {
            const text = (option.dataset.skinSearchText || '').toLocaleLowerCase();
            const visible = query === '' || text.includes(query);
            option.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        if (skinCount) {
            skinCount.textContent = query
                ? `${visibleCount} / ${skinOptions.length} SKIN`
                : `${skinOptions.length} SKIN`;
        }
        if (skinEmpty) skinEmpty.hidden = visibleCount !== 0;
    };

    skinSearch.addEventListener('input', updateSkinFilter);
}
