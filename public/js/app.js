/**
 * Sadaa Surah Player App
 */

// Get translations from PHP (passed via window.translations)
const t = window.translations || {};

// Helper function to get translation
function __(key) {
    return t[key] || key;
}

// Convert Western numerals to Arabic-Indic numerals
function toArabicNumerals(num) {
    const arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    return String(num).split('').map(d => arabicNumerals[parseInt(d)] || d).join('');
}

// State
const state = {
    currentSurah: 1,
    currentCategory: null,
    currentLanguage: window.currentLang || getCookie('sadaa_lang') || 'fr',
    currentBook: 'quran',
    categories: [],
    types: [],
    surahData: null,
    surahGroups: [],
    currentGroupIndex: 0,
    currentPage: 0,
    totalPages: 0,
    pages: []
};

// DOM Elements
const els = {
    surahTitle: document.getElementById('surah-title'),
    verseRef: document.getElementById('verse-ref'),
    verseText: document.getElementById('verse-text'),
    translationText: document.getElementById('translation-text'),
    translationInner: document.getElementById('translation-inner'),
    translationWrapper: document.getElementById('translation-wrapper'),
    textScrollArrows: document.getElementById('text-scroll-arrows'),
    textScrollUp: document.getElementById('text-scroll-up'),
    textScrollDown: document.getElementById('text-scroll-down'),
    arabicWrapper: document.getElementById('arabic-wrapper'),
    arabicTextArea: document.getElementById('arabic-text-area'),
    arabicScrollArrows: document.getElementById('arabic-scroll-arrows'),
    arabicScrollUp: document.getElementById('arabic-scroll-up'),
    arabicScrollDown: document.getElementById('arabic-scroll-down'),
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
    groupTags: document.getElementById('group-tags'),
    themeToggle: document.getElementById('theme-toggle')
};

