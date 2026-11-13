// Alpine component of the calendar card (calender-area.blade.php): the day modal,
// the details of each document and the downloads, which run in the background.
//
// Config from the view: { urls: { labels, di, diExcel, details }, texts, locale }
// URLs carry "__DATE__" in place of the day.

// :name, :date, :file and :count are filled in by fill()
const fill = (text, values) => text.replace(/:(\w+)/g, (match, key) => values[key] ?? match);

const DATE_LOCALES = { es: 'es-MX', en: 'en-US', ja: 'ja-JP' };

export function calendarDocuments({ urls, texts, locale }) {
    const dateLocale = DATE_LOCALES[locale] || 'es-MX';
    const at = (date) => new Date(`${date}T12:00:00`);

    // What each download asks for and what the server must send back
    const DOWNLOADS = {
        labels: { url: urls.labels, name: texts.names.labels, type: 'pdf' },
        di: { url: urls.di, name: texts.names.di, type: 'pdf' },
        'di-excel': { url: urls.diExcel, name: texts.names.diExcel, type: 'spreadsheetml' },
    };

    let toastSeq = 0;

    return {
        texts,
        open: false,
        documentsDown: false,
        day: { date: '', labels: 0, di: 0, past: false, workday: null },
        // Details view: 'labels' | 'di' | null, and what the server sent for the day
        view: null,
        details: null,
        detailsDate: null,
        // The version being looked at in the details view, or null for the current document
        detailsVersion: null,
        loadingDetails: false,
        detailsError: false,
        search: '',
        versions: {},
        versionsDate: null,
        versionsLoaded: false,
        lineSearch: '',
        // Downloads being prepared, by "kind-date"; a second click on the same one is ignored
        pending: {},
        toasts: [],

        get weekday() {
            return this.day.date ? at(this.day.date).toLocaleDateString(dateLocale, { weekday: 'long' }) : '';
        },
        get longDate() {
            return this.day.date ? at(this.day.date).toLocaleDateString(dateLocale, { day: 'numeric', month: 'long', year: 'numeric' }) : '';
        },

        openDay(detail) {
            this.day = { labels: 0, di: 0, past: false, workday: null, ...detail };
            this.view = null;
            this.versions = {};
            this.versionsDate = null;
            this.versionsLoaded = false;
            this.open = true;
            this.$nextTick(() => this.$refs.closeButton?.focus());
        },
        close() {
            this.open = false;
        },

        downloadText(download) {
            const date = new Date(download.at).toLocaleString(dateLocale, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
            return fill(download.changed ? texts.changedAfter : texts.downloadedBy, { date, name: download.by });
        },

        // Loads the day once and keeps it while the modal shows that same day
        // With a version (from the list of versions) it shows the document as it was then
        async showDetails(kind, version = null) {
            this.view = kind;
            this.detailsVersion = version;
            this.search = '';
            this.lineSearch = '';
            const date = this.day.date;
            const key = `${date}|${version?.snapshot ?? 'current'}`;
            if (this.detailsDate === key && this.details) return;

            this.details = null;
            this.detailsError = false;
            this.loadingDetails = true;
            try {
                const url = version ? urls.versionDetails.replace('__DATE__', date).replace('__SNAP__', version.snapshot) : urls.details.replace('__DATE__', date);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const details = await response.json();
                // Another day may have been opened meanwhile
                if (this.day.date !== date) return;
                this.details = details;
                this.detailsDate = key;
            } catch (e) {
                if (this.day.date === date) this.detailsError = true;
            } finally {
                if (this.day.date === date) this.loadingDetails = false;
            }
        },
        hideDetails() {
            this.view = null;
        },

        // The search looks at the whole row: part, description, date, origin, order, quantity and boxes.
        // A part that matches keeps all its rows; otherwise only the rows that match are left.
        get filteredParts() {
            const parts = this.details?.di || [];
            const query = this.search.trim().toLowerCase();
            if (!query) return parts;

            const matches = (...values) => values.some((value) => String(value ?? '').toLowerCase().includes(query));

            return parts.flatMap((part) => {
                if (matches(part.part, part.description)) return [part];
                const rows = part.rows.filter((row) => matches(
                    row.date, this.shortDate(row.date), texts.origins[row.origin], row.order,
                    row.quantity, this.num(row.quantity), row.boxes,
                ));
                return rows.length ? [{ ...part, rows }] : [];
            });
        },
        get filteredLines() {
            return this.rowsMatching(this.details?.labels, this.lineSearch, ['part', 'description', 'order', 'shift', 'warehouse', 'quantity', 'pending']);
        },
        rowsMatching(rows, search, keys) {
            const query = search.trim().toLowerCase();
            if (!query) return rows || [];
            return (rows || []).filter((row) => keys.some((key) => String(row[key] ?? '').toLowerCase().includes(query)));
        },
        num(value) {
            return Number(value || 0).toLocaleString(dateLocale);
        },
        sum(rows, key) {
            // Rows marked as removed (version compared to the previous one) are not part of the totals
            return (rows || []).reduce((total, row) => total + (row.change === 'removed' ? 0 : Number(row[key] || 0)), 0);
        },
        // Row colour and tag of what changed against the previous version
        changeTone(change) {
            return { new: 'bg-success-50/60 dark:bg-success-500/10', modified: 'bg-warning-50/60 dark:bg-warning-500/10', removed: 'bg-error-50/60 text-gray-400 line-through dark:bg-error-500/10' }[change] || '';
        },
        // A part is new or removed as a whole, or modified when any of its rows changed
        partChange(part) {
            return part.change || (part.rows.some((row) => row.change) ? 'modified' : null);
        },
        changeTag(change) {
            return {
                new: 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
                modified: 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
                removed: 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400',
            }[change] || '';
        },
        shortDate(date) {
            return at(date).toLocaleDateString(dateLocale, { weekday: 'short', day: 'numeric', month: 'short' });
        },
        count(text, value) {
            return fill(text, { count: this.num(value) });
        },

        // Received pieces (HPO): { ordered, received }
        receiptPercent(receipt) {
            if (!receipt?.ordered) return 0;
            return Math.min(100, Math.round((receipt.received * 100) / receipt.ordered));
        },
        receiptPending(receipt) {
            return Math.max(0, (receipt?.ordered || 0) - (receipt?.received || 0));
        },
        receiptText(receipt) {
            return fill(texts.receivedOf, { received: this.num(receipt?.received), ordered: this.num(receipt?.ordered) });
        },
        receiptTone(receipt) {
            if (this.receiptPending(receipt) === 0) return 'bg-success-500';
            return this.dayIsOver() ? 'bg-error-500' : 'bg-warning-500';
        },
        // Before today: what is missing did not arrive ("faltó"); today and later it is still on the way
        dayIsOver() {
            const now = new Date();
            const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            return this.day.date < today;
        },

        // What Infor did to the day: { at, kind: new | modified | removed, recent }, in a few words
        changeText(change) {
            return `${texts.changeKinds[change.kind]} · ${this.shortDateTime(change.at)}`;
        },
        shortDateTime(iso) {
            return new Date(iso).toLocaleString(dateLocale, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        },

        // Versions of a document: the original and each change, the last is the current
        versionName(version) {
            return version.original ? texts.versionOriginal : texts.changeKinds[version.kind];
        },
        // mode: 'pdf' or 'excel' (both download)
        versionUrl(kind, version, mode) {
            const url = urls.version.replace('__DATE__', this.day.date).replace('__DOC__', kind).replace('__SNAP__', version.snapshot);
            return mode === 'excel' ? `${url}?formato=excel` : url;
        },
        // "Original · 12 nov, 4:24 p.m." for the header of a version being looked at
        get detailsVersionText() {
            const version = this.detailsVersion;
            return version ? `${this.versionName(version)} · ${this.shortDateTime(version.at)}` : '';
        },
        async loadVersions(kind) {
            if (this.versionsDate === this.day.date) return;
            const date = this.day.date;
            this.versionsDate = date;
            this.versionsLoaded = false;
            try {
                const response = await fetch(urls.versions.replace('__DATE__', date), { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                if (this.day.date === date) this.versions = await response.json();
            } catch (e) {
                this.versionsDate = null; // tries again the next time it is opened
            } finally {
                if (this.day.date === date) this.versionsLoaded = true;
            }
        },

        isPending(kind) {
            return Boolean(this.pending[`${kind}-${this.day.date}`]);
        },

        // Notifications: "loading" stays until the download ends; the others close by themselves
        notify(toast) {
            const id = ++toastSeq;
            this.toasts.push({ id, ...toast });
            this.autoDismiss(id, toast.kind);
            return id;
        },
        update(id, changes) {
            this.toasts = this.toasts.map((toast) => (toast.id === id ? { ...toast, ...changes } : toast));
            this.autoDismiss(id, changes.kind);
        },
        autoDismiss(id, kind) {
            if (kind === 'loading') return;
            setTimeout(() => this.dismiss(id), kind === 'error' ? 10000 : 6000);
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },

        // The modal closes at once and the file is prepared in the background:
        // a notification says it is on its way and another one when it is ready
        async download(kind) {
            const { url, name, type } = DOWNLOADS[kind];
            const date = this.day.date;
            const key = `${kind}-${date}`;
            if (this.pending[key]) return;

            this.pending[key] = true;
            this.close();

            const id = this.notify({
                kind: 'loading',
                title: fill(texts.preparingTitle, { name }),
                message: fill(texts.preparingMessage, { date: this.longDate }),
            });
            const fail = (message) => this.update(id, { kind: 'error', title: fill(texts.failedTitle, { name }), message });

            try {
                const response = await fetch(url.replace('__DATE__', date));
                if (!response.ok) {
                    fail(response.status === 404 ? texts.noData : texts.genericError);
                    return;
                }

                // An expired session redirects to the sign in page instead of sending the file
                if (!(response.headers.get('Content-Type') || '').includes(type)) {
                    fail(texts.sessionEnded);
                    return;
                }

                const blob = await response.blob();
                const match = (response.headers.get('Content-Disposition') || '').match(/filename="?([^";]+)"?/);
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = match ? match[1] : kind;
                link.click();
                setTimeout(() => URL.revokeObjectURL(link.href), 1000);

                this.update(id, { kind: 'success', title: fill(texts.readyTitle, { name }), message: fill(texts.readyMessage, { file: link.download }) });
                // The calendar reloads its days to show the check mark
                window.dispatchEvent(new CustomEvent('calendar-document-downloaded'));
            } catch (e) {
                fail(texts.genericError);
            } finally {
                delete this.pending[key];
            }
        },
    };
}
