import Swiper from 'swiper';
import { A11y, Autoplay, Keyboard, Pagination } from 'swiper/modules';
import AOS from 'aos';
import TomSelect from 'tom-select';
import 'trix';

let aosInitialized = false;

const livewireProperty = (select) => {
    if (select.dataset.livewireProperty) return select.dataset.livewireProperty;
    return Array.from(select.attributes).find((attribute) => attribute.name.startsWith('wire:model'))?.value;
};

const initTomSelect = (root = document) => {
    root.querySelectorAll('[data-tom-select]').forEach((select) => {
        if (select.tomselect) return;
        const property = livewireProperty(select);
        const plugins = select.multiple
            ? { remove_button: { title: 'Hapus' } }
            : (select.querySelector('option[value=""]') ? { clear_button: { title: 'Kosongkan pilihan' } } : {});

        new TomSelect(select, {
            plugins,
            create: false,
            maxOptions: null,
            hideSelected: select.multiple,
            closeAfterSelect: !select.multiple,
            placeholder: select.dataset.placeholder ?? select.getAttribute('aria-label') ?? 'Pilih opsi',
            onChange(value) {
                if (!property) return;
                const componentRoot = select.closest('[wire\\:id]');
                if (!componentRoot) return;
                window.Livewire.find(componentRoot.getAttribute('wire:id')).set(property, select.multiple ? value : (value ?? ''));
            },
        });
    });
};

const setImageUploadStatus = (editor, message = '', failed = false) => {
    const status = editor.parentElement?.querySelector('[data-image-upload-status]');
    if (!status) return;
    status.textContent = message;
    status.classList.toggle('hidden', !message);
    status.classList.toggle('text-red-600', failed);
    status.classList.toggle('text-muted', !failed);
};

const uploadTrixAttachment = async (editor, attachment) => {
    const formData = new FormData();
    formData.append('image', attachment.file);
    setImageUploadStatus(editor, 'Mengunggah gambar...');

    try {
        const response = await fetch(editor.dataset.imageUploadUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: formData,
        });
        if (!response.ok) throw new Error('Gambar gagal diunggah. Periksa format dan ukurannya.');
        const uploaded = await response.json();
        attachment.setAttributes({ url: uploaded.url, href: uploaded.href });
        attachment.setUploadProgress(100);
        setImageUploadStatus(editor, 'Gambar berhasil disisipkan.');
        setTimeout(() => setImageUploadStatus(editor), 2200);
    } catch (error) {
        attachment.remove();
        setImageUploadStatus(editor, error.message || 'Gambar gagal diunggah.', true);
    }
};