// Init
document.addEventListener('DOMContentLoaded', async () => {
    // Parse URL params
    const params = new URLSearchParams(window.location.search);
    const hasSurahParam = params.has('surah');
    if (hasSurahParam) state.currentSurah = parseInt(params.get('surah'));
    if (params.has('category')) state.currentCategory = parseInt(params.get('category'));

    // Set initial selects
    if (els.langSelect) els.langSelect.value = state.currentLanguage;

    // Load Data
    await loadInitialData();

    // If we have a category but no explicit surah, load first surah for that category
    if (state.currentCategory && !hasSurahParam) {
        await loadFirstSurahForCategory(state.currentCategory);
    } else {
        await loadSurah(state.currentSurah);
    }

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

async function loadSurah(number, startAtEnd = false) {
    els.surahTitle.textContent = __('loading');
    els.verseText.innerHTML = '';
    els.translationText.innerHTML = '';

    try {
        const url = `api.php?endpoint=surah&surah=${number}&category=${state.currentCategory || ''}`;
        const res = await fetch(url).then(r => r.json());

        if (res.status === 'success') {
            state.surahData = res.data;

            // Handle Assignment Groups
            if (res.data.groups && res.data.groups.length > 0) {
                state.surahGroups = res.data.groups;
                // If starting at end (coming from next surah), go to last group
                state.currentGroupIndex = startAtEnd ? state.surahGroups.length - 1 : 0;
            } else {
                state.surahGroups = [];
                state.currentGroupIndex = 0;
            }

            renderSurah();
            updateNavigation();
        } else {
            els.surahTitle.textContent = __('error');
        }
    } catch (e) {
        console.error('Error loading surah', e);
        els.surahTitle.textContent = __('error');
    }
}

// Load the first available surah for a category
async function loadFirstSurahForCategory(categoryId) {
    try {
        const res = await fetch(`api.php?endpoint=surahs&category_id=${categoryId}`).then(r => r.json());
        if (res.status === 'success' && res.data && res.data.length > 0) {
            state.currentSurah = res.data[0].number;
            updateUrl();
            await loadSurah(state.currentSurah);
        } else {
            // No surahs found for this category - show message
            els.surahTitle.textContent = __('no_verses_category') || 'Aucun verset pour cette catégorie';
            els.verseText.innerHTML = '';
            els.translationText.innerHTML = '';
        }
    } catch (e) {
        console.error('Error loading surahs for category', e);
    }
}

// --- Rendering ---

// Text scroll state
let textScrollPosition = 0;
let arabicScrollPosition = 0;
const LINE_HEIGHT_EM = 2.2;
const VISIBLE_LINES = 5;

function renderSurah() {
    if (!state.surahData) {
        return;
    }

    const { surah, ayahs, next_surah, prev_surah } = state.surahData;
    const lang = state.currentLanguage;

    // Determine which ayahs to show: Groups or Flat List
    let ayahsToShow = ayahs;
    if (state.surahGroups.length > 0) {
        const currentGroup = state.surahGroups[state.currentGroupIndex];
        // Title can optionally indicate group progress "Group 1/3"
        // For now, stick to Surah Title
        ayahsToShow = currentGroup.ayahs;

        // Debugging / Context Info
        // console.log(`Showing Group ${state.currentGroupIndex + 1} of ${state.surahGroups.length}`);
    }

    // Title & Info
    const name = JSON.parse(surah.name);
    els.surahTitle.textContent = lang === 'ar' ? name['ar'] : (name[lang] || name['en']);
    els.verseRef.textContent = `AYAT ${surah.ayah_count} • ${surah.revelation_type}`;

    // Display Tags
    if (els.groupTags) {
        els.groupTags.innerHTML = '';
        if (state.surahGroups.length > 0) {
            const currentGroup = state.surahGroups[state.currentGroupIndex];
            if (currentGroup.tags && currentGroup.tags.length > 0) {
                currentGroup.tags.forEach(tag => {
                    const badge = document.createElement('span');
                    badge.className = 'tag-badge';
                    // Use CSS variables or inline styles for colors
                    badge.style.background = `${tag.color}20`;
                    badge.style.color = tag.color;
                    badge.style.border = `1px solid ${tag.color}40`;
                    badge.style.fontSize = '0.7rem';
                    badge.style.padding = '0.1rem 0.5rem';
                    badge.style.borderRadius = '999px';
                    badge.style.fontWeight = '500';
                    badge.textContent = tag.name;
                    els.groupTags.appendChild(badge);
                });
            }
        }
    }

    const edition = window.languageEditions?.[lang] || 'quran-uthmani';
    const source = window.importSource || 'alquran.cloud';
    els.sourceAttribution.textContent = `${edition} • ${source}`;

    // Prepare Arabic text
    let fullArabic = '';
    ayahsToShow.forEach(ayah => {
        const text = JSON.parse(ayah.text);
        const arabicNum = toArabicNumerals(ayah.ayah_number);
        fullArabic += `${text.ar} <span class="ayah-ornament">۝${arabicNum}</span> `;
    });

    // Reset text scroll
    textScrollPosition = 0;
    arabicScrollPosition = 0;

    if (lang === 'ar') {
        // Mode Arabic Only: Show only Arabic text with 4-line scroll
        els.verseText.innerHTML = fullArabic;
        els.verseText.style.transform = 'translateY(0)';
        els.arabicWrapper.className = 'arabic-block is-primary';

        // Hide translation wrapper
        els.translationWrapper.style.display = 'none';
        els.textScrollArrows.classList.add('hidden');

        // Check for Arabic overflow (after DOM render)
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                checkArabicOverflow();
            });
        });
    } else {
        // Mode Translated: Show translation + Arabic
        let translationContent = '';
        ayahsToShow.forEach(ayah => {
            const text = JSON.parse(ayah.text);
            const tText = text[lang] || text['en'] || '';
            translationContent += `${tText} <span class="ayah-number">${ayah.ayah_number}</span> `;
        });

        // Show translation
        els.translationWrapper.style.display = 'flex';
        els.translationText.className = 'translation-text-area';

        // Ensure translation-inner exists in the DOM (recreate if missing)
        let innerEl = document.getElementById('translation-inner');
        if (!innerEl) {
            innerEl = document.createElement('div');
            innerEl.id = 'translation-inner';
            els.translationText.appendChild(innerEl);
            els.translationInner = innerEl; // Update cached reference
        }

        innerEl.innerHTML = translationContent;
        innerEl.style.transform = 'translateY(0)';

        // Show Arabic at bottom
        els.verseText.innerHTML = fullArabic;
        els.verseText.style.transform = 'translateY(0)';
        els.arabicWrapper.className = 'arabic-block';

        // Check if text overflows and show/hide scroll arrows (after DOM render)
        // Use double requestAnimationFrame to ensure layout is computed
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                checkTextOverflow();
                checkArabicOverflow();
            });
        });
    }

    // Update Navigation Links
    state.nextSurahId = next_surah ? next_surah.number : null;
    state.prevSurahId = prev_surah ? prev_surah.number : null;
    updateNavigation();
}

