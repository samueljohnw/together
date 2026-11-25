const pollIntervalMs = 5000;

/* ------------------------------------------------------------------ *
 * Shared helpers
 * ------------------------------------------------------------------ */

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

class RequestError extends Error {}

async function request(url, { method = 'GET', json, formData } = {}) {
    const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() };
    let body;

    if (json !== undefined) {
        headers['Content-Type'] = 'application/json';
        body = JSON.stringify(json);
    } else if (formData) {
        body = formData;
    }

    let response;

    try {
        response = await fetch(url, { method, headers, body, credentials: 'same-origin' });
    } catch {
        throw new RequestError('Could not reach the server. Check your connection and try again.');
    }

    if (response.status === 401 || response.status === 419) {
        window.location.href = '/login';
        throw new RequestError('Your session has expired. Please sign in again.');
    }

    if (response.status === 413) {
        throw new RequestError('That upload is larger than the server allows.');
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
        throw new RequestError(firstError ?? payload?.message ?? 'Something went wrong. Please try again.');
    }

    return payload;
}

function readJsonScript(selector) {
    const script = document.querySelector(selector);

    if (!script) {
        return null;
    }

    try {
        return JSON.parse(script.textContent);
    } catch {
        return null;
    }
}

function showError(element, message) {
    if (!element) {
        return;
    }

    element.textContent = message ?? '';
    element.hidden = !message;
}

function isSameDay(first, second) {
    return first.toDateString() === second.toDateString();
}

function shortDateLabel(isoDate) {
    return new Intl.DateTimeFormat([], { month: 'short', day: 'numeric' }).format(new Date(isoDate));
}

function dayLabel(isoDate) {
    const date = new Date(isoDate);
    const now = new Date();
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);

    if (isSameDay(date, now)) {
        return 'Today';
    }

    if (isSameDay(date, yesterday)) {
        return 'Yesterday';
    }

    if (now - date < 6 * 24 * 60 * 60 * 1000) {
        return new Intl.DateTimeFormat([], { weekday: 'long' }).format(date);
    }

    return shortDateLabel(isoDate);
}

function messageTimeLabel(isoDate) {
    const date = new Date(isoDate);
    const time = new Intl.DateTimeFormat([], { hour: 'numeric', minute: '2-digit' }).format(date);

    return isSameDay(date, new Date()) ? time : `${dayLabel(isoDate)} · ${time}`;
}

function formatFileSize(size) {
    if (size < 1024) {
        return `${size} B`;
    }

    const kilobytes = size / 1024;

    if (kilobytes < 1024) {
        return `${kilobytes.toFixed(1)} KB`;
    }

    return `${(kilobytes / 1024).toFixed(1)} MB`;
}

/* ------------------------------------------------------------------ *
 * Media viewer: images and videos open full size over the page
 * ------------------------------------------------------------------ */

const mediaViewer = document.querySelector('[data-media-viewer]');
const mediaViewerStage = document.querySelector('[data-media-viewer-stage]');
const mediaViewerTitle = document.querySelector('[data-media-viewer-title]');
const mediaViewerMeta = document.querySelector('[data-media-viewer-meta]');
const mediaViewerDownload = document.querySelector('[data-media-viewer-download]');
let mediaViewerTrigger = null;
let bodyWasLocked = false;

function openMediaViewer(attachment, trigger = null) {
    if (!mediaViewer || !mediaViewerStage) {
        window.open(attachment.preview_url, '_blank', 'noopener');
        return;
    }

    mediaViewerTrigger = trigger;
    mediaViewerTitle.textContent = attachment.name;
    mediaViewerMeta.textContent = `${attachment.type} · ${formatFileSize(attachment.size)}`;
    mediaViewerDownload.href = attachment.url;

    let media;

    if (attachment.media === 'video') {
        media = document.createElement('video');
        media.controls = true;
        media.autoplay = true;
        media.playsInline = true;
    } else {
        media = document.createElement('img');
        media.alt = attachment.name;
    }

    media.src = attachment.preview_url;
    media.className = 'max-h-full max-w-full rounded-lg object-contain shadow-2xl';
    mediaViewerStage.replaceChildren(media);

    bodyWasLocked = document.body.classList.contains('overflow-hidden');
    document.body.classList.add('overflow-hidden');
    mediaViewer.hidden = false;
    mediaViewer.classList.add('flex');
    mediaViewer.querySelector('[data-media-viewer-close]')?.focus();
}

function closeMediaViewer() {
    if (!mediaViewer || mediaViewer.hidden) {
        return;
    }

    mediaViewerStage.querySelector('video')?.pause();
    mediaViewerStage.replaceChildren();
    mediaViewer.hidden = true;
    mediaViewer.classList.remove('flex');

    if (!bodyWasLocked) {
        document.body.classList.remove('overflow-hidden');
    }

    if (mediaViewerTrigger?.isConnected) {
        mediaViewerTrigger.focus();
    }
}

