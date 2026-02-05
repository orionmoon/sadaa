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
    currentBook: getCookie('sadaa_book') || 'quran',
    categories: [],
    types: [],
    surahData: null,
    surahGroups: [],
    currentGroupIndex: 0,
    currentPage: 0,
    totalPages: 0,
    pages: [],
    categorySurahs: []
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
    modalSource: document.getElementById('modal-source'),
    modalActiveTypeLabel: document.getElementById('modal-active-type-label'),
    typeTabs: document.querySelectorAll('.tab'),
    // Modal Tabs Scroll
    modalTabsScroll: document.getElementById('modal-type-tabs-scroll'),
    modalTabNavLeft: document.getElementById('modal-tab-nav-left'),
    modalTabNavRight: document.getElementById('modal-tab-nav-right'),
    // Mobile selectors
    langToggleMobile: document.getElementById('lang-toggle-mobile'),
    bookToggleMobile: document.getElementById('book-toggle-mobile'),
    langModal: document.getElementById('lang-modal'),
    bookModal: document.getElementById('book-modal'),
    langSelect: document.getElementById('lang-select'),
    bookSelect: document.getElementById('book-select'),
    groupTags: document.getElementById('group-tags'),
    themeToggle: document.getElementById('theme-toggle'),
    // Share elements
    btnShare: document.getElementById('btn-share'),
    shareModal: document.getElementById('share-modal'),
    btnCloseShare: document.getElementById('btn-close-share'),
    btnDownloadImage: document.getElementById('btn-download-image'),
    cardAyahRef: document.getElementById('card-ayah-ref'),
    cardAyahArabic: document.getElementById('card-ayah-arabic'),
    cardAyahTranslation: document.getElementById('card-ayah-translation'),
    cardSurahTitle: document.getElementById('card-surah-title'),
    cardCategory: document.getElementById('card-category'),
    shareCard: document.getElementById('share-card-story'),
    btnFormats: document.querySelectorAll('.btn-format'),
    btnThemes: document.querySelectorAll('.btn-theme'),
    shareBgGallery: document.getElementById('share-bg-gallery'),
    cardCustomBg: document.querySelector('.card-custom-bg'),
    toggleArabicText: document.getElementById('toggle-arabic-text')
};

// Init
document.addEventListener('DOMContentLoaded', async () => {
    // Parse URL params
    const params = new URLSearchParams(window.location.search);
    const hasSurahParam = params.has('surah');
    if (hasSurahParam) state.currentSurah = parseInt(params.get('surah'));
    if (params.has('category')) state.currentCategory = parseInt(params.get('category'));

    // If no category in URL but page has initialCategoryId (from slug), use it
    if (!state.currentCategory && window.initialCategoryId) {
        state.currentCategory = window.initialCategoryId;
    }

    // Set initial selects
    if (els.langSelect) els.langSelect.value = state.currentLanguage;
    if (els.bookSelect) els.bookSelect.value = state.currentBook;

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
            fetch('/api.php?endpoint=categories').then(r => r.json()),
            fetch('/api.php?endpoint=types').then(r => r.json())
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
        // Load surah data and category surahs in parallel if in category mode
        const promises = [];

        // Main surah data
        const url = `/api.php?endpoint=surah&surah=${number}&category=${state.currentCategory || ''}`;
        promises.push(fetch(url).then(r => r.json()));

        // If in category mode and categorySurahs is empty, load them
        if (state.currentCategory && state.categorySurahs.length === 0) {
            const catUrl = `/api.php?endpoint=surahs&category_id=${state.currentCategory}`;
            promises.push(fetch(catUrl).then(r => r.json()));
        }

        const results = await Promise.all(promises);
        const res = results[0];

        // If we loaded category surahs, store them
        if (results[1] && results[1].status === 'success' && results[1].data) {
            state.categorySurahs = results[1].data;
        }

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
        const res = await fetch(`/api.php?endpoint=surahs&category_id=${categoryId}`).then(r => r.json());
        if (res.status === 'success' && res.data && res.data.length > 0) {
            state.categorySurahs = res.data;
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
    const revType = surah.revelation_type === 'meccan' ? __('meccan') : __('medinan');
    els.verseRef.textContent = `${surah.ayah_count} ${__('verses')} • ${revType}`;

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
                updateArabicScrollButtons();
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
                updateTextScrollButtons();
                updateArabicScrollButtons();
            });
        });
    }

    // Update Navigation Links
    // When in category mode, use category boundaries instead of chronological next/prev
    if (state.currentCategory && state.categorySurahs.length > 0) {
        const currentSurahNum = parseInt(surah.number, 10);
        const currentIndex = state.categorySurahs.findIndex(s => parseInt(s.number, 10) === currentSurahNum);

        if (currentIndex !== -1) {
            state.prevSurahId = currentIndex > 0 ? state.categorySurahs[currentIndex - 1].number : null;
            state.nextSurahId = currentIndex < state.categorySurahs.length - 1 ? state.categorySurahs[currentIndex + 1].number : null;
        } else {
            state.nextSurahId = null;
            state.prevSurahId = null;
        }
    } else {
        // Not in category mode - use chronological next/prev from API
        state.nextSurahId = next_surah ? next_surah.number : null;
        state.prevSurahId = prev_surah ? prev_surah.number : null;
    }
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
    els.textScrollDown.disabled = textScrollPosition >= maxScroll - 5;
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
    els.arabicScrollDown.disabled = arabicScrollPosition >= maxScroll - 5;
}

