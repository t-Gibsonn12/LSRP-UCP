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


const applicationForm = document.querySelector('.application-form');
const applicationValidation = document.querySelector('[data-application-validation]');

if (applicationForm && applicationValidation) {
    const applicationFieldLabels = {
        slot: 'Slot nhân vật',
        character_name: 'Tên nhân vật',
        gender: 'Giới tính',
        birth_date: 'Ngày sinh',
        birth_place: 'Nơi sinh',
        nationality: 'Quốc tịch',
        skin_tone: 'Màu da',
        occupation: 'Nghề nghiệp',
        height_cm: 'Chiều cao',
        weight_kg: 'Cân nặng',
        skin: 'Skin nhân vật',
        personality: 'Tính cách',
        strengths: 'Điểm mạnh',
        weaknesses: 'Điểm yếu',
        concept: 'Khái quát nhân vật',
        background: 'Tiểu sử nhân vật',
        roleplay_goal: 'Mục tiêu Roleplay',
        rules_agreed: 'Xác nhận hồ sơ'
    };

    const formControls = () => Array.from(applicationForm.elements);
    const clearInvalidState = (element) => {
        const field = element.closest('label');
        if (field) field.classList.remove('client-invalid');
    };
    const clearInvalidGroup = (element) => {
        if (!element || !element.name) return;
        formControls()
            .filter((control) => control.name === element.name)
            .forEach(clearInvalidState);
        applicationValidation.hidden = true;
    };
    const fieldLabel = (element) => applicationFieldLabels[element.name]
        || element.closest('label')?.querySelector('span')?.textContent?.trim()
        || element.name
        || 'Thông tin';

    applicationForm.addEventListener('input', (event) => clearInvalidGroup(event.target));
    applicationForm.addEventListener('change', (event) => clearInvalidGroup(event.target));

    applicationForm.addEventListener('submit', (event) => {
        const invalidControls = formControls().filter((control) => {
            if (control.disabled || control.type === 'hidden') return false;
            return typeof control.checkValidity === 'function' && !control.checkValidity();
        });

        if (!invalidControls.length) return;

        event.preventDefault();
        formControls().forEach(clearInvalidState);
        invalidControls.forEach((control) => {
            const field = control.closest('label');
            if (field) field.classList.add('client-invalid');
        });

        const missingFields = [...new Set(invalidControls.map(fieldLabel))];
        applicationValidation.replaceChildren();

        const title = document.createElement('strong');
        title.textContent = 'Hồ sơ chưa đủ thông tin.';
        const lead = document.createElement('span');
        lead.textContent = 'Vui lòng bổ sung các mục sau rồi gửi lại:';
        const list = document.createElement('ul');
        missingFields.forEach((name) => {
            const item = document.createElement('li');
            item.textContent = name;
            list.appendChild(item);
        });
        applicationValidation.append(title, lead, list);
        applicationValidation.hidden = false;

        const firstInvalid = invalidControls[0];
        firstInvalid.focus({preventScroll: true});
        firstInvalid.closest('section')?.scrollIntoView({behavior: 'smooth', block: 'center'});
    });
}