mediaViewer?.querySelector('[data-media-viewer-close]')?.addEventListener('click', closeMediaViewer);
mediaViewer?.addEventListener('click', (event) => {
    if (event.target === mediaViewer || event.target === mediaViewerStage) {
        closeMediaViewer();
    }
});

// Capture phase, so Escape closes only the viewer and not the to-do or thread window underneath it.
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && mediaViewer && !mediaViewer.hidden) {
        event.stopImmediatePropagation();
        closeMediaViewer();
    }
}, true);

/* ------------------------------------------------------------------ *
 * Confirmation dialog
 * ------------------------------------------------------------------ */

const confirmDialog = document.querySelector('[data-confirm-dialog]');
let resolveConfirmDialog = null;
let confirmDialogTrigger = null;

/**
 * Ask the user to confirm an action. Resolves true when they confirm, false when they cancel.
 */
function confirmAction({ title, message, confirmLabel }) {
    if (!confirmDialog) {
        return Promise.resolve(window.confirm(title));
    }

    resolveConfirmDialog?.(false);
    confirmDialogTrigger = document.activeElement;
    confirmDialog.querySelector('[data-confirm-title]').textContent = title;
    confirmDialog.querySelector('[data-confirm-message]').textContent = message;
    confirmDialog.querySelector('[data-confirm-accept]').textContent = confirmLabel;
    confirmDialog.hidden = false;
    confirmDialog.classList.add('flex');
    confirmDialog.querySelector('[data-confirm-cancel]').focus();

    return new Promise((resolve) => {
        resolveConfirmDialog = resolve;
    });
}

function settleConfirmDialog(confirmed) {
    if (!confirmDialog || confirmDialog.hidden) {
        return;
    }

    confirmDialog.hidden = true;
    confirmDialog.classList.remove('flex');

    if (!confirmed && confirmDialogTrigger?.isConnected) {
        confirmDialogTrigger.focus();
    }

    const resolve = resolveConfirmDialog;
    resolveConfirmDialog = null;
    resolve?.(confirmed);
}

confirmDialog?.querySelector('[data-confirm-accept]')?.addEventListener('click', () => settleConfirmDialog(true));
confirmDialog?.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => settleConfirmDialog(false));
confirmDialog?.addEventListener('click', (event) => {
    if (event.target === confirmDialog) {
        settleConfirmDialog(false);
    }
});

// Capture phase, so Escape cancels only the dialog and leaves the window underneath open.
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && confirmDialog && !confirmDialog.hidden) {
        event.stopImmediatePropagation();
        settleConfirmDialog(false);
    }
}, true);

function buildMediaPreview(attachment, onLoad = null) {
    const wrapper = document.createElement('div');
    wrapper.className = 'mt-1.5 inline-block max-w-full text-left';

    const opener = document.createElement('button');
    opener.type = 'button';
    opener.className = 'relative block overflow-hidden rounded-xl border border-[#e7e9e2] bg-[#f7f8f5] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#819477]';
    opener.setAttribute('aria-label', attachment.media === 'video' ? `Play ${attachment.name}` : `View ${attachment.name}`);
    opener.addEventListener('click', () => openMediaViewer(attachment, opener));

    if (attachment.media === 'video') {
        const video = document.createElement('video');
        video.src = `${attachment.preview_url}#t=0.1`;
        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;
        video.className = 'pointer-events-none block max-h-72 min-h-[120px] min-w-[200px] max-w-full bg-[#252923] object-contain';

        if (onLoad) {
            video.addEventListener('loadedmetadata', onLoad, { once: true });
        }

        const playBadge = document.createElement('span');
        playBadge.className = 'absolute inset-0 flex items-center justify-center';
        playBadge.innerHTML = '<span class="flex h-12 w-12 items-center justify-center rounded-full bg-black/55 text-white shadow-lg"><svg class="ml-0.5 h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.52.85l10.4-6.5a1 1 0 0 0 0-1.7L9.52 4.65A1 1 0 0 0 8 5.5Z"/></svg></span>';

        opener.append(video, playBadge);
    } else {
        const image = document.createElement('img');
        image.src = attachment.preview_url;
        image.alt = attachment.name;
        image.loading = 'lazy';
        image.className = 'block max-h-72 max-w-full object-contain';

        if (onLoad) {
            image.addEventListener('load', onLoad, { once: true });
        }

        opener.append(image);
    }

    const download = document.createElement('a');
    download.href = attachment.url;
    download.className = 'mt-1 block truncate text-[11px] text-[#92988d] hover:text-[#53684d] hover:underline';
    download.textContent = `${attachment.name} · ${formatFileSize(attachment.size)} · Download`;

    wrapper.append(opener, download);

    return wrapper;
}