function updateNavigation() {
    // Check if we can move within groups
    const hasNextGroup = state.surahGroups.length > 0 && state.currentGroupIndex < state.surahGroups.length - 1;
    const hasPrevGroup = state.surahGroups.length > 0 && state.currentGroupIndex > 0;

    // Navigation logic:
    // Prev: Enabled if (HasPrevGroup OR HasPrevSurah)
    // Next: Enabled if (HasNextGroup OR HasNextSurah)
    const shouldDisableNext = !(hasNextGroup || state.nextSurahId);
    const shouldDisablePrev = !(hasPrevGroup || state.prevSurahId);

    els.btnPrev.disabled = shouldDisablePrev;
    els.btnNext.disabled = shouldDisableNext;
}

function updatePickerUI() {
    // Find category object
    const cat = state.categories.find(c => c.id == state.currentCategory);
    if (cat) {
        const nameEl = document.getElementById('current-category-name');
        const iconEl = document.getElementById('current-category-icon');
        if (nameEl) nameEl.textContent = getLocalized(cat.name);
        if (iconEl) iconEl.innerHTML = `<iconify-icon icon="${cat.icon}"></iconify-icon>`;
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

    // Intervations
    // Copy
    if (els.btnCopy) {
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
    }

    // Share
    if (els.btnShare) {
        els.btnShare.addEventListener('click', openShareModal);
    }
    if (els.btnCloseShare) {
        els.btnCloseShare.addEventListener('click', closeShareModal);
    }
    if (els.btnDownloadImage) {
        els.btnDownloadImage.addEventListener('click', downloadShareImage);
    }
    if (els.toggleArabicText) {
        els.toggleArabicText.addEventListener('change', () => {
            // Only toggle if current language is not Arabic
            if (state.currentLanguage !== 'ar') {
                if (els.cardAyahArabic) {
                    els.cardAyahArabic.style.display = els.toggleArabicText.checked ? '' : 'none';
                }
                // Re-scale text when visibility changes
                autoScaleVerseText();
            }
        });
    }

    // Share Format Switcher
    els.btnFormats.forEach(btn => {
        btn.addEventListener('click', () => {
            const format = btn.dataset.format;
            setShareFormat(format);
        });
    });

    // Share Theme Switcher
    els.btnThemes.forEach(btn => {
        btn.addEventListener('click', () => {
            const theme = btn.dataset.theme;
            setShareTheme(theme);
        });
    });

    // Share Modal Backdrop
    const shareBackdrop = els.shareModal ? els.shareModal.querySelector('.picker-modal-backdrop') : null;
    if (shareBackdrop) {
        shareBackdrop.addEventListener('click', closeShareModal);
    }

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

    // Modal Tab Scroll Navigation
    if (els.modalTabsScroll) {
        if (els.modalTabNavLeft && els.modalTabNavRight) {
            els.modalTabNavLeft.addEventListener('click', () => scrollModalTabsBy(-1));
            els.modalTabNavRight.addEventListener('click', () => scrollModalTabsBy(1));

            els.modalTabsScroll.addEventListener('scroll', updateModalTabArrows);
            window.addEventListener('resize', updateModalTabsWidth);
        }
    }

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

        // Language modal keyboard navigation
        if (els.langModal && !els.langModal.classList.contains('hidden')) {
            if (e.key === 'Escape') {
                closeSimpleModal(els.langModal);
            }
            return;
        }

        // Book modal keyboard navigation
        if (els.bookModal && !els.bookModal.classList.contains('hidden')) {
            if (e.key === 'Escape') {
                closeSimpleModal(els.bookModal);
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
    let currentTouchTarget = null;

    // Touch events for translation wrapper
    els.translationWrapper.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
        currentTouchTarget = 'translation';
    }, { passive: true });

    els.translationWrapper.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    }, { passive: true });

    // Touch events for arabic wrapper
    if (els.arabicWrapper) {
        els.arabicWrapper.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
            currentTouchTarget = 'arabic';
        }, { passive: true });

        els.arabicWrapper.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            handleSwipe();
        }, { passive: true });
    }

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
            // Vertical swipe - text scrolling based on target
            if (Math.abs(diffY) > swipeThreshold) {
                const direction = diffY > 0 ? 'down' : 'up';
                if (currentTouchTarget === 'arabic') {
                    scrollArabicContent(direction);
                } else {
                    scrollTextContent(direction);
                }
            }
        }
        currentTouchTarget = null;
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

    // Mobile language selector
    if (els.langToggleMobile && els.langModal) {
        els.langToggleMobile.addEventListener('click', () => openSimpleModal(els.langModal));

        // Language options click
        const langOptions = els.langModal.querySelectorAll('.simple-option');
        langOptions.forEach(opt => {
            opt.addEventListener('click', () => {
                const langCode = opt.dataset.lang;
                if (langCode) {
                    setCookie('sadaa_lang', langCode, 365);
                    state.currentLanguage = langCode;
                    window.location.reload();
                }
            });
        });

        // Close on backdrop click
        els.langModal.querySelector('.picker-modal-backdrop').addEventListener('click', () => {
            closeSimpleModal(els.langModal);
        });
    }

    // Mobile book selector
    if (els.bookToggleMobile && els.bookModal) {
        els.bookToggleMobile.addEventListener('click', () => openSimpleModal(els.bookModal));

        // Book options click
        const bookOptions = els.bookModal.querySelectorAll('.simple-option');
        bookOptions.forEach(opt => {
            opt.addEventListener('click', () => {
                const bookSlug = opt.dataset.book;
                if (bookSlug) {
                    // Update state
                    state.currentBook = bookSlug;

                    // Update desktop select if exists
                    if (els.bookSelect) {
                        els.bookSelect.value = bookSlug;
                    }

                    // Save preference
                    setCookie('sadaa_book', bookSlug, 365);

                    // Close modal
                    closeSimpleModal(els.bookModal);

                    // Show feedback (optional)
                    const bookLabel = opt.querySelector('.option-label')?.textContent || bookSlug;
                    console.log(`Switched to book: ${bookLabel}`);

                    // Reload data for the new book
                    if (state.currentCategory) {
                        loadFirstSurahForCategory(state.currentCategory);
                    } else if (state.currentSurah) {
                        loadSurah(state.currentSurah);
                    }
                }
            });
        });

        // Close on backdrop click
        els.bookModal.querySelector('.picker-modal-backdrop').addEventListener('click', () => {
            closeSimpleModal(els.bookModal);
        });
    }

    // Desktop book selector
    if (els.bookSelect) {
        els.bookSelect.addEventListener('change', (e) => {
            const bookSlug = e.target.value;
            if (bookSlug) {
                state.currentBook = bookSlug;
                setCookie('sadaa_book', bookSlug, 365);

                // Reload data
                if (state.currentCategory) {
                    loadFirstSurahForCategory(state.currentCategory);
                } else if (state.currentSurah) {
                    loadSurah(state.currentSurah);
                }
            }
        });
    }
}

