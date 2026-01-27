/**
 * Sadaa Surah Player App
 */

// State
const state = {
    currentSurah: 1,
    currentCategory: null,
    currentLanguage: getCookie('sadaa_lang') || 'fr',
    currentBook: 'quran',
    categories: [],
    types: [],
    surahData: null
};

// DOM Elements
const els = {
    surahTitle: document.getElementById('surah-title'),
    verseRef: document.getElementById('verse-ref'),
    verseText: document.getElementById('verse-text'),
    translationText: document.getElementById('translation-text'),
    sourceAttribution: document.getElementById('source-attribution'),
    btnPrev: document.getElementById('btn-prev'),
    btnNext: document.getElementById('btn-next'),
    btnCopy: document.getElementById('btn-copy'),
    pickerTrigger: document.getElementById('picker-trigger'),
    pickerModal: document.getElementById('picker-modal'),
    modalBackdrop: document.querySelector('.picker-modal-backdrop'),
    modalConfirm: document.getElementById('modal-confirm'),
    modalPickerTrack: document.getElementById('modal-picker-track'),
    modalArrowUp: document.getElementById('modal-arrow-up'),
    modalArrowDown: document.getElementById('modal-arrow-down'),
    modalDescription: document.getElementById('modal-description'),
    typeTabs: document.querySelectorAll('.tab'),
    langSelect: document.getElementById('lang-select'),
    bookSelect: document.getElementById('book-select'),
    themeToggle: document.getElementById('theme-toggle')
};

// Init
document.addEventListener('DOMContentLoaded', async () => {
    // Parse URL params
    const params = new URLSearchParams(window.location.search);
    if (params.has('surah')) state.currentSurah = parseInt(params.get('surah'));
    if (params.has('category')) state.currentCategory = parseInt(params.get('category'));

    // Set initial selects
    if (els.langSelect) els.langSelect.value = state.currentLanguage;

    // Load Data
    await loadInitialData();
    await loadSurah(state.currentSurah);

    // Event Listeners
    setupEventListeners();
});

// --- Data Loading ---

async function loadInitialData() {
    try {
        // Fetch Categories & Types
        const [catsRes, typesRes] = await Promise.all([
            fetch('api.php?endpoint=categories').then(r => r.json()),
            fetch('api.php?endpoint=types').then(r => r.json())
        ]);

        if (catsRes.status === 'success') state.categories = catsRes.data;
        if (typesRes.status === 'success') state.types = typesRes.data;

        // Populate Picker if category is selected
        if (state.currentCategory) {
            updatePickerUI();
        }

    } catch (e) {
        console.error('Failed to load initial data', e);
    }
}

async function loadSurah(number) {
    els.surahTitle.textContent = 'Chargement...';
    els.verseText.innerHTML = '';
    els.translationText.innerHTML = '';

    try {
        const url = `api.php?endpoint=surah&surah=${number}&category=${state.currentCategory || ''}`;
        const res = await fetch(url).then(r => r.json());

        if (res.status === 'success') {
            state.surahData = res.data;
            renderSurah();
            updateNavigation();
        } else {
            els.surahTitle.textContent = 'Erreur loading surah';
        }
    } catch (e) {
        console.error('Error loading surah', e);
        els.surahTitle.textContent = 'Erreur de connexion';
    }
}

// --- Rendering ---

// --- Rendering ---