function renderChatMessages(container, messages, emptyText) {
    container.replaceChildren();

    if (messages.length === 0) {
        const emptyState = document.createElement('p');
        emptyState.className = 'py-8 text-center text-[13px] text-[#969b91]';
        emptyState.textContent = emptyText;
        container.append(emptyState);
        return;
    }

    messages.forEach((message) => {
        const isCurrentUser = message.is_mine;
        const row = document.createElement('article');
        row.className = `flex gap-2.5 ${isCurrentUser ? 'flex-row-reverse' : ''}`;

        const avatar = document.createElement('span');
        avatar.className = `mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[12px] font-semibold ${isCurrentUser ? 'bg-[#e5d1bf] text-[#715139]' : 'bg-[#e5d9e8] text-[#694e70]'}`;
        avatar.textContent = message.initials;

        const content = document.createElement('div');
        content.className = `max-w-[85%] ${isCurrentUser ? 'text-right' : ''}`;

        const meta = document.createElement('div');
        meta.className = `flex items-baseline gap-2 ${isCurrentUser ? 'justify-end' : ''}`;

        const author = document.createElement('span');
        author.className = 'text-[13px] font-semibold text-[#545a51]';
        author.textContent = message.author;

        const time = document.createElement('time');
        time.className = 'text-[12px] text-[#b0b5ab]';
        time.dateTime = message.created_at;
        time.textContent = messageTimeLabel(message.created_at);

        meta.append(author, time);

        if (message.attachment?.preview_url) {
            content.append(meta, buildMediaPreview(message.attachment, () => {
                container.scrollTop = container.scrollHeight;
            }));
        } else if (message.attachment) {
            const attachment = document.createElement('a');
            attachment.href = message.attachment.url;
            attachment.className = 'mt-1.5 flex items-center gap-3 rounded-xl border border-[#e7e9e2] bg-[#f7f8f5] px-3 py-2.5 text-left hover:bg-[#f1f4ee]';

            const fileIcon = document.createElement('span');
            fileIcon.className = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#71816a]';
            fileIcon.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.5h7l5 5v12H7a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 3.5v5h5M8.5 13h7m-7 3h7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';

            const fileInfo = document.createElement('span');
            fileInfo.className = 'min-w-0';

            const fileName = document.createElement('span');
            fileName.className = 'block truncate text-[13px] font-medium text-[#4f5c49]';
            fileName.textContent = message.attachment.name;

            const fileSize = document.createElement('span');
            fileSize.className = 'mt-0.5 block text-[11px] text-[#92988d]';
            fileSize.textContent = `${message.attachment.type || 'File'} · ${formatFileSize(message.attachment.size)}`;

            fileInfo.append(fileName, fileSize);
            attachment.append(fileIcon, fileInfo);
            content.append(meta, attachment);
        } else {
            const text = document.createElement('p');
            text.className = `mt-1.5 whitespace-pre-wrap rounded-xl px-3 py-2 text-left text-[14px] leading-[1.65] ${isCurrentUser ? 'bg-[#f1f4ee] text-[#5f6b58]' : 'bg-[#f7f8f5] text-[#656b62]'}`;
            text.textContent = message.body;
            content.append(meta, text);
        }

        row.append(avatar, content);
        container.append(row);
    });

    container.scrollTop = container.scrollHeight;
}

/* ------------------------------------------------------------------ *
 * Mobile accordions
 * ------------------------------------------------------------------ */

const mobileAccordionQuery = window.matchMedia('(max-width: 767px)');
const mobileAccordionToggles = document.querySelectorAll('[data-mobile-accordion-toggle]');

function syncMobileAccordions() {
    mobileAccordionToggles.forEach((toggle) => {
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        const chevron = toggle.querySelector('[data-mobile-accordion-chevron]');
        if (!panel) return;

        const isExpanded = !mobileAccordionQuery.matches;
        panel.classList.toggle('hidden', !isExpanded);
        toggle.setAttribute('aria-expanded', String(isExpanded));
        chevron?.classList.toggle('rotate-180', isExpanded && mobileAccordionQuery.matches);
    });
}

mobileAccordionToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
        if (!mobileAccordionQuery.matches) return;
        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        const chevron = toggle.querySelector('[data-mobile-accordion-chevron]');
        if (!panel) return;

        const isExpanded = panel.classList.toggle('hidden') === false;
        toggle.setAttribute('aria-expanded', String(isExpanded));
        chevron?.classList.toggle('rotate-180', isExpanded);
    });
});

syncMobileAccordions();
mobileAccordionQuery.addEventListener('change', syncMobileAccordions);

/* ------------------------------------------------------------------ *
 * Workspace: open to-dos and threads
 * ------------------------------------------------------------------ */

const initialItems = readJsonScript('[data-initial-items]');

if (initialItems) {
    initWorkspace(initialItems);
}