// --- Simple Modal Helpers ---

function openSimpleModal(modal) {
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.classList.add('visible');
}

function closeSimpleModal(modal) {
    modal.classList.remove('visible');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// --- Helpers ---

// --- Modal Tabs Scroll Logic ---
function getEffectiveModalVisibleCount() {
    if (!els.modalTabsScroll) return 5;
    const configCount = parseInt(els.modalTabsScroll.dataset.visibleCount) || 5;
    // Mobile/Tablet adjustments: Max 2 tabs
    if (window.innerWidth < 768) return 2;
    return configCount;
}

function updateModalTabsWidth() {
    if (!els.modalTabsScroll) return;

    const visibleCount = getEffectiveModalVisibleCount();
    const tabs = els.modalTabsScroll.querySelectorAll('.tab');

    if (tabs.length <= visibleCount) {
        els.modalTabsScroll.style.maxWidth = 'none';
        updateModalTabArrows();
        return;
    }

    // Get gap from computed style
    const style = window.getComputedStyle(els.modalTabsScroll);
    const gap = parseFloat(style.gap) || parseFloat(style.columnGap) || 8;

    let totalWidth = 0;
    const countToMeasure = Math.min(visibleCount, tabs.length);
    for (let i = 0; i < countToMeasure; i++) {
        totalWidth += tabs[i].offsetWidth;
    }
    // Add gaps
    if (countToMeasure > 1) {
        totalWidth += gap * (countToMeasure - 1);
    }

    els.modalTabsScroll.style.maxWidth = totalWidth + 'px';
    updateModalTabArrows();
}

function updateModalTabArrows() {
    if (!els.modalTabsScroll || !els.modalTabNavLeft || !els.modalTabNavRight) return;

    const isRtl = document.documentElement.dir === 'rtl';
    const scrollLeft = els.modalTabsScroll.scrollLeft;
    const maxScroll = els.modalTabsScroll.scrollWidth - els.modalTabsScroll.clientWidth;

    if (isRtl) {
        els.modalTabNavRight.disabled = scrollLeft >= 0;
        els.modalTabNavLeft.disabled = scrollLeft <= -maxScroll + 5;
    } else {
        els.modalTabNavLeft.disabled = scrollLeft <= 5;
        els.modalTabNavRight.disabled = scrollLeft >= maxScroll - 5;
    }
}

function scrollModalTabsBy(direction) {
    if (!els.modalTabsScroll) return;

    const isRtl = document.documentElement.dir === 'rtl';
    const visibleCount = getEffectiveModalVisibleCount();
    const tabs = els.modalTabsScroll.querySelectorAll('.tab');

    let scrollAmount = 0;
    // Scroll by visible width or at most 2 tabs for better navigation on mobile
    const scrollTags = Math.min(visibleCount, 2);

    for (let i = 0; i < Math.min(scrollTags, tabs.length); i++) {
        scrollAmount += tabs[i].offsetWidth;
    }

    els.modalTabsScroll.scrollBy({
        left: direction * scrollAmount,
        behavior: 'smooth'
    });

    setTimeout(updateModalTabArrows, 300);
}

function openPicker() {
    els.pickerModal.classList.remove('hidden');
    void els.pickerModal.offsetWidth;
    els.pickerModal.classList.add('visible');

    // Init scrollable tabs width now that modal is visible
    updateModalTabsWidth();

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
    let activeTab = null;
    els.typeTabs.forEach(t => {
        if (t.dataset.type === typeSlug) {
            t.classList.add('active');
            activeTab = t;
        } else {
            t.classList.remove('active');
        }
    });

    // Update active type label for mobile
    if (els.modalActiveTypeLabel && activeTab) {
        const tabText = activeTab.querySelector('span');
        if (tabText) {
            els.modalActiveTypeLabel.textContent = tabText.textContent;
        }
    }

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
    const activeIdx = currentPickerCategories.findIndex(c => c.id == state.currentCategory);
    if (activeIdx === -1) return;

    // Same dynamic calculation as index page
    // Force browser to recalculate layout
    void els.modalPickerTrack.offsetHeight;

    // Get all picker items
    const items = els.modalPickerTrack.querySelectorAll('.picker-item');
    if (!items.length) return;

    const tempItem = items[0];
    const tempActive = items[activeIdx];

    // Force layout recalculation
    els.modalPickerTrack.getBoundingClientRect();
    tempActive.getBoundingClientRect();

    // Get computed styles (same technique as index page)
    const itemStyles = window.getComputedStyle(tempItem);
    const activeStyles = window.getComputedStyle(tempActive);

    const ITEM_HEIGHT = parseFloat(itemStyles.height) || 40;
    const ACTIVE_HEIGHT = parseFloat(activeStyles.height) || 60;
    const VIEWPORT_HEIGHT = parseFloat(window.getComputedStyle(els.modalPickerTrack.parentElement).height) || 140;
    const VIEWPORT_CENTER = VIEWPORT_HEIGHT / 2;

    // Calculate position: sum of normal items + half of active item height
    const position = (activeIdx * ITEM_HEIGHT) + (ACTIVE_HEIGHT / 2);
    const translateY = VIEWPORT_CENTER - position;

    els.modalPickerTrack.style.transform = `translateY(${translateY}px)`;

    // Update arrows state
    if (els.modalArrowUp) els.modalArrowUp.disabled = activeIdx === 0;
    if (els.modalArrowDown) els.modalArrowDown.disabled = activeIdx === currentPickerCategories.length - 1;

    // Update Description
    const cat = currentPickerCategories[activeIdx];
    if (cat && els.modalDescription) {
        els.modalDescription.textContent = getLocalized(cat.description) || getLocalized(cat.name);
    }
    // Update Source
    if (cat && els.modalSource) {
        const sourceText = getLocalized(cat.source);
        els.modalSource.textContent = sourceText || '';
        els.modalSource.style.display = sourceText ? 'block' : 'none';
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
            // scrollToActiveCategory is called after render via requestAnimationFrame
        };
        container.appendChild(item);
    });

    // Ensure scroll position is updated after DOM render
    requestAnimationFrame(() => {
        scrollToActiveCategory();
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
        const res = await fetch('/api.php?endpoint=surahs').then(r => r.json());
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
        const res = await fetch(`/api.php?endpoint=surah&surah=${readerState.currentSurah}`).then(r => r.json());

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

function openShareModal() {
    if (!els.shareModal) return;

    // Reset format and theme
    setShareFormat('story');
    setShareTheme('dark');
    setShareBackground(''); // Clear custom background

    // Initialize Arabic text toggle (only for non-Arabic languages)
    if (els.toggleArabicText) {
        const isArabic = state.currentLanguage === 'ar';
        // Show toggle only when language is not Arabic
        const toggleContainer = els.toggleArabicText.closest('.share-arabic-toggle');
        if (toggleContainer) {
            toggleContainer.style.display = isArabic ? 'none' : '';
        }
        if (!isArabic) {
            // Set default to checked and ensure Arabic text is visible
            els.toggleArabicText.checked = true;
            if (els.cardAyahArabic) {
                els.cardAyahArabic.style.display = '';
            }
        }
    }

    // Populate the card with current state data
    if (els.cardAyahRef) {
        els.cardAyahRef.innerText = els.verseRef.innerText;
        // Apply RTL and remove letter-spacing for Arabic text
        const currentLang = state.currentLanguage || 'ar';
        if (currentLang === 'ar') {
            els.cardAyahRef.style.direction = 'rtl';
            els.cardAyahRef.style.letterSpacing = '0';
            els.cardAyahRef.style.fontFamily = "'Noto Naskh Arabic', serif";
        } else {
            els.cardAyahRef.style.direction = 'ltr';
            els.cardAyahRef.style.letterSpacing = '';
            els.cardAyahRef.style.fontFamily = '';
        }
    }
    if (els.cardAyahArabic) {
        els.cardAyahArabic.innerHTML = els.verseText.innerHTML;
    }
    if (els.cardAyahTranslation) {
        const inner = document.getElementById('translation-inner');
        els.cardAyahTranslation.innerHTML = inner ? inner.innerHTML : els.translationText.innerHTML;
    }

    // Populate Category Description
    if (els.cardCategory && state.currentCategory && state.categories) {
        const cat = state.categories.find(c => c.id == state.currentCategory);
        if (cat) {
            const currentLang = state.currentLanguage || 'ar';
            const descJson = typeof cat.description === 'string' ? JSON.parse(cat.description) : cat.description;
            const categoryText = descJson[currentLang] || descJson.ar || cat.description_en || '';
            els.cardCategory.innerText = categoryText;
            // Apply RTL for Arabic text and fix letter-spacing issue
            if (currentLang === 'ar') {
                els.cardCategory.style.direction = 'rtl';
                els.cardCategory.style.fontFamily = "'Noto Naskh Arabic', serif";
                els.cardCategory.style.letterSpacing = '0';
                els.cardCategory.style.textTransform = 'none';
            } else {
                els.cardCategory.style.direction = 'ltr';
                els.cardCategory.style.fontFamily = "";
                els.cardCategory.style.letterSpacing = '';
                els.cardCategory.style.textTransform = '';
            }
        }
    }

    // Populate Surah Title
    if (els.cardSurahTitle && state.surahData && state.surahData.surah) {
        const surah = state.surahData.surah;
        const currentLang = state.currentLanguage || 'ar';
        const nameJson = typeof surah.name === 'string' ? JSON.parse(surah.name) : surah.name;
        els.cardSurahTitle.innerText = nameJson[currentLang] || nameJson.ar || surah.name_en;
    }

    // Load available backgrounds
    loadShareBackgrounds();

    // Auto-scale text to fit
    autoScaleVerseText();

    els.shareModal.classList.remove('hidden');
    void els.shareModal.offsetWidth; // Force reflow
    els.shareModal.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function setShareTheme(theme) {
    if (!els.shareCard) return;

    // Update buttons
    els.btnThemes.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.theme === theme);
    });

    // Update card class
    els.shareCard.classList.remove('theme-dark', 'theme-light');
    els.shareCard.classList.add(`theme-${theme}`);
}

