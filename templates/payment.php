
<style>
    /*
     * Design tokens — override on :root (or any ancestor) to re-theme.
     * Every usage below is var(--token, <default>), so the default look
     * stays exactly the same when nothing is overridden.
     *
     *   fonts    --gvm-font, --gvm-font-mono
     *   text     --gvm-fg, --gvm-fg-2, --gvm-fg-3, --gvm-fg-muted,
     *            --gvm-fg-subtle, --gvm-fg-faint
     *   surface  --gvm-bg, --gvm-bg-subtle, --gvm-bg-muted, --gvm-bg-glass
     *   lines    --gvm-border, --gvm-border-subtle
     *   accent   --gvm-accent, --gvm-accent-fg, --gvm-accent-hover
     *   blocked  --gvm-blocked, --gvm-blocked-hover
     *   effects  --gvm-overlay, --gvm-shadow-color
     *   shape    --gvm-radius, --gvm-radius-pill
     */

    .gvm-payment-drawer-overlay {
        --gvm-drawer-pad: 24px;
        --gvm-qr-size: min(180px, 55vw, 30vh);

        position: fixed;
        inset: 0;
        z-index: 1000001;

        box-sizing: border-box;

        display: flex;
        align-items: stretch;
        justify-content: flex-end;

        width: 100%;
        max-width: none;
        height: auto;
        max-height: none;

        margin: 0;
        padding: 0;

        border: 0;
        border-radius: 0;

        background: var(--gvm-overlay, rgba(0, 0, 0, 0.60));
        color: var(--gvm-fg, #111);

        -webkit-backdrop-filter: blur(4px);
        backdrop-filter: blur(4px);

        overflow: hidden;
        overscroll-behavior: contain;
    }

    .gvm-payment-drawer-overlay::backdrop {
        background: var(--gvm-overlay, rgba(0, 0, 0, 0.60));
    }

    .gvm-payment-drawer,
    .gvm-payment-drawer * {
        box-sizing: border-box;
    }

    /*
     * Payment process as a drawer, complementing the paywall drawer.
     * Desktop: full-height panel on the right.
     * Mobile: bottom sheet.
     */
    .gvm-payment-drawer {
        display: flex;
        flex-direction: column;

        width: min(400px, 100%);
        height: 100%;

        background: var(--gvm-bg, #fff);
        color: var(--gvm-fg, #111);

        border-left: 1px solid var(--gvm-border, #d8d8d8);

        box-shadow: -16px 0 48px var(--gvm-shadow-color, rgba(0, 0, 0, 0.20));

        overflow: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;

        font-family: var(--gvm-font, system-ui, -apple-system, BlinkMacSystemFont,
            "Segoe UI", sans-serif);
    }

    .gvm-payment-drawer__handle {
        display: none;

        width: 38px;
        height: 4px;

        margin: 10px auto 0;

        flex: 0 0 auto;

        background: var(--gvm-border, #d0d0d0);
        border-radius: var(--gvm-radius-pill, 999px);
    }

    /* ------------------------------------------------------- header */
    .gvm-payment-drawer__header {
        flex: 0 0 auto;

        padding: var(--gvm-drawer-pad);

        border-bottom: 1px solid var(--gvm-border-subtle, #e1e1e1);
    }

    .gvm-payment-drawer__eyebrow {
        margin: 0 0 7px;

        color: var(--gvm-fg-subtle, #777);

        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .gvm-payment-drawer__title {
        margin: 0;

        color: var(--gvm-fg, #111);

        font-size: 22px;
        line-height: 1.15;
        font-weight: 600;
    }

    .gvm-payment-drawer__subtitle {
        margin: 7px 0 0;

        color: var(--gvm-fg-muted, #666);

        font-size: 13px;
        line-height: 1.45;
    }

    /* the “scan the code” hint only makes sense when the QR is visible */
    .gvm-payment-drawer__qr-hint {
        display: inline;
    }

    /* --------------------------------------------------------- body */
    .gvm-payment-drawer__body {
        flex: 1 1 auto;

        padding: var(--gvm-drawer-pad);
    }

    .gvm-payment-drawer__price {
        display: flex;
        align-items: center;
        justify-content: space-between;

        margin: 0 0 20px;
        padding: 12px 14px;

        background: var(--gvm-bg-subtle, #fafafa);
        border: 1px solid var(--gvm-border, #dedede);
        border-radius: var(--gvm-radius, 3px);
    }

    .gvm-payment-drawer__price-label {
        color: var(--gvm-fg-muted, #666);
        font-size: 13px;
    }

    .gvm-payment-drawer__price-value {
        color: var(--gvm-fg, #111);
        font-size: 18px;
        font-weight: 700;
    }

    .gvm-payment-drawer__qr {
        display: flex;
        align-items: center;
        justify-content: center;

        width: var(--gvm-qr-size);
        height: var(--gvm-qr-size);

        margin: 0 auto 16px;

        background: var(--gvm-bg, #fff);
        border: 1px solid var(--gvm-border, #ddd);
        border-radius: var(--gvm-radius, 3px);

        overflow: hidden;
    }

    .gvm-payment-drawer__qr > * {
        max-width: 100%;
        max-height: 100%;
    }

    /*
     * QR <img> is injected without dimensions (library default 300x300),
     * so force it to fit the box while keeping its aspect ratio.
     */
    .gvm-payment-drawer__qr img {
        display: block;

        width: 100%;
        height: 100%;

        object-fit: contain;
    }

    .gvm-payment-drawer__qr [aria-busy="true"] {
        width: 25px;
        height: 25px;

        border: 2px solid var(--gvm-border, #ddd);
        border-top-color: var(--gvm-accent, #111);
        border-radius: 50%;

        animation: gvm-payment-drawer-spin 0.8s linear infinite;
    }

    @keyframes gvm-payment-drawer-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .gvm-payment-drawer__status {
        margin: 0;

        color: var(--gvm-fg-3, #444);

        font-size: 13px;
        line-height: 1.4;
        text-align: center;
    }

    .gvm-payment-drawer__time {
        color: var(--gvm-fg-subtle, #888);
        white-space: nowrap;
    }

    .gvm-payment-drawer__promise {
        margin: 16px 0 0;
        padding: 11px 13px;

        background: var(--gvm-bg-muted, #f7f7f7);
        border-left: 2px solid var(--gvm-accent, #111);

        color: var(--gvm-fg-muted, #666);

        font-size: 12px;
        line-height: 1.5;
    }

    .gvm-payment-drawer__actions {
        display: grid;
        gap: 8px;

        margin-top: 18px;
    }

    .gvm-payment-drawer__actions button {
        width: 100%;
        min-height: 46px;

        margin: 0;
        padding: 10px 14px;

        border-radius: var(--gvm-radius, 4px);

        font-family: inherit;
        font-size: 14px;
        font-weight: 600;

        cursor: pointer;
    }

    .gvm-payment-drawer__actions button:focus-visible {
        outline: 2px solid var(--gvm-accent, #111);
        outline-offset: 2px;
    }

    .gvm-payment-drawer__send {
        background: var(--gvm-accent, #111);
        color: var(--gvm-accent-fg, #fff);
        border: 1px solid var(--gvm-accent, #111);
    }

    .gvm-payment-drawer__send:hover {
        background: var(--gvm-accent-hover, #333);
        border-color: var(--gvm-accent-hover, #333);
    }

    .gvm-payment-drawer__cancel {
        background: var(--gvm-bg, #fff);
        color: var(--gvm-fg-2, #333);
        border: 1px solid var(--gvm-border, #d5d5d5);
    }

    .gvm-payment-drawer__cancel:hover {
        background: var(--gvm-bg-muted, #f5f5f5);
    }

    /* ------------------------------------------------------- footer */
    .gvm-payment-drawer__footer {
        flex: 0 0 auto;

        padding: 12px var(--gvm-drawer-pad);

        background: var(--gvm-bg-subtle, #fafafa);
        border-top: 1px solid var(--gvm-border-subtle, #e1e1e1);

        text-align: center;
    }

    .gvm-payment-drawer__footer small {
        color: var(--gvm-fg-subtle, #888);
        font-size: 11px;
    }

    .gvm-payment-drawer__footer a {
        color: var(--gvm-fg-3, #444);
    }

    /* ---------------------------------------------------- bottom sheet */
    @media (max-width: 640px) {
        .gvm-payment-drawer-overlay {
            --gvm-drawer-pad: 20px;

            align-items: flex-end;
        }

        .gvm-payment-drawer {
            width: 100%;
            height: auto;
            max-height: 94%;

            border-left: 0;
            border-top: 1px solid var(--gvm-border, #d8d8d8);
            border-radius: 16px 16px 0 0;

            box-shadow: 0 -16px 48px var(--gvm-shadow-color, rgba(0, 0, 0, 0.22));

            padding-bottom: env(safe-area-inset-bottom);
        }

        .gvm-payment-drawer__handle {
            display: block;
        }

        /* a phone cannot scan its own screen — SMS is the action */
        .gvm-payment-drawer__qr,
        .gvm-payment-drawer__qr-hint {
            display: none;
        }
    }

    /* ------------------------------------------- short screens (land.) */
    @media (max-height: 560px) {
        .gvm-payment-drawer-overlay {
            --gvm-drawer-pad: 16px;
        }

        .gvm-payment-drawer__promise {
            display: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .gvm-payment-drawer__qr [aria-busy="true"] {
            animation: none;
        }
    }
</style>

<dialog
    open
    class="gvm-payment-drawer-overlay"
    aria-label="Płatność za dostęp do materiału"
>
    <div class="gvm-payment-drawer">
        <span class="gvm-payment-drawer__handle" aria-hidden="true"></span>

        <header class="gvm-payment-drawer__header">
            <p class="gvm-payment-drawer__eyebrow">
                Potwierdzenie SMS
            </p>

            <h2 class="gvm-payment-drawer__title">
                Odblokuj materiał
            </h2>

            <p class="gvm-payment-drawer__subtitle">
                Wyślij SMS, aby odblokować dostęp. To zwykła
                wiadomość, zwykle bezpłatna (zgodnie z cennikiem operatora).
                <span class="gvm-payment-drawer__qr-hint">
                    Możesz też zeskanować kod telefonem.
                </span>
            </p>
        </header>

        <section class="gvm-payment-drawer__body">
            <div class="gvm-payment-drawer__price">
                <span class="gvm-payment-drawer__price-label">
                    Cena materiału
                </span>

                <strong class="gvm-payment-drawer__price-value">
                    <span data-gvm-bind-price></span>
                    <span data-gvm-bind-currency></span>
                </strong>
            </div>

            <div
                data-gvm-bind-qr
                class="gvm-payment-drawer__qr"
            >
                <span aria-busy="true"></span>
            </div>

            <p class="gvm-payment-drawer__status">
                <span
                    data-gvm-bind-status
                    data-gvm-bind-status-initializing="Przygotowywanie kodu QR…"
                    data-gvm-bind-status-waiting="Oczekiwanie na wiadomość SMS…"
                    data-gvm-bind-status-duplicated="Ten numer ma już odblokowany materiał."
                    data-gvm-bind-status-resolved="Płatność potwierdzona. Odblokowujemy materiał…"
                    data-gvm-bind-status-rejected="Nie udało się potwierdzić płatności. Spróbuj ponownie."
                ></span>

                <span class="gvm-payment-drawer__time">
                    (
                    <span data-gvm-bind-time-remaining>
                        03:00
                    </span>
                    )
                </span>
            </p>

            <p class="gvm-payment-drawer__promise">
                Płacisz tylko za odblokowane treści. Rozliczenie zbiorcze i link do płatności otrzymasz SMS-em po zakończeniu sesji (14 dni).
            </p>

            <div class="gvm-payment-drawer__actions">
                <button
                    type="button"
                    class="gvm-payment-drawer__send"
                    data-gvm-bind-send-sms
                >
                    Wyślij SMS i odblokuj
                </button>

                <button
                    type="button"
                    class="gvm-payment-drawer__cancel"
                    data-gvm-bind-cancel
                >
                    Anuluj
                </button>
            </div>
        </section>

        <footer class="gvm-payment-drawer__footer">
            <small>
                Potwierdzenie SMS · system obsługuje
                <a href="https://getviamsg.wdft.ovh/">
                    GetViaMsg
                </a>
            </small>
        </footer>
    </div>
</dialog>