function initWorkspace(startingItems) {
    const threadList = document.querySelector('[data-thread-list]');
    const threadEmpty = document.querySelector('[data-thread-empty]');
    const todoList = document.querySelector('[data-todo-list]');
    const todoEmpty = document.querySelector('[data-todo-empty]');
    const workspaceSearch = document.querySelector('[data-workspace-search]');
    const workspaceSearchResults = document.querySelector('[data-search-results]');
    const workspaceSearchContainer = document.querySelector('[data-search-container]');
    const newTodoForm = document.querySelector('[data-new-todo-form]');
    const newThreadForm = document.querySelector('[data-new-thread-form]');

    const windows = {
        todo: {
            root: document.querySelector('[data-todo-detail]'),
            title: document.querySelector('[data-detail-title]'),
            messages: document.querySelector('[data-detail-messages]'),
            form: document.querySelector('[data-detail-form]'),
            fileInput: document.querySelector('[data-todo-file-input]'),
            attachButton: document.querySelector('[data-todo-attach]'),
            dismissButtons: document.querySelectorAll('[data-todo-close]'),
            closeItemButton: document.querySelector('[data-todo-close-item]'),
            error: document.querySelector('[data-detail-error]'),
            emptyText: 'No messages yet. Start the conversation about this to-do.',
        },
        thread: {
            root: document.querySelector('[data-thread-window]'),
            title: document.querySelector('[data-thread-title]'),
            messages: document.querySelector('[data-thread-messages]'),
            form: document.querySelector('[data-thread-form]'),
            fileInput: document.querySelector('[data-thread-file-input]'),
            attachButton: document.querySelector('[data-thread-attach]'),
            dismissButtons: document.querySelectorAll('[data-thread-close]'),
            closeItemButton: document.querySelector('[data-thread-close-item]'),
            error: document.querySelector('[data-thread-error]'),
            emptyText: 'Thread created. Send the first message to start the conversation.',
        },
    };

    let items = startingItems;
    let itemsSignature = JSON.stringify(items);
    let activeItemId = null;
    let activeKind = null;
    let activeMessagesSignature = null;
    let lastTrigger = null;
    let isSending = false;

    function buildRow(item) {
        const isThread = item.kind === 'thread';
        const row = document.createElement('article');
        row.dataset.itemId = String(item.id);
        row.setAttribute('role', isThread ? 'button' : 'listitem');
        row.setAttribute('tabindex', '0');
        row.setAttribute('aria-label', isThread ? `Open ${item.title} chat` : `Open ${item.title}`);
        row.className = isThread
            ? 'cursor-pointer px-4 py-4 transition hover:bg-[#f8faf6] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#819477]'
            : 'group flex cursor-pointer gap-3.5 bg-white px-5 py-5 transition hover:bg-[#f8faf6] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#819477] sm:px-6';

        const details = document.createElement('div');
        details.className = 'min-w-0 flex-1';

        const heading = document.createElement('h4');

        if (isThread) {
            heading.className = 'text-[14px] font-medium text-[#4f554d]';
            heading.textContent = item.title;
        } else {
            const title = document.createElement('span');
            title.className = 'text-[15px] font-medium leading-5 text-[#343a32]';
            title.textContent = item.title;
            heading.append(title);
        }

        const preview = document.createElement('p');
        preview.className = isThread
            ? 'mt-1.5 text-[13px] leading-relaxed text-[#858b81]'
            : 'mt-1 line-clamp-2 text-[13px] leading-relaxed text-[#858b81]';
        preview.textContent = item.preview ?? (isThread ? 'Start the conversation…' : 'No replies yet');

        details.append(heading, preview);

        if (item.last_response_at) {
            const footer = document.createElement('div');
            footer.className = 'mt-3 flex justify-end';

            const label = document.createElement('span');
            label.className = 'mr-1.5 rounded-full bg-[#f3f4f0] px-2 py-0.5 text-[10px] font-medium text-[#92988d]';
            label.textContent = 'Last response';

            const date = document.createElement('time');
            date.className = 'text-[12px] text-[#a0a49b]';
            date.dateTime = item.last_response_at;
            date.textContent = shortDateLabel(item.last_response_at);

            footer.append(label, date);
            details.append(footer);
        }

        row.append(details);
        row.addEventListener('click', () => openItem(item.id, row));
        row.addEventListener('keydown', (event) => {
            if (event.target === row && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                openItem(item.id, row);
            }
        });

        return row;
    }

    function renderLists() {
        const lists = { thread: [threadList, threadEmpty], todo: [todoList, todoEmpty] };

        Object.entries(lists).forEach(([kind, [list, empty]]) => {
            if (!list) {
                return;
            }

            const rows = items.filter((item) => item.kind === kind).map(buildRow);
            list.replaceChildren(...rows, ...(empty ? [empty] : []));

            if (empty) {
                empty.hidden = rows.length > 0;
            }
        });

        renderWorkspaceSearch(workspaceSearch?.value ?? '');
    }

    function setItems(nextItems) {
        const nextSignature = JSON.stringify(nextItems);

        if (nextSignature === itemsSignature) {
            return;
        }

        items = nextItems;
        itemsSignature = nextSignature;
        renderLists();
    }

    async function refreshItems() {
        try {
            const { data } = await request('/items');
            setItems(data);
        } catch {
            // Polling failures are silent; the next poll tries again.
        }
    }

    function renderActiveMessages(item) {
        const signature = JSON.stringify(item.messages.map((message) => message.id));

        if (signature === activeMessagesSignature) {
            return;
        }

        activeMessagesSignature = signature;
        const view = windows[item.kind];
        renderChatMessages(view.messages, item.messages, view.emptyText);
    }

    async function openItem(id, trigger = null) {
        const summary = items.find((item) => item.id === id);

        if (!summary) {
            return;
        }

        if (activeItemId !== null) {
            dismissWindow({ restoreFocus: false });
        }

        const view = windows[summary.kind];

        if (!view.root) {
            return;
        }

        activeItemId = id;
        activeKind = summary.kind;
        activeMessagesSignature = null;
        lastTrigger = trigger;
        view.title.textContent = summary.title;
        view.messages.replaceChildren();
        showError(view.error, null);
        view.root.hidden = false;
        view.root.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        view.form?.querySelector('input[name="message"]')?.focus();

        await refreshActiveItem();
    }

    async function refreshActiveItem() {
        if (activeItemId === null) {
            return;
        }

        const id = activeItemId;

        try {
            const { data } = await request(`/items/${id}`);

            if (activeItemId !== id) {
                return;
            }

            if (data.closed_at) {
                dismissWindow();
                await refreshItems();
                return;
            }

            renderActiveMessages(data);
        } catch (error) {
            if (activeItemId === id && activeMessagesSignature === null) {
                showError(windows[activeKind].error, error.message);
            }
        }
    }

    function dismissWindow({ restoreFocus = true } = {}) {
        if (activeKind === null) {
            return;
        }

        const view = windows[activeKind];
        view.root.hidden = true;
        view.root.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        activeItemId = null;
        activeKind = null;
        activeMessagesSignature = null;

        if (restoreFocus && lastTrigger?.isConnected) {
            lastTrigger.focus();
        }
    }

    async function sendToActiveItem({ body, files }) {
        if (activeItemId === null || isSending) {
            return false;
        }

        const view = windows[activeKind];
        const formData = new FormData();

        if (body) {
            formData.append('body', body);
        }

        Array.from(files ?? []).forEach((file) => formData.append('attachments[]', file));

        isSending = true;
        showError(view.error, null);

        try {
            await request(`/items/${activeItemId}/messages`, { method: 'POST', formData });
            await Promise.all([refreshActiveItem(), refreshItems()]);
            return true;
        } catch (error) {
            showError(view.error, error.message);
            return false;
        } finally {
            isSending = false;
        }
    }

    async function closeActiveItem() {
        if (activeItemId === null) {
            return;
        }

        const id = activeItemId;
        const view = windows[activeKind];
        const isThread = activeKind === 'thread';
        const confirmed = await confirmAction({
            title: isThread ? 'Close this thread?' : 'Close this to-do?',
            message: `“${view.title.textContent}” will move to Closed Items on your Account page. You can re-open it from there at any time.`,
            confirmLabel: isThread ? 'Close thread' : 'Close to-do',
        });

        if (!confirmed || activeItemId !== id) {
            return;
        }

        try {
            await request(`/closed-items/${id}`, { method: 'POST' });
            dismissWindow({ restoreFocus: false });
            setItems(items.filter((item) => item.id !== id));
        } catch (error) {
            showError(view.error, error.message);
        }
    }

    Object.values(windows).forEach((view) => {
        if (!view.root) {
            return;
        }

        view.dismissButtons.forEach((button) => button.addEventListener('click', () => dismissWindow()));
        view.closeItemButton?.addEventListener('click', closeActiveItem);

        view.root.addEventListener('click', (event) => {
            if (event.target === view.root) {
                dismissWindow();
            }
        });

        view.form?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const input = view.form.querySelector('input[name="message"]');
            const body = input?.value.trim();

            if (!body) {
                return;
            }

            if (await sendToActiveItem({ body })) {
                input.value = '';
                input.focus();
            }
        });

        view.attachButton?.addEventListener('click', () => view.fileInput?.click());

        view.fileInput?.addEventListener('change', async () => {
            if (!view.fileInput.files?.length) {
                return;
            }

            await sendToActiveItem({ files: view.fileInput.files });
            view.fileInput.value = '';
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeKind !== null) {
            dismissWindow();
        }
    });

    async function createItem(form, kind) {
        const input = form.querySelector('input[name="title"]');
        const title = input?.value.trim();

        if (!title) {
            return;
        }

        try {
            const { data } = await request('/items', { method: 'POST', json: { kind, title } });
            form.reset();
            setItems([data, ...items.filter((item) => item.id !== data.id)]);

            if (kind === 'thread') {
                await openItem(data.id, threadList?.querySelector(`[data-item-id="${data.id}"]`));
            }
        } catch (error) {
            input.setCustomValidity(error.message);
            input.reportValidity();
            input.addEventListener('input', () => input.setCustomValidity(''), { once: true });
        }
    }

    newTodoForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        void createItem(newTodoForm, 'todo');
    });

    newThreadForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        void createItem(newThreadForm, 'thread');
    });

    function renderWorkspaceSearch(rawQuery) {
        if (!workspaceSearchResults) {
            return;
        }

        const query = rawQuery.trim().toLocaleLowerCase();

        if (!query) {
            workspaceSearchResults.hidden = true;
            workspaceSearchResults.replaceChildren();
            return;
        }

        const matches = items.filter((item) => `${item.title} ${item.preview ?? ''}`.toLocaleLowerCase().includes(query));

        workspaceSearchResults.replaceChildren();

        if (matches.length === 0) {
            const emptyState = document.createElement('p');
            emptyState.className = 'px-3 py-4 text-[13px] text-[#858b80]';
            emptyState.textContent = 'No matching conversations or to-dos.';
            workspaceSearchResults.append(emptyState);
            workspaceSearchResults.hidden = false;
            return;
        }

        matches.forEach((item) => {
            const kind = item.kind === 'thread' ? 'Conversation' : 'To-do';
            const badgeStyles = kind === 'To-do' ? 'bg-[#edf3e9] text-[#657a5b]' : 'bg-[#f1f3ee] text-[#788372]';

            const result = document.createElement('button');
            result.type = 'button';
            result.className = 'flex w-full items-start justify-between gap-4 rounded-lg px-3 py-2.5 text-left transition hover:bg-[#f7f8f5] focus:bg-[#f7f8f5] focus:outline-none';

            const text = document.createElement('span');
            text.className = 'min-w-0';

            const resultTitle = document.createElement('span');
            resultTitle.className = 'block truncate text-[14px] font-medium text-[#343a32]';
            resultTitle.textContent = item.title;

            const resultPreview = document.createElement('span');
            resultPreview.className = 'mt-0.5 block truncate text-[12px] text-[#92988d]';
            resultPreview.textContent = item.preview ?? '';

            const kindBadge = document.createElement('span');
            kindBadge.className = `shrink-0 rounded-md px-2 py-1 text-[11px] font-medium ${badgeStyles}`;
            kindBadge.textContent = kind;

            text.append(resultTitle, resultPreview);
            result.append(text, kindBadge);
            result.addEventListener('click', () => {
                workspaceSearchResults.hidden = true;
                openItem(item.id, document.querySelector(`[data-item-id="${item.id}"]`));
            });

            workspaceSearchResults.append(result);
        });

        workspaceSearchResults.hidden = false;
    }

    workspaceSearch?.addEventListener('input', () => renderWorkspaceSearch(workspaceSearch.value));
    workspaceSearch?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            workspaceSearchResults?.querySelector('button')?.click();
        }

        if (event.key === 'Escape' && workspaceSearchResults) {
            workspaceSearchResults.hidden = true;
        }
    });

    document.addEventListener('click', (event) => {
        if (workspaceSearchContainer && !workspaceSearchContainer.contains(event.target) && workspaceSearchResults) {
            workspaceSearchResults.hidden = true;
        }
    });

    async function poll() {
        if (document.hidden) {
            return;
        }

        await Promise.all([refreshItems(), refreshActiveItem()]);
    }

    renderLists();
    window.setInterval(poll, pollIntervalMs);
    document.addEventListener('visibilitychange', poll);
}