async function loadShareBackgrounds() {
    if (!els.shareBgGallery) return;

    // Reset gallery
    const noneBtn = els.shareBgGallery.querySelector('.btn-bg-none');
    if (noneBtn) {
        noneBtn.onclick = () => setShareBackground('', noneBtn);
    }

    try {
        const res = await fetch('/api.php?endpoint=backgrounds').then(r => r.json());
        if (res.status === 'success' && res.data) {
            // Remove existing background buttons (except "none")
            const existingBgs = els.shareBgGallery.querySelectorAll('button:not(.btn-bg-none)');
            existingBgs.forEach(b => b.remove());

            res.data.forEach(bgFile => {
                const btn = document.createElement('button');
                btn.style.backgroundImage = `url('/assets/backgrounds/${bgFile}')`;
                btn.dataset.bg = bgFile;
                btn.onclick = () => setShareBackground(bgFile, btn);
                els.shareBgGallery.appendChild(btn);
            });
        }
    } catch (e) {
        console.error('Error loading backgrounds', e);
    }
}

function setShareBackground(bgFile, btn) {
    if (!els.cardCustomBg) return;

    // Update gallery active state
    els.shareBgGallery.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    if (bgFile) {
        els.cardCustomBg.style.backgroundImage = `url('/assets/backgrounds/${bgFile}')`;
        els.cardCustomBg.style.opacity = '1';
    } else {
        els.cardCustomBg.style.backgroundImage = 'none';
        els.cardCustomBg.style.opacity = '0';
    }
}