function checkTextOverflow() {
    const inner = document.getElementById('translation-inner') || els.translationInner;
    const textArea = document.getElementById('translation-text') || els.translationText;

    if (!inner || !textArea) {
        return;
    }

    const contentHeight = inner.scrollHeight;
    const containerHeight = textArea.clientHeight;

    if (contentHeight > containerHeight + 5) {
        els.textScrollArrows.classList.remove('hidden');
        els.translationText.classList.add('has-overflow');
        updateTextScrollButtons();
    } else {
        els.textScrollArrows.classList.add('hidden');
        els.translationText.classList.remove('has-overflow');
    }
}

function scrollTextContent(direction) {
    if (!els.translationInner || !els.translationText) return;

    const contentHeight = els.translationInner.scrollHeight;
    const containerHeight = els.translationText.clientHeight;
    const maxScroll = contentHeight - containerHeight;

    // Scroll by approximately 2 lines
    const scrollStep = containerHeight * 0.4;

    if (direction === 'up') {
        textScrollPosition = Math.max(0, textScrollPosition - scrollStep);
    } else {
        textScrollPosition = Math.min(maxScroll, textScrollPosition + scrollStep);
    }

    els.translationInner.style.transform = `translateY(-${textScrollPosition}px)`;
    updateTextScrollButtons();
}

function updateTextScrollButtons() {
    if (!els.translationInner || !els.translationText) return;

    const contentHeight = els.translationInner.scrollHeight;
    const containerHeight = els.translationText.clientHeight;
    const maxScroll = contentHeight - containerHeight;

    els.textScrollUp.disabled = textScrollPosition <= 0;
    els.textScrollDown.disabled = textScrollPosition >= maxScroll - 1;
}

function checkArabicOverflow() {
    if (!els.verseText || !els.arabicTextArea) {
        return;
    }

    const contentHeight = els.verseText.scrollHeight;
    const containerHeight = els.arabicTextArea.clientHeight;

    if (contentHeight > containerHeight + 5) {
        els.arabicScrollArrows.classList.remove('hidden');
        els.arabicTextArea.classList.add('has-overflow');
        updateArabicScrollButtons();
    } else {
        els.arabicScrollArrows.classList.add('hidden');
        els.arabicTextArea.classList.remove('has-overflow');
    }
}

function scrollArabicContent(direction) {
    if (!els.verseText || !els.arabicTextArea) return;

    const contentHeight = els.verseText.scrollHeight;
    const containerHeight = els.arabicTextArea.clientHeight;
    const maxScroll = contentHeight - containerHeight;

    // Scroll by approximately 2 lines
    const scrollStep = containerHeight * 0.5;

    if (direction === 'up') {
        arabicScrollPosition = Math.max(0, arabicScrollPosition - scrollStep);
    } else {
        arabicScrollPosition = Math.min(maxScroll, arabicScrollPosition + scrollStep);
    }

    els.verseText.style.transform = `translateY(-${arabicScrollPosition}px)`;
    updateArabicScrollButtons();
}

function updateArabicScrollButtons() {
    if (!els.verseText || !els.arabicTextArea) return;

    const contentHeight = els.verseText.scrollHeight;
    const containerHeight = els.arabicTextArea.clientHeight;
    const maxScroll = contentHeight - containerHeight;

    els.arabicScrollUp.disabled = arabicScrollPosition <= 0;
    els.arabicScrollDown.disabled = arabicScrollPosition >= maxScroll - 1;
}