/* ------------------------------------------------------------------ *
 * Account: closed items
 * ------------------------------------------------------------------ */

const initialClosedItems = readJsonScript('[data-initial-closed-items]');

if (initialClosedItems) {
    initClosedItems(initialClosedItems);
}

function initClosedItems(closedItems) {
    const closedList = document.querySelector('[data-closed-list]');
    const closedItemsSearch = document.querySelector('[data-closed-items-search]');
    const closedItemsEmpty = document.querySelector('[data-closed-empty]');
    const closedItemsCount = document.querySelector('[data-closed-count]');
    const closedDetail = document.querySelector('[data-closed-detail]');
    const closedDetailKind = document.querySelector('[data-closed-detail-kind]');
    const closedDetailTitle = document.querySelector('[data-closed-detail-title]');
    const closedDetailAssignee = document.querySelector('[data-closed-detail-assignee]');
    const closedDetailClose = document.querySelector('[data-closed-detail-close]');
    const closedReopen = document.querySelector('[data-closed-reopen]');
    const closedError = document.querySelector('[data-closed-error]');
    const closedDetailHistoryLabel = document.querySelector('[data-closed-detail-history-label]');
    const closedDetailHistory = document.querySelector('[data-closed-detail-history]');

    let activeClosedItem = null;
    let activeClosedCard = null;

    function closedMeta(item) {
        return `Closed by ${item.closed_by ?? 'a former member'} · ${dayLabel(item.closed_at)}`;
    }

    function buildCard(item) {
        const card = document.createElement('article');
        card.dataset.closedItem = String(item.id);
        card.className = 'flex cursor-pointer items-start gap-3.5 px-5 py-4 transition hover:bg-[#f7f8f5] focus:bg-[#f7f8f5] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#819477] sm:px-6';
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');
        card.setAttribute('aria-label', `Open ${item.title}`);

        const check = document.createElement('span');
        check.className = 'mt-0.5 flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full bg-[#e4ecdf] text-[#66805d]';
        check.innerHTML = '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        const details = document.createElement('div');
        details.className = 'min-w-0 flex-1';

        const title = document.createElement('h3');
        title.className = 'text-[14px] font-medium text-[#51584e]';
        title.textContent = item.title;

        const meta = document.createElement('p');
        meta.className = 'mt-1 text-[12px] text-[#a0a49b]';
        meta.textContent = `${item.kind === 'thread' ? 'Thread' : 'To-do'} · ${closedMeta(item)}`;

        details.append(title, meta);
        card.append(check, details);

        card.addEventListener('click', () => openClosedItem(item, card));
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openClosedItem(item, card);
            }
        });

        return card;
    }

    function updateClosedItemsView() {
        const query = closedItemsSearch?.value.trim().toLocaleLowerCase() ?? '';
        const visible = closedItems.filter((item) => `${item.title} ${item.preview ?? ''}`.toLocaleLowerCase().includes(query));

        closedList?.replaceChildren(...visible.map(buildCard), ...(closedItemsEmpty ? [closedItemsEmpty] : []));

        if (closedItemsEmpty) {
            closedItemsEmpty.hidden = visible.length > 0;
            closedItemsEmpty.textContent = closedItems.length === 0 ? 'No closed items yet.' : 'No closed items match your search.';
        }

        if (closedItemsCount) {
            closedItemsCount.textContent = String(visible.length);
        }
    }

    async function openClosedItem(item, card) {
        activeClosedItem = item;
        activeClosedCard = card;

        if (closedDetailKind) closedDetailKind.textContent = item.kind === 'thread' ? 'Thread' : 'To-do';
        if (closedDetailTitle) closedDetailTitle.textContent = item.title;
        if (closedDetailAssignee) closedDetailAssignee.textContent = closedMeta(item);
        if (closedDetailHistoryLabel) closedDetailHistoryLabel.textContent = item.kind === 'thread' ? 'Full thread' : 'To-do chat';
        showError(closedError, null);
        closedDetailHistory?.replaceChildren();

        if (closedDetail) {
            closedDetail.hidden = false;
            closedDetail.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        closedDetailClose?.focus();

        try {
            const { data } = await request(`/items/${item.id}`);

            if (activeClosedItem?.id === item.id) {
                renderClosedItemHistory(data.messages);
            }
        } catch (error) {
            showError(closedError, error.message);
        }
    }

    function renderClosedItemHistory(messages) {
        if (!closedDetailHistory) return;
        closedDetailHistory.replaceChildren();

        if (messages.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'rounded-xl bg-[#f7f8f5] px-4 py-3 text-[13px] leading-relaxed text-[#747b71]';
            empty.textContent = 'No conversation history for this item.';
            closedDetailHistory.append(empty);
            return;
        }

        messages.forEach((message) => {
            const entry = document.createElement('article');
            entry.className = 'rounded-xl bg-[#f7f8f5] px-4 py-3';

            const header = document.createElement('div');
            header.className = 'flex items-baseline justify-between gap-3';

            const author = document.createElement('span');
            author.className = 'text-[12px] font-semibold text-[#545a51]';
            author.textContent = message.author;

            const time = document.createElement('time');
            time.className = 'text-right text-[11px] text-[#a0a49b]';
            time.dateTime = message.created_at;
            time.textContent = messageTimeLabel(message.created_at);

            header.append(author, time);
            entry.append(header);

            if (message.attachment?.preview_url) {
                entry.append(buildMediaPreview(message.attachment));
            } else if (message.attachment) {
                const link = document.createElement('a');
                link.href = message.attachment.url;
                link.className = 'mt-1.5 block text-[13px] font-medium text-[#53684d] underline-offset-2 hover:underline';
                link.textContent = `${message.attachment.name} (${formatFileSize(message.attachment.size)})`;
                entry.append(link);
            } else {
                const body = document.createElement('p');
                body.className = 'mt-1.5 whitespace-pre-wrap text-[13px] leading-relaxed text-[#656b62]';
                body.textContent = message.body;
                entry.append(body);
            }

            closedDetailHistory.append(entry);
        });
    }

    function closeClosedItem() {
        if (!closedDetail) return;
        closedDetail.hidden = true;
        closedDetail.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        activeClosedCard?.focus();
        activeClosedItem = null;
    }

    closedDetailClose?.addEventListener('click', closeClosedItem);
    closedDetail?.addEventListener('click', (event) => {
        if (event.target === closedDetail) closeClosedItem();
    });

    closedReopen?.addEventListener('click', async () => {
        if (!activeClosedItem) return;

        const item = activeClosedItem;
        closedReopen.disabled = true;

        try {
            await request(`/closed-items/${item.id}`, { method: 'DELETE' });
            window.location.href = item.kind === 'thread' ? '/#threads' : '/#open-items';
        } catch (error) {
            showError(closedError, error.message);
            closedReopen.disabled = false;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && closedDetail && !closedDetail.hidden) closeClosedItem();
    });

    closedItemsSearch?.addEventListener('input', updateClosedItemsView);

    updateClosedItemsView();
}