function renderSurah() {
    if (!state.surahData) return;

    const { surah, ayahs, next_surah, prev_surah } = state.surahData;
    const lang = state.currentLanguage;

    // Title & Info
    const name = JSON.parse(surah.name);
    // If 'ar', show Arabic name as main. If others, show localized name.
    els.surahTitle.textContent = lang === 'ar' ? name['ar'] : (name[lang] || name['en']);
    els.verseRef.textContent = `AYAT ${surah.ayah_count} • ${surah.revelation_type}`;
    els.sourceAttribution.textContent = `Surah ${surah.number} - ${lang === 'ar' ? 'القرآن الكريم' : 'Sadaa Edition'}`;

    // Content Calculation
    let contentHtml = '';

    // Group all ayahs into one block of text? Or verse by verse?
    // User demand: "Show translation at top and bottom... show text in arabic at bottom in smaller font."
    // Interpretation: The user likely means "For the whole Surah (or current view), show the Translation Block, then the Arabic Block below it". 
    // OR "For each verse, show Translation, then Arabic".
    // Given the HTML structure has `verse-text` and `translation-text` separate divs in the container, I should assume they are two separate blocks in the flow.
    // BUT the user said: "when arabic is chosen, do not show translation".
    // "for other languages, show translation at top... and show arabic text at bottom in smaller font".

    // So for 'ar':
    // verseText (the div) -> Contains Arabic. Large font.
    // translationText (the div) -> Empty / Hidden.

    // For 'fr'/'en':
    // verseText (the div) -> Contains Arabic. Smaller font. MOVED TO BOTTOM via CSS order or just inserting it second?
    // The HTML has `verse-text` THEN `translation-text`. 
    // I should probably swap content or use CSS flex order if container is flex col.
    // The CSS `.scroll-content` is flex-col. 
    // `verse-text` is first child. `translation-text` is second.

    // Approach:
    // I will populate the DIVs based on the logic.
    // - div#translation-text: used for the PRIMARY content (Translation in FR, Empty in AR).
    // - div#verse-text: used for the SECONDARY content (Arabic in FR, Arabic in AR).

    // Wait, if AR:
    // Primary is Arabic. 
    // Let's use specific class manipulation for styling.

    let primaryHtml = ''; // Will go into a 'primary' slot
    let secondaryHtml = ''; // Will go into a 'secondary' slot

    // Prepare HTML strings
    let fullArabic = '';
    let fullTranslation = '';

    ayahs.forEach(ayah => {
        const text = JSON.parse(ayah.text);
        // Arabic always has separators
        fullArabic += `${text.ar} <span class="arabic-separator">۝</span> `;
        // Translation has numbers
        const tText = text[lang] || text['en'] || '';
        fullTranslation += `${tText} <span class="ayah-number">${ayah.ayah_number}</span> `;
    });

    if (lang === 'ar') {
        // Mode Arabic Only:
        // We want the 'verse-text' div to show Arabic big. 
        // We want 'translation-text' to be empty.
        els.verseText.innerHTML = fullArabic;
        els.verseText.className = 'text-primary-display is-arabic fade-in';
        els.verseText.style.display = 'block';

        els.translationText.innerHTML = '';
        els.translationText.style.display = 'none';

    } else {
        // Mode Translated:
        // User wants Translation at TOP. Arabic at BOTTOM (small).
        // HTML structure: #verse-text (1st), #translation-text (2nd).
        // I need to swap them visually or logically.
        // Easiest is to put Translation into #verse-text (the top one) and styling it as Primary.
        // And put Arabic into #translation-text (the bottom one) and styling it as Secondary.
        // Wait, ID names are confusing then.
        // Better: flex order? 
        // Let's rely on standard ID usage but change `order` style.

        // #verse-text: Arabic. Class: text-secondary-display. Order: 2.
        // #translation-text: Translation. Class: text-primary-display. Order: 1.

        els.translationText.innerHTML = fullTranslation;
        els.translationText.className = 'text-primary-display fade-in';
        els.translationText.style.display = 'block';
        els.translationText.style.order = '1';

        els.verseText.innerHTML = fullArabic;
        els.verseText.className = 'text-secondary-display fade-in';
        els.verseText.style.order = '2';
        els.verseText.style.marginTop = '2rem'; // Spacing
    }

    // Update Navigation Links
    state.nextSurahId = next_surah ? next_surah.number : null;
    state.prevSurahId = prev_surah ? prev_surah.number : null;
    updateNavigation();
}

function updateNavigation() {
    els.btnPrev.disabled = !state.prevSurahId;
    els.btnNext.disabled = !state.nextSurahId;
}