function updateNavigation() {
    // Check if we can move within groups
    const hasNextGroup = state.surahGroups.length > 0 && state.currentGroupIndex < state.surahGroups.length - 1;
    const hasPrevGroup = state.surahGroups.length > 0 && state.currentGroupIndex > 0;

    // Navigation logic:
    // Prev: Enabled if (HasPrevGroup OR HasPrevSurah)
    // Next: Enabled if (HasNextGroup OR HasNextSurah)

    els.btnPrev.disabled = !(hasPrevGroup || state.prevSurahId);
    els.btnNext.disabled = !(hasNextGroup || state.nextSurahId);
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
    // Bottom navigation
    els.btnPrev.addEventListener('click', () => {
        if (state.surahGroups.length > 0 && state.currentGroupIndex > 0) {
            // Move to previous group
            state.currentGroupIndex--;
            renderSurah();
            return;
        }

        if (state.prevSurahId) {
            state.currentSurah = state.prevSurahId;
            updateUrl();
            loadSurah(state.currentSurah, true); // startAtEnd = true
        }
    });

    els.btnNext.addEventListener('click', () => {
        if (state.surahGroups.length > 0 && state.currentGroupIndex < state.surahGroups.length - 1) {
            // Move to next group
            state.currentGroupIndex++;
            renderSurah();
            return;
        }

        if (state.nextSurahId) {
            state.currentSurah = state.nextSurahId;
            updateUrl();
            loadSurah(state.currentSurah);
        }
    });

    // Text scroll arrows
    if (els.textScrollUp) {
        els.textScrollUp.addEventListener('click', () => scrollTextContent('up'));
    }
    if (els.textScrollDown) {
        els.textScrollDown.addEventListener('click', () => scrollTextContent('down'));
    }

    // Arabic scroll arrows
    if (els.arabicScrollUp) {
        els.arabicScrollUp.addEventListener('click', () => scrollArabicContent('up'));
    }
    if (els.arabicScrollDown) {
        els.arabicScrollDown.addEventListener('click', () => scrollArabicContent('down'));
    }

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
        els.modalConfirm.addEventListener('click', async () => {
            closePicker();
            updateUrl();
            // Load first available surah for the selected category
            if (state.currentCategory) {
                await loadFirstSurahForCategory(state.currentCategory);
            } else {
                await loadSurah(state.currentSurah);
            }
        });
    }

    // Picker Tabs
    els.typeTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const type = tab.dataset.type;
            setActiveType(type);
        });
    });

    // Keyboard Navigation
    document.addEventListener('keydown', (e) => {
        // Picker Modal keyboard navigation
        if (!els.pickerModal.classList.contains('hidden')) {
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                navigatePicker(-1);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                navigatePicker(1);
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                navigateTabs(-1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                navigateTabs(1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                closePicker();
                updateUrl();
                if (state.currentCategory) {
                    loadFirstSurahForCategory(state.currentCategory);
                } else {
                    loadSurah(state.currentSurah);
                }
            } else if (e.key === 'Escape') {
                closePicker();
            }
            return;
        }

        // Main view keyboard navigation
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            e.preventDefault();
            if (e.key === 'ArrowLeft') {
                els.btnPrev.click();
            } else {
                els.btnNext.click();
            }
        }
    });

    // Touch/Swipe support for text scrolling (vertical) and surah navigation (horizontal)
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    els.translationWrapper.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    els.translationWrapper.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        const swipeThreshold = 50;
        const diffX = touchStartX - touchEndX;
        const diffY = touchStartY - touchEndY;

        // Determine if swipe is more horizontal or vertical
        if (Math.abs(diffX) > Math.abs(diffY)) {
            // Horizontal swipe - surah navigation
            if (Math.abs(diffX) > swipeThreshold) {
                if (diffX > 0) {
                    els.btnNext.click();
                } else {
                    els.btnPrev.click();
                }
            }
        } else {
            // Vertical swipe - text scrolling
            if (Math.abs(diffY) > swipeThreshold) {
                if (diffY > 0) {
                    scrollTextContent('down');
                } else {
                    scrollTextContent('up');
                }
            }
        }
    }

    // Picker Arrows
    if (els.modalArrowUp) {
        els.modalArrowUp.addEventListener('click', () => navigatePicker(-1));
    }
    if (els.modalArrowDown) {
        els.modalArrowDown.addEventListener('click', () => navigatePicker(1));
    }

    // Language
    if (els.langSelect) {
        els.langSelect.addEventListener('change', (e) => {
            setCookie('sadaa_lang', e.target.value, 365);
            state.currentLanguage = e.target.value;
            window.location.reload();
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
    void els.pickerModal.offsetWidth;
    els.pickerModal.classList.add('visible');

    // Sync UI with current category state
    const currentCat = state.categories.find(c => c.id == state.currentCategory);

    // Determine which type to select
    let typeSlug = null;
    if (currentCat) {
        // Find type slug for current category
        const type = state.types.find(t => t.id == currentCat.type_id);
        if (type) typeSlug = type.slug;
    }

    // Fallback: use first type if available
    if (!typeSlug && state.types.length > 0) {
        typeSlug = state.types[0].slug;
    }

    if (typeSlug) {
        setActiveType(typeSlug, false);
    }

    renderPickerItems();
    scrollToActiveCategory();
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

// Picker Render Logic
let currentPickerCategories = [];

function setActiveType(typeSlug, render = true) {
    // Update Tabs UI
    els.typeTabs.forEach(t => {
        if (t.dataset.type === typeSlug) t.classList.add('active');
        else t.classList.remove('active');
    });

    // Filter categories
    // Map slug to type_id
    const typeObj = state.types.find(t => t.slug === typeSlug);
    if (!typeObj) return;

    currentPickerCategories = state.categories.filter(c => c.type_id == typeObj.id);

    if (render) {
        // Auto select first if current category not in this type
        const stillExists = currentPickerCategories.find(c => c.id == state.currentCategory);
        if (!stillExists && currentPickerCategories.length > 0) {
            state.currentCategory = currentPickerCategories[0].id;
            updatePickerUI(); // updates trigger text
        }
        renderPickerItems();
        scrollToActiveCategory();
    }
}

function navigateTabs(dir) {
    const tabs = Array.from(els.typeTabs);
    const activeIndex = tabs.findIndex(t => t.classList.contains('active'));
    if (activeIndex === -1) return;

    let newIndex = activeIndex + dir;
    if (newIndex < 0) newIndex = tabs.length - 1;
    if (newIndex >= tabs.length) newIndex = 0;

    const newType = tabs[newIndex].dataset.type;
    setActiveType(newType);
}

function navigatePicker(dir) {
    if (currentPickerCategories.length === 0) return;

    const currentIndex = currentPickerCategories.findIndex(c => c.id == state.currentCategory);
    let newIndex = currentIndex + dir;

    if (newIndex < 0) newIndex = currentPickerCategories.length - 1;
    if (newIndex >= currentPickerCategories.length) newIndex = 0;

    state.currentCategory = currentPickerCategories[newIndex].id;
    updatePickerUI();
    renderPickerItems(); // re-render to update active class
    scrollToActiveCategory();
}

function scrollToActiveCategory() {
    // Calculate transformY to center the active item
    // Item height = 40px base. Active = 60px.
    // Container usually shows 3 items? 
    // Logic: Center the active index.
    // Let's assume uniform height logic for simplicity or center based on index
    // The CSS logic `picker-track` usually transforms.
    // For simplicity here, let's just re-render and ensure style updates.
    // Detailed scroll logic:
    const activeIdx = currentPickerCategories.findIndex(c => c.id == state.currentCategory);
    if (activeIdx === -1) return;

    // Move track so active item is in middle. 
    // Viewport height ~ 120px. Middle is 60px.
    // Item heights: 40px each. Active one is 60px.
    // If we have items 0..N.
    // Position of active item center = (Sum of prev items height) + (ActiveHeight/2).
    // Just use simple index * 40px offset approximation + generic offset?
    // Let's use specific logic if available from landing page

    const itemHeight = 40;
    // We want the center of the active item to be at vertical center (60px)
    // Roughly: Center = 60.
    // Y Position of item I = I * 40.
    // But active item is taller? css handles scaling. layout flow is still flex col.
    // We translate track up by (Index * 40) - (ViewportHeight/2) + (ItemHeight/2)
    const offset = -(activeIdx * itemHeight) + (120 / 2) - (itemHeight / 2);
    els.modalPickerTrack.style.transform = `translateY(${offset}px)`;

    // Update arrows state
    if (els.modalArrowUp) els.modalArrowUp.disabled = activeIdx === 0;
    if (els.modalArrowDown) els.modalArrowDown.disabled = activeIdx === currentPickerCategories.length - 1;

    // Update Description
    const cat = currentPickerCategories[activeIdx];
    if (cat && els.modalDescription) {
        els.modalDescription.textContent = getLocalized(cat.description) || getLocalized(cat.name);
    }
}

function renderPickerItems() {
    const container = els.modalPickerTrack;
    container.innerHTML = '';

    currentPickerCategories.forEach((cat) => {
        const isActive = cat.id == state.currentCategory;
        const item = document.createElement('div');
        item.className = `picker-item ${isActive ? 'active' : ''}`;
        item.innerHTML = `<iconify-icon icon="${cat.icon}"></iconify-icon> ${getLocalized(cat.name)}`;
        item.onclick = () => {
            state.currentCategory = cat.id;
            updatePickerUI();
            renderPickerItems();
            scrollToActiveCategory();
        };
        container.appendChild(item);
    });
}

// ============================================
// Quran Reader Modal
// ============================================

const readerState = {
    isOpen: false,
    currentSurah: 1,
    currentAyah: 1,
    startAyah: null, // Ayah to scroll to when opening
    surahData: null,
    allSurahs: [],
    fontSize: 3, // 1-6 scale
    ayahsPerPage: 10
};

const readerEls = {
    modal: document.getElementById('reader-modal'),
    backdrop: document.querySelector('.reader-modal-backdrop'),
    closeBtn: document.getElementById('reader-close'),
    surahName: document.getElementById('reader-surah-name'),
    verseIndicator: document.getElementById('reader-verse-indicator'),
    surahSelect: document.getElementById('reader-surah-select'),
    prevSurah: document.getElementById('reader-prev-surah'),
    nextSurah: document.getElementById('reader-next-surah'),
    textContainer: document.getElementById('reader-text'),
    prevPage: document.getElementById('reader-prev-page'),
    nextPage: document.getElementById('reader-next-page'),
    currentAyah: document.getElementById('reader-current-ayah'),
    totalAyahs: document.getElementById('reader-total-ayahs'),
    fontDecrease: document.getElementById('reader-font-decrease'),
    fontIncrease: document.getElementById('reader-font-increase'),
    btnReadQuran: document.getElementById('btn-read-quran')
};

// Initialize Reader
async function initQuranReader() {
    if (!readerEls.modal) return;

    // Load all surahs for the dropdown
    await loadAllSurahs();

    // Setup event listeners
    setupReaderEventListeners();
}

async function loadAllSurahs() {
    try {
        const res = await fetch('api.php?endpoint=surahs').then(r => r.json());
        if (res.status === 'success') {
            readerState.allSurahs = res.data;
            populateSurahSelect();
        }
    } catch (e) {
        console.error('Error loading surahs for reader', e);
    }
}

function populateSurahSelect() {
    if (!readerEls.surahSelect) return;

    const lang = state.currentLanguage || 'ar';
    const isArabic = lang === 'ar';

    readerEls.surahSelect.innerHTML = '';
    readerState.allSurahs.forEach(surah => {
        const option = document.createElement('option');
        option.value = surah.number;
        const name = typeof surah.name === 'string' ? JSON.parse(surah.name) : surah.name;
        const displayName = name[lang] || name.en || name.ar;
        option.textContent = `${surah.number}. ${displayName}`;
        readerEls.surahSelect.appendChild(option);
    });

    // Update select direction based on language
    if (isArabic) {
        readerEls.surahSelect.style.direction = 'rtl';
    } else {
        readerEls.surahSelect.style.direction = 'ltr';
    }
}

function setupReaderEventListeners() {
    // Open reader button
    if (readerEls.btnReadQuran) {
        readerEls.btnReadQuran.addEventListener('click', openReader);
    }

    // Close button
    if (readerEls.closeBtn) {
        readerEls.closeBtn.addEventListener('click', closeReader);
    }

    // Backdrop click
    if (readerEls.backdrop) {
        readerEls.backdrop.addEventListener('click', closeReader);
    }

    // Surah select
    if (readerEls.surahSelect) {
        readerEls.surahSelect.addEventListener('change', (e) => {
            readerState.currentSurah = parseInt(e.target.value);
            readerState.currentAyah = 1;
            loadReaderSurah();
        });
    }

    // Surah navigation
    if (readerEls.prevSurah) {
        readerEls.prevSurah.addEventListener('click', () => {
            if (readerState.currentSurah > 1) {
                readerState.currentSurah--;
                readerState.currentAyah = 1;
                loadReaderSurah();
            }
        });
    }
    if (readerEls.nextSurah) {
        readerEls.nextSurah.addEventListener('click', () => {
            if (readerState.currentSurah < 114) {
                readerState.currentSurah++;
                readerState.currentAyah = 1;
                loadReaderSurah();
            }
        });
    }

    // Page navigation (ayah-based)
    if (readerEls.prevPage) {
        readerEls.prevPage.addEventListener('click', () => {
            navigateAyahs(-readerState.ayahsPerPage);
        });
    }
    if (readerEls.nextPage) {
        readerEls.nextPage.addEventListener('click', () => {
            navigateAyahs(readerState.ayahsPerPage);
        });
    }

    // Font size controls
    if (readerEls.fontDecrease) {
        readerEls.fontDecrease.addEventListener('click', () => {
            if (readerState.fontSize > 1) {
                readerState.fontSize--;
                updateFontSize();
            }
        });
    }
    if (readerEls.fontIncrease) {
        readerEls.fontIncrease.addEventListener('click', () => {
            if (readerState.fontSize < 6) {
                readerState.fontSize++;
                updateFontSize();
            }
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!readerState.isOpen) return;

        if (e.key === 'Escape') {
            closeReader();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            navigateAyahs(readerState.ayahsPerPage);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            navigateAyahs(-readerState.ayahsPerPage);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (readerState.currentSurah > 1) {
                readerState.currentSurah--;
                readerState.currentAyah = 1;
                loadReaderSurah();
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (readerState.currentSurah < 114) {
                readerState.currentSurah++;
                readerState.currentAyah = 1;
                loadReaderSurah();
            }
        }
    });

    // Touch swipe for mobile
    let touchStartX = 0;
    const readerContent = document.querySelector('.reader-content');
    if (readerContent) {
        readerContent.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        readerContent.addEventListener('touchend', (e) => {
            const touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    navigateAyahs(readerState.ayahsPerPage);
                } else {
                    navigateAyahs(-readerState.ayahsPerPage);
                }
            }
        }, { passive: true });
    }
}

function openReader() {
    if (!readerEls.modal) return;

    readerState.isOpen = true;

    // Start from current surah being viewed
    readerState.currentSurah = state.currentSurah || 1;

    // Try to get the first ayah currently displayed
    let startAyah = 1;
    if (state.surahGroups && state.surahGroups.length > 0) {
        const currentGroup = state.surahGroups[state.currentGroupIndex];
        if (currentGroup && currentGroup.ayahs && currentGroup.ayahs.length > 0) {
            startAyah = currentGroup.ayahs[0].ayah_number || 1;
        }
    } else if (state.surahData && state.surahData.ayahs && state.surahData.ayahs.length > 0) {
        startAyah = state.surahData.ayahs[0].ayah_number || 1;
    }

    readerState.startAyah = startAyah;
    readerState.currentAyah = startAyah;

    // Show modal
    readerEls.modal.classList.remove('hidden');
    void readerEls.modal.offsetWidth; // Force reflow
    readerEls.modal.classList.add('visible');

    // Prevent body scroll
    document.body.style.overflow = 'hidden';

    // Load surah data
    loadReaderSurah();
}

function closeReader() {
    if (!readerEls.modal) return;

    readerState.isOpen = false;
    readerEls.modal.classList.remove('visible');

    setTimeout(() => {
        readerEls.modal.classList.add('hidden');
    }, 400);

    // Restore body scroll
    document.body.style.overflow = '';
}

async function loadReaderSurah() {
    if (!readerEls.textContainer) return;

    // Show loading
    readerEls.textContainer.innerHTML = `<div class="reader-loading">${__('loading')}</div>`;

    try {
        const res = await fetch(`api.php?endpoint=surah&surah=${readerState.currentSurah}`).then(r => r.json());

        if (res.status === 'success') {
            readerState.surahData = res.data;
            renderReaderContent();
            updateReaderUI();
        } else {
            readerEls.textContainer.innerHTML = `<div class="reader-error">${__('error')}</div>`;
        }
    } catch (e) {
        console.error('Error loading surah for reader', e);
        readerEls.textContainer.innerHTML = `<div class="reader-error">${__('error')}</div>`;
    }
}

function renderReaderContent() {
    if (!readerState.surahData || !readerEls.textContainer) return;

    const { surah, ayahs } = readerState.surahData;
    const lang = state.currentLanguage || 'ar';
    const isArabic = lang === 'ar';

    // Build text in user's language
    let html = '';

    // Add Bismillah for all surahs except At-Tawbah (9)
    if (surah.number !== 9) {
        html += '<span class="bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</span>';
    }

    ayahs.forEach((ayah) => {
        const text = typeof ayah.text === 'string' ? JSON.parse(ayah.text) : ayah.text;
        // Get text in user's language, fallback to Arabic
        const displayText = text[lang] || text.ar || '';

        html += `<span class="ayah" data-ayah="${ayah.ayah_number}">${displayText}</span>`;
        html += `<span class="ayah-end">${ayah.ayah_number}</span>`;
    });

    readerEls.textContainer.innerHTML = html;

    // Update text direction and styling based on language
    if (isArabic) {
        readerEls.textContainer.classList.add('is-arabic');
        readerEls.textContainer.classList.remove('is-translation');
    } else {
        readerEls.textContainer.classList.remove('is-arabic');
        readerEls.textContainer.classList.add('is-translation');
    }

    // Scroll to the starting ayah if specified
    if (readerState.startAyah && readerState.startAyah > 1) {
        setTimeout(() => {
            scrollToAyah(readerState.startAyah);
            readerState.currentAyah = readerState.startAyah;
            if (readerEls.currentAyah) {
                readerEls.currentAyah.textContent = readerState.startAyah;
            }
            readerState.startAyah = null; // Reset
        }, 100);
    } else {
        // Scroll to top
        const readerContent = document.querySelector('.reader-content');
        if (readerContent) {
            readerContent.scrollTop = 0;
        }
    }
}

function updateReaderUI() {
    if (!readerState.surahData) return;

    const { surah, ayahs } = readerState.surahData;
    const name = typeof surah.name === 'string' ? JSON.parse(surah.name) : surah.name;
    const lang = state.currentLanguage || 'ar';
    const isArabic = lang === 'ar';

    // Update RTL class on modal content
    const modalContent = document.querySelector('.reader-modal-content');
    if (modalContent) {
        if (isArabic) {
            modalContent.classList.add('is-rtl');
        } else {
            modalContent.classList.remove('is-rtl');
        }
    }

    // Update surah name in user's language
    if (readerEls.surahName) {
        const displayName = name[lang] || name.en || name.ar;
        readerEls.surahName.textContent = displayName;

        // Update styling based on language
        if (isArabic) {
            readerEls.surahName.classList.remove('is-translation');
        } else {
            readerEls.surahName.classList.add('is-translation');
        }
    }

    // Update verse indicator
    if (readerEls.verseIndicator) {
        const revType = surah.revelation_type === 'meccan' ? __('meccan') || 'مكية' : __('medinan') || 'مدنية';
        readerEls.verseIndicator.textContent = `${surah.ayah_count} ${__('verses') || 'آيات'} • ${revType}`;
    }

    // Update select
    if (readerEls.surahSelect) {
        readerEls.surahSelect.value = readerState.currentSurah;
    }

    // Update surah navigation buttons
    if (readerEls.prevSurah) {
        readerEls.prevSurah.disabled = readerState.currentSurah <= 1;
    }
    if (readerEls.nextSurah) {
        readerEls.nextSurah.disabled = readerState.currentSurah >= 114;
    }

    // Update ayah counter
    if (readerEls.currentAyah) {
        readerEls.currentAyah.textContent = readerState.currentAyah;
    }
    if (readerEls.totalAyahs) {
        readerEls.totalAyahs.textContent = ayahs.length;
    }

    // Update page navigation
    updatePageNavigation();
}

function navigateAyahs(delta) {
    if (!readerState.surahData) return;

    const totalAyahs = readerState.surahData.ayahs.length;
    let newAyah = readerState.currentAyah + delta;

    // Handle surah boundaries
    if (newAyah < 1) {
        // Go to previous surah
        if (readerState.currentSurah > 1) {
            readerState.currentSurah--;
            readerState.currentAyah = 1;
            loadReaderSurah();
        }
        return;
    }

    if (newAyah > totalAyahs) {
        // Go to next surah
        if (readerState.currentSurah < 114) {
            readerState.currentSurah++;
            readerState.currentAyah = 1;
            loadReaderSurah();
        }
        return;
    }

    readerState.currentAyah = newAyah;

    // Scroll to the ayah
    scrollToAyah(newAyah);

    // Update UI
    if (readerEls.currentAyah) {
        readerEls.currentAyah.textContent = newAyah;
    }
    updatePageNavigation();
}

function scrollToAyah(ayahNumber) {
    const ayahEl = readerEls.textContainer?.querySelector(`.ayah[data-ayah="${ayahNumber}"]`);
    if (ayahEl) {
        const readerContent = document.querySelector('.reader-content');
        if (readerContent) {
            const offsetTop = ayahEl.offsetTop - readerContent.offsetTop - 20;
            readerContent.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
        }
    }
}

function updatePageNavigation() {
    if (!readerState.surahData) return;

    const totalAyahs = readerState.surahData.ayahs.length;

    if (readerEls.prevPage) {
        readerEls.prevPage.disabled = readerState.currentAyah <= 1 && readerState.currentSurah <= 1;
    }
    if (readerEls.nextPage) {
        readerEls.nextPage.disabled = readerState.currentAyah >= totalAyahs && readerState.currentSurah >= 114;
    }
}

function updateFontSize() {
    if (!readerEls.textContainer) return;

    // Remove all font size classes
    for (let i = 1; i <= 6; i++) {
        readerEls.textContainer.classList.remove(`font-size-${i}`);
    }

    // Add current font size class
    readerEls.textContainer.classList.add(`font-size-${readerState.fontSize}`);

    // Update button states
    if (readerEls.fontDecrease) {
        readerEls.fontDecrease.disabled = readerState.fontSize <= 1;
    }
    if (readerEls.fontIncrease) {
        readerEls.fontIncrease.disabled = readerState.fontSize >= 6;
    }
}

// Initialize reader when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initQuranReader();
    updateFontSize();
});