/* ------------------------------------------------------------------ *
 * Account: tabs
 * ------------------------------------------------------------------ */

const accountTabs = document.querySelectorAll('[data-account-tab]');
const accountPanels = document.querySelectorAll('[data-account-panel]');
const closedSearchContainer = document.querySelector('[data-closed-search-container]');

accountTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        const activePanel = tab.dataset.accountTab;

        accountTabs.forEach((accountTab) => {
            const isActive = accountTab === tab;
            accountTab.setAttribute('aria-selected', String(isActive));
            accountTab.classList.toggle('border-[#6f8f62]', isActive);
            accountTab.classList.toggle('text-[#53684d]', isActive);
            accountTab.classList.toggle('border-transparent', !isActive);
            accountTab.classList.toggle('text-[#92988d]', !isActive);
        });

        accountPanels.forEach((panel) => {
            panel.hidden = panel.dataset.accountPanel !== activePanel;
        });

        if (closedSearchContainer) {
            closedSearchContainer.hidden = activePanel !== 'closed-items';
        }
    });
});

/* ------------------------------------------------------------------ *
 * Push notifications
 * ------------------------------------------------------------------ */

const pushToggle = document.querySelector('[data-push-toggle]');
const pushStatus = document.querySelector('[data-push-status]');

function pushHeaders() {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
    };
}