function setShareFormat(format) {
    if (!els.shareCard) return;

    // Update buttons
    els.btnFormats.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.format === format);
    });

    // Update card class
    els.shareCard.classList.remove('format-story', 'format-square');
    els.shareCard.classList.add(`format-${format}`);

    // Re-scale text because container size changed
    autoScaleVerseText();
}

function autoScaleVerseText() {
    if (!els.shareCard) return;

    const arabic = els.cardAyahArabic;
    const translation = els.cardAyahTranslation;
    const content = els.shareCard.querySelector('.card-content');
    const isSquare = els.shareCard.classList.contains('format-square');

    // Base sizes
    let arabicSize = 72;
    let translationSize = 56;
    let gap = 60;

    // Adjust based on length
    const arabicLen = arabic.innerText.length;
    const transLen = translation.innerText.length;

    // Standard scaling
    if (arabicLen > 200) arabicSize = 48;
    else if (arabicLen > 100) arabicSize = 60;

    if (transLen > 400) translationSize = 36;
    else if (transLen > 200) translationSize = 44;

    // Square format needs very aggressive scaling
    if (isSquare) {
        gap = 30; // Tighter gap

        // Even smaller base sizes for square
        arabicSize *= 0.85;
        translationSize *= 0.85;

        const totalLen = arabicLen + transLen;

        // Extreme cases handling for 1:1 format
        if (totalLen > 500) {
            arabicSize = Math.min(arabicSize, 34);
            translationSize = Math.min(translationSize, 28);
        } else if (totalLen > 300) {
            arabicSize = Math.min(arabicSize, 42);
            translationSize = Math.min(translationSize, 34);
        }
    }

    arabic.style.fontSize = `${arabicSize}px`;
    translation.style.fontSize = `${translationSize}px`;
    if (content) content.style.gap = `${gap}px`;
}

