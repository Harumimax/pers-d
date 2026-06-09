import './bootstrap';

const initializeSiteHeader = () => {
    document.querySelectorAll('[data-site-header]').forEach((header) => {
        if (header.dataset.siteHeaderBound === 'true') {
            return;
        }

        const toggle = header.querySelector('[data-site-header-toggle]');
        const drawer = header.querySelector('[data-site-header-drawer]');

        if (!toggle || !drawer) {
            return;
        }

        const closeDrawer = () => {
            drawer.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        const openDrawer = () => {
            drawer.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        };

        toggle.addEventListener('click', () => {
            if (drawer.hidden) {
                openDrawer();
                return;
            }

            closeDrawer();
        });

        document.addEventListener('click', (event) => {
            if (!header.contains(event.target)) {
                closeDrawer();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDrawer();
            }
        });

        drawer.querySelectorAll('a, button').forEach((item) => {
            item.addEventListener('click', () => {
                closeDrawer();
            });
        });

        closeDrawer();
        header.dataset.siteHeaderBound = 'true';
    });
};

const initializeWordPronunciation = () => {
    const selector = '[data-pronounce-button]';
    const supportsSpeechSynthesis = typeof window !== 'undefined'
        && 'speechSynthesis' in window
        && 'SpeechSynthesisUtterance' in window;

    document.documentElement.classList.toggle('speech-synthesis-supported', supportsSpeechSynthesis);
    document.documentElement.classList.toggle('speech-synthesis-unsupported', !supportsSpeechSynthesis);

    let activeButton = null;
    let activeUtterance = null;

    const resetButtonState = (button) => {
        if (!(button instanceof HTMLElement)) {
            return;
        }

        button.classList.remove('is-speaking');

        const icon = button.querySelector('.word-list-pronounce-btn__icon');

        if (icon) {
            icon.textContent = '🔊';
        }
    };

    const syncButtons = () => {
        document.querySelectorAll(selector).forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            const canPronounce = supportsSpeechSynthesis
                && button.dataset.pronounceWord
                && button.dataset.pronounceLang;

            if (canPronounce) {
                button.hidden = false;
                button.removeAttribute('hidden');
            }

            button.classList.toggle('is-speech-ready', Boolean(canPronounce));

            if (!canPronounce) {
                resetButtonState(button);
            }
        });
    };

    if (document.body.dataset.wordPronunciationBound !== 'true') {
        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target.closest(selector) : null;

            if (!(target instanceof HTMLButtonElement)) {
                return;
            }

            event.preventDefault();

            if (!supportsSpeechSynthesis) {
                target.classList.remove('is-speech-ready');
                return;
            }

            const word = target.dataset.pronounceWord?.trim();
            const lang = target.dataset.pronounceLang?.trim();

            if (!word || !lang) {
                return;
            }

            if (activeButton === target && window.speechSynthesis.speaking) {
                window.speechSynthesis.cancel();
                resetButtonState(target);
                activeButton = null;
                activeUtterance = null;
                return;
            }

            if (window.speechSynthesis.speaking) {
                window.speechSynthesis.cancel();
            }

            if (activeButton && activeButton !== target) {
                resetButtonState(activeButton);
            }

            const utterance = new window.SpeechSynthesisUtterance(word);
            utterance.lang = lang;

            utterance.onstart = () => {
                activeButton = target;
                activeUtterance = utterance;
                target.classList.add('is-speaking');

                const icon = target.querySelector('.word-list-pronounce-btn__icon');

                if (icon) {
                    icon.textContent = '🔉';
                }
            };

            utterance.onend = () => {
                resetButtonState(target);

                if (activeButton === target) {
                    activeButton = null;
                    activeUtterance = null;
                }
            };

            utterance.onerror = () => {
                resetButtonState(target);

                if (activeButton === target) {
                    activeButton = null;
                    activeUtterance = null;
                }
            };

            window.speechSynthesis.speak(utterance);
        });

        const observer = new MutationObserver(() => {
            syncButtons();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['data-pronounce-word', 'data-pronounce-lang', 'hidden', 'class'],
        });

        document.addEventListener('livewire:init', () => {
            if (typeof window.Livewire?.hook !== 'function') {
                return;
            }

            window.Livewire.hook('morphed', () => {
                syncButtons();
            });

            window.Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    syncButtons();
                });
            });
        }, { once: true });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden && supportsSpeechSynthesis && window.speechSynthesis.speaking) {
                window.speechSynthesis.cancel();

                if (activeButton) {
                    resetButtonState(activeButton);
                    activeButton = null;
                    activeUtterance = null;
                }
            }
        });

        document.body.dataset.wordPronunciationBound = 'true';
    }

    if (supportsSpeechSynthesis && activeUtterance === null) {
        window.speechSynthesis.cancel();
    }

    syncButtons();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeSiteHeader();
        initializeWordPronunciation();
    }, { once: true });
} else {
    initializeSiteHeader();
    initializeWordPronunciation();
}