function decodeApplicationServerKey(encodedKey) {
    const padding = '='.repeat((4 - (encodedKey.length % 4)) % 4);
    const base64 = (encodedKey + padding).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
}

async function updatePushControlState() {
    if (!pushToggle || !('serviceWorker' in navigator)) return;

    try {
        const registration = await navigator.serviceWorker.getRegistration('/');
        const subscription = await registration?.pushManager?.getSubscription();
        pushToggle.textContent = subscription ? 'Turn off notifications' : 'Enable notifications';
        if (subscription && pushStatus) {
            pushStatus.textContent = 'Notifications are enabled for this device.';
        }
    } catch {
        return;
    }
}

pushToggle?.addEventListener('click', async () => {
    if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        if (pushStatus) pushStatus.textContent = 'Push notifications are not supported by this browser.';
        return;
    }

    const applicationServerKey = document.querySelector('meta[name="push-public-key"]')?.content;
    if (!applicationServerKey) {
        if (pushStatus) pushStatus.textContent = 'Add VAPID keys to the server before enabling notifications.';
        return;
    }

    try {
        let permission = Notification.permission;
        if (permission === 'default') {
            permission = await Notification.requestPermission();
        }
        if (permission !== 'granted') {
            if (pushStatus) pushStatus.textContent = 'Allow notifications in your browser to receive alerts.';
            return;
        }

        const registration = await navigator.serviceWorker.register('/sw.js');
        const existingSubscription = await registration.pushManager.getSubscription();

        if (existingSubscription) {
            const response = await fetch('/push/subscriptions', {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: pushHeaders(),
                body: JSON.stringify({ endpoint: existingSubscription.endpoint }),
            });

            if (!response.ok) throw new Error('The subscription could not be removed.');
            await existingSubscription.unsubscribe();
            pushToggle.textContent = 'Enable notifications';
            if (pushStatus) pushStatus.textContent = 'Notifications are off for this device.';
            return;
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: decodeApplicationServerKey(applicationServerKey),
        });
        const response = await fetch('/push/subscriptions', {
            method: 'POST',
            credentials: 'same-origin',
            headers: pushHeaders(),
            body: JSON.stringify({ subscription: subscription.toJSON() }),
        });

        if (!response.ok) throw new Error('The subscription could not be saved.');
        pushToggle.textContent = 'Turn off notifications';
        if (pushStatus) pushStatus.textContent = 'Notifications are enabled for this device.';
    } catch {
        if (pushStatus) pushStatus.textContent = 'Could not set up notifications. Check the site connection and try again.';
    }
});

void updatePushControlState();