function closeShareModal() {
    if (!els.shareModal) return;
    els.shareModal.classList.remove('visible');
    setTimeout(() => {
        els.shareModal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

async function downloadShareImage() {
    if (!els.shareCard) return;

    const btn = els.btnDownloadImage;
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<iconify-icon icon="mdi:loading" class="spin"></iconify-icon> Génération...';
    btn.disabled = true;

    try {
        // Use html2canvas to render the card
        const isSquare = els.shareCard.classList.contains('format-square');
        const width = 1080;
        const height = isSquare ? 1080 : 1920;

        const canvas = await html2canvas(els.shareCard, {
            scale: 2, // 2x for high resolution
            useCORS: true,
            backgroundColor: null,
            logging: false,
            width: width,
            height: height,
            scrollX: 0,
            scrollY: -window.scrollY, // Corrects offset if page is scrolled
            onclone: (clonedDoc) => {
                const clonedCard = clonedDoc.getElementById('share-card-story');
                if (clonedCard) {
                    clonedCard.style.transform = 'none';
                    clonedCard.style.position = 'relative';
                    clonedCard.style.top = '0';
                    clonedCard.style.left = '0';
                    clonedCard.style.margin = '0';

                    // Reset container styles in clone to ensure absolute capture
                    const container = clonedCard.parentElement;
                    if (container) {
                        container.style.padding = '0';
                        container.style.margin = '0';
                        container.style.transform = 'none';
                        container.style.display = 'block';
                    }
                }
            }
        });

        // Convert canvas to image and trigger download
        const link = document.createElement('a');
        link.download = `sadaa-${isSquare ? 'post' : 'story'}-${Date.now()}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();

        btn.innerHTML = '<iconify-icon icon="mdi:check"></iconify-icon> Terminé !';
        setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }, 2000);

    } catch (error) {
        console.error('Erreur lors de la génération de l\'image:', error);
        alert('Désolé, une erreur est survenue lors de la génération de l\'image.');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}