const initCarousels = (root = document) => {
    root.querySelectorAll('.breaking-swiper').forEach((carousel) => {
        if (carousel.swiper) return;
        const slideCount = carousel.querySelectorAll('.swiper-slide').length;

        new Swiper(carousel, {
            modules: [A11y, Autoplay, Keyboard],
            direction: 'vertical',
            loop: slideCount > 1,
            allowTouchMove: slideCount > 1,
            keyboard: { enabled: true, onlyInViewport: true },
            autoplay: slideCount > 1 ? { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
            observer: true,
            observeParents: true,
            a11y: { enabled: true },
        });
    });

    root.querySelectorAll('.hero-swiper').forEach((carousel) => {
        if (carousel.swiper) return;
        const slideCount = carousel.querySelectorAll('.swiper-slide').length;

        new Swiper(carousel, {
            modules: [A11y, Autoplay, Keyboard, Pagination],
            loop: slideCount > 1,
            allowTouchMove: slideCount > 1,
            keyboard: { enabled: true, onlyInViewport: true },
            autoplay: slideCount > 1 ? { delay: 5000, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
            pagination: { el: carousel.querySelector('.swiper-pagination'), clickable: true },
            observer: true,
            observeParents: true,
            a11y: { enabled: true },
        });
    });
};

const initPublicAnimations = (root = document) => {
    const publicShell = root.querySelector?.('[data-public-shell]') ?? (root.matches?.('[data-public-shell]') ? root : null);
    if (!publicShell) return;

    publicShell.querySelectorAll('#main-content > *, #main-content .section-heading, #main-content .grid > article, #main-content figure, #main-content .article-body').forEach((element) => {
        if (!element.hasAttribute('data-aos')) element.dataset.aos = 'fade-up';
    });

    publicShell.querySelectorAll('[data-aos]').forEach((element, index) => {
        if (!element.dataset.aosDelay && element.hasAttribute('data-aos-stagger')) {
            element.dataset.aosDelay = String(Math.min(index % 4, 3) * 70);
        }
    });

    try {
        if (!aosInitialized) {
            AOS.init({
                duration: 620,
                easing: 'ease-out-cubic',
                once: true,
                offset: 48,
                anchorPlacement: 'top-bottom',
                disable: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            });
            aosInitialized = true;
        }

        AOS.refreshHard();
    } catch (error) {
        publicShell.querySelectorAll('[data-aos]').forEach((element) => {
            element.classList.add('aos-animate');
        });
        console.error('AOS gagal diinisialisasi. Konten tetap ditampilkan.', error);
    }
};

const initUi = () => {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        if (button.dataset.ready) return;
        button.dataset.ready = 'true';
        button.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        });
    });
    document.querySelectorAll('[data-copy-url]').forEach((button) => {
        if (button.dataset.ready) return;
        button.dataset.ready = 'true';
        button.addEventListener('click', async () => {
            await navigator.clipboard.writeText(button.dataset.copyUrl);
            const original = button.innerHTML;
            button.textContent = 'Tautan disalin';
            setTimeout(() => { button.innerHTML = original; }, 1800);
        });
    });
    initCarousels();
    document.querySelectorAll('[data-carousel-toggle]').forEach((button) => {
        if (button.dataset.ready) return;
        button.dataset.ready = 'true';
        button.addEventListener('click', () => {
            const carousel = document.querySelector(`.${button.dataset.carouselToggle}-swiper`);
            if (!carousel?.swiper?.autoplay) return;
            const paused = button.getAttribute('aria-pressed') === 'true';
            if (paused) carousel.swiper.autoplay.start(); else carousel.swiper.autoplay.stop();
            button.setAttribute('aria-pressed', paused ? 'false' : 'true');
            button.setAttribute('aria-label', `${paused ? 'Jeda' : 'Putar'} carousel ${button.dataset.carouselToggle === 'breaking' ? 'Radar Terkini' : 'pilihan'}`);
            button.querySelector('i')?.classList.toggle('fa-pause', paused);
            button.querySelector('i')?.classList.toggle('fa-play', !paused);
        });
    });
    initTomSelect();
    document.querySelectorAll('[data-rich-text]').forEach((editor) => {
        if (editor.dataset.ready) return;
        editor.dataset.ready = 'true';
        editor.addEventListener('trix-change', () => {
            const root = editor.closest('[wire\\:id]');
            if (root) window.Livewire.find(root.getAttribute('wire:id')).set(editor.dataset.livewireProperty, editor.value, false);
        });
        editor.addEventListener('trix-file-accept', (event) => {
            const file = event.file;
            const allowed = editor.dataset.imageUploadUrl
                && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
                && file.size <= 5 * 1024 * 1024;
            if (!allowed) {
                event.preventDefault();
                setImageUploadStatus(editor, editor.dataset.imageUploadUrl ? 'Gunakan gambar JPG, PNG, atau WebP maksimal 5 MB.' : 'Lampiran file tidak diizinkan pada editor ini.', true);
            }
        });
        editor.addEventListener('trix-attachment-add', (event) => {
            if (event.attachment.file && editor.dataset.imageUploadUrl) uploadTrixAttachment(editor, event.attachment);
        });
    });
    initPublicAnimations();
};

document.addEventListener('DOMContentLoaded', initUi);
document.addEventListener('livewire:navigated', initUi);
document.addEventListener('livewire:initialized', initUi);
document.addEventListener('tom-select:set', (event) => {
    document.querySelectorAll('[data-tom-select]').forEach((select) => {
        if (livewireProperty(select) !== event.detail.property) return;
        select.tomselect?.setValue(event.detail.value, true);
    });
});
document.addEventListener('rich-text:set', (event) => {
    document.querySelectorAll('[data-rich-text]').forEach((editor) => {
        if (editor.dataset.livewireProperty === event.detail.property) editor.editor.loadHTML(event.detail.value ?? '');
    });
});
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('commit', ({ succeed }) => {
        succeed(() => queueMicrotask(() => {
            initTomSelect();
            initPublicAnimations();
        }));
    });
});
