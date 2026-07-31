import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('commandPalette', {
    open: false,
});

/**
 * Live-polls a batch's /status endpoint so the detail page's progress bar,
 * status badge and ETA update on their own without a page reload. A store
 * (rather than a per-element x-data) because the status badge lives in the
 * page header slot and the progress card lives in the body — two separate
 * DOM subtrees that need to share one timer/state, not one each.
 *
 * The server throttles how often this actually calls OpenAI (see
 * BatchStatusPoller::pollIfStale) — the polling cadence here is just how
 * often the browser checks in, not how often OpenAI is actually hit.
 */
Alpine.store('batchStatus', {
    url: null,
    status: null,
    done: 0,
    total: 0,
    etaHuman: null,
    labels: {},
    timer: null,
    consecutiveFailures: 0,
    maxConsecutiveFailures: 5,
    terminalStatuses: ['completed', 'failed', 'expired', 'cancelled'],
    colors: {
        completed: { dot: 'bg-green-500', text: 'text-green-700', bg: 'bg-green-50' },
        in_progress: { dot: 'bg-amber-500', text: 'text-amber-700', bg: 'bg-amber-50' },
        finalizing: { dot: 'bg-amber-500', text: 'text-amber-700', bg: 'bg-amber-50' },
        queued: { dot: 'bg-navy-500', text: 'text-navy-700', bg: 'bg-navy-50' },
        uploading: { dot: 'bg-navy-500', text: 'text-navy-700', bg: 'bg-navy-50' },
        failed: { dot: 'bg-red-500', text: 'text-red-700', bg: 'bg-red-50' },
        expired: { dot: 'bg-red-500', text: 'text-red-700', bg: 'bg-red-50' },
        cancelled: { dot: 'bg-red-500', text: 'text-red-700', bg: 'bg-red-50' },
        cancelling: { dot: 'bg-red-500', text: 'text-red-700', bg: 'bg-red-50' },
    },
    defaultColor: { dot: 'bg-gray-400', text: 'text-gray-600', bg: 'bg-gray-100' },

    // Deliberately not named init() — Alpine.store() auto-calls a method
    // named init() with zero arguments as soon as Alpine.start() runs. This
    // one needs the page's config object, so it's called explicitly instead
    // (see x-init="$store.batchStatus.boot(...)" in show.blade.php). Naming
    // it init() previously meant Alpine called it with no arguments during
    // startup, throwing on `config.url` and taking down Alpine entirely —
    // breaking every other Alpine-powered element on every page, not just
    // this one.
    boot(config) {
        this.url = config.url;
        this.status = config.status;
        this.done = config.done;
        this.total = config.total;
        this.etaHuman = config.etaHuman;
        this.labels = config.labels;

        if (!this.isTerminal) {
            this.timer = setInterval(() => this.poll(), 5000);
        }
    },

    get isTerminal() {
        return this.terminalStatuses.includes(this.status);
    },

    get progressPercent() {
        return this.total > 0 ? Math.round((this.done / this.total) * 100) : 0;
    },

    get label() {
        return this.labels[this.status] ?? this.status;
    },

    get color() {
        return this.colors[this.status] ?? this.defaultColor;
    },

    async poll() {
        let response;

        try {
            response = await fetch(this.url, { headers: { Accept: 'application/json' } });
        } catch {
            // Network hiccup (offline, DNS, etc.) — treat like a failed
            // response below rather than letting it throw out of the timer.
            response = null;
        }

        if (!response || !response.ok) {
            // A stale session (expired login), a server error, or the
            // network being down would otherwise poll forever every 5s with
            // no feedback and no way to stop. Give up after a few misses in
            // a row instead of polling silently forever.
            this.consecutiveFailures += 1;

            if (this.consecutiveFailures >= this.maxConsecutiveFailures) {
                clearInterval(this.timer);
            }

            return;
        }

        this.consecutiveFailures = 0;

        const data = await response.json();

        this.status = data.status;
        this.done = data.done;
        this.total = data.total;
        this.etaHuman = data.eta_human;

        if (this.isTerminal) {
            clearInterval(this.timer);
            // Full reload rather than client-side patching — lets the server
            // render the real Results/Download section for the new status.
            window.location.reload();
        }
    },
});

Alpine.start();