function updatePickerUI() {
    // Determine active type/category and render picker track
    // For now, simpler implementation: just populate track with current category's type
    // This part requires mirroring the index.php logic or reusing it.
    // Given the complexity, we'll implement basic picker rendering here.

    // Find category object
    const cat = state.categories.find(c => c.id == state.currentCategory);
    if (cat) {
        document.getElementById('current-category-name').textContent = getLocalized(cat.name);
        document.getElementById('current-category-icon').innerHTML = `<iconify-icon icon="${cat.icon}"></iconify-icon>`;
    }
}

// --- Interactions ---

function setupEventListeners() {
    // Navigation
    els.btnPrev.addEventListener('click', () => {
        if (state.prevSurahId) {
            state.currentSurah = state.prevSurahId;
            updateUrl();
            loadSurah(state.currentSurah);
        }
    });

    els.btnNext.addEventListener('click', () => {
        if (state.nextSurahId) {
            state.currentSurah = state.nextSurahId;
            updateUrl();
            loadSurah(state.currentSurah);
        }
    });

    // Copy
    els.btnCopy.addEventListener('click', async () => {
        const text = `${els.verseText.innerText}\n\n${els.translationText.innerText}`;
        try {
            await navigator.clipboard.writeText(text);
            const icon = els.btnCopy.querySelector('iconify-icon');
            const originalIcon = icon.getAttribute('icon');
            icon.setAttribute('icon', 'mdi:check');
            setTimeout(() => icon.setAttribute('icon', originalIcon), 2000);
        } catch (e) { console.error(e); }
    });

    // Picker Modal
    if (els.pickerTrigger) {
        els.pickerTrigger.addEventListener('click', openPicker);
    }
    if (els.modalBackdrop) {
        els.modalBackdrop.addEventListener('click', closePicker);
    }
    if (els.modalConfirm) {
        els.modalConfirm.addEventListener('click', () => {
            closePicker();
            loadSurah(state.currentSurah); // Reload with new category if changed
        });
    }

    // Language
    if (els.langSelect) {
        els.langSelect.addEventListener('change', (e) => {
            setCookie('sadaa_lang', e.target.value, 365);
            state.currentLanguage = e.target.value;
            window.location.reload(); // Reload to refresh generic texts too
        });
    }

    // Theme
    if (els.themeToggle) {
        els.themeToggle.addEventListener('click', toggleTheme);
    }
}

// --- Helpers ---

function openPicker() {
    els.pickerModal.classList.remove('hidden');
    // Force reflow
    void els.pickerModal.offsetWidth;
    els.pickerModal.classList.add('visible');
    // Initialize picker logic here if needed (e.g. render categories)
    renderPickerItems();
}

function closePicker() {
    els.pickerModal.classList.remove('visible');
    setTimeout(() => els.pickerModal.classList.add('hidden'), 300);
}

function updateUrl() {
    const url = new URL(window.location);
    url.searchParams.set('surah', state.currentSurah);
    if (state.currentCategory) url.searchParams.set('category', state.currentCategory);
    window.history.pushState({}, '', url);
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/`;
}

function toggleTheme() {
    const html = document.documentElement;
    const current = html.classList.contains('dark') ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';

    html.classList.remove(current);
    html.classList.add(next);
    setCookie('sadaa_theme', next, 365);
}

function getLocalized(jsonStr) {
    try {
        const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
        return obj[state.currentLanguage] || obj['en'] || obj['ar'];
    } catch { return ''; }
}

// Picker Render Logic (Simplified)
function renderPickerItems() {
    const container = els.modalPickerTrack;
    container.innerHTML = '';

    // Default to show all categories or current type's categories
    // Only showing a subset for demo or full list
    state.categories.forEach((cat, index) => {
        const item = document.createElement('div');
        item.className = `picker-item ${cat.id == state.currentCategory ? 'active' : ''}`;
        item.innerHTML = `<iconify-icon icon="${cat.icon}"></iconify-icon> ${getLocalized(cat.name)}`;
        item.onclick = () => {
            state.currentCategory = cat.id;
            document.querySelectorAll('.picker-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            updatePickerUI();
        };
        container.appendChild(item);
    });
}
