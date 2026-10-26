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
        loadingDetails: false,
        detailsError: false,
        search: '',
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
        async showDetails(kind) {
            this.view = kind;
            this.search = '';
            const date = this.day.date;
            if (this.detailsDate === date && this.details) return;

            this.details = null;
            this.detailsError = false;
            this.loadingDetails = true;
            try {
                const response = await fetch(urls.details.replace('__DATE__', date), { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const details = await response.json();
                // Another day may have been opened meanwhile
                if (this.day.date !== date) return;
                this.details = details;
                this.detailsDate = date;
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
        num(value) {
            return Number(value || 0).toLocaleString(dateLocale);
        },
        sum(rows, key) {
            return (rows || []).reduce((total, row) => total + Number(row[key] || 0), 0);
        },
        shortDate(date) {
            return at(date).toLocaleDateString(dateLocale, { weekday: 'short', day: 'numeric', month: 'short' });
        },
        count(text, value) {
            return fill(text, { count: this.num(value) });
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
