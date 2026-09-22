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

        .gvm-payment-dialog {
            --gvm-overlay-pad: 20px;
            --gvm-card-max: 440px;

            box-sizing: border-box;
            /*
             * Fluid QR size: never bigger than 170px, capped by viewport
             * width (phones) and height (landscape / short screens).
             */
            --gvm-qr-size: min(170px, 45vw, 34vh);

            position: fixed;
            inset: 0;
            z-index: 1000001;

            display: flex;

            width: 100%;
            max-width: none;
            height: auto;
            max-height: none;

            margin: 0;
            padding: var(--gvm-overlay-pad);
            padding:
                max(var(--gvm-overlay-pad), env(safe-area-inset-top))
                max(var(--gvm-overlay-pad), env(safe-area-inset-right))
                max(var(--gvm-overlay-pad), env(safe-area-inset-bottom))
                max(var(--gvm-overlay-pad), env(safe-area-inset-left));

            border: 0;
            border-radius: 0;

            background: var(--gvm-overlay, rgba(0, 0, 0, 0.6));
            color: var(--gvm-fg, #111);

            -webkit-backdrop-filter: blur(4px);
            backdrop-filter: blur(4px);

            overflow: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        .gvm-payment-dialog::backdrop {
            background: var(--gvm-overlay, rgba(0, 0, 0, 0.6));
        }

        .gvm-payment,
        .gvm-payment * {
            box-sizing: border-box;
        }

        /*
         * The dialog is a full-screen overlay, so it covers and blurs the
         * sticky paywall (z-index 999999) while payment is open. The card is
         * this article, centered inside the overlay.
         */
        .gvm-payment {
            width: 100%;
            max-width: var(--gvm-card-max);

            margin: auto;
            padding: 0;

            border: 1px solid var(--gvm-border, #d8d8d8);
            border-radius: var(--gvm-radius, 4px);

            background: var(--gvm-bg, #fff);
            color: var(--gvm-fg, #111);

            box-shadow: 0 20px 60px var(--gvm-shadow-color, rgba(0, 0, 0, 0.2));

            overflow: hidden;

            font-family:
                var(--gvm-font, system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif);
        }

        .gvm-payment__header {
            margin: 0;
            padding: 24px 26px 20px;

            border-bottom: 1px solid var(--gvm-border-subtle, #e1e1e1);
        }

        .gvm-payment__eyebrow {
            margin: 0 0 7px;

            color: var(--gvm-fg-subtle, #777);

            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .gvm-payment__title {
            margin: 0;

            color: var(--gvm-fg, #111);

            font-size: clamp(20px, 5.5vw, 25px);
            line-height: 1.15;
            font-weight: 600;
        }

        .gvm-payment__subtitle {
            margin: 7px 0 0;

            color: var(--gvm-fg-muted, #666);

            font-size: 14px;
            line-height: 1.45;
        }

        .gvm-payment__body {
            padding: 22px 26px 24px;
        }

        .gvm-payment__price {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin: 0 0 20px;
            padding: 12px 14px;

            border: 1px solid var(--gvm-border, #dedede);
            border-radius: var(--gvm-radius, 3px);

            background: var(--gvm-bg-subtle, #fafafa);
        }

        .gvm-payment__price-label {
            color: var(--gvm-fg-muted, #666);
            font-size: 13px;
        }

        .gvm-payment__price-value {
            color: var(--gvm-fg, #111);
            font-size: 18px;
            font-weight: 700;
        }

        /*
         * QR gate
         */
        .gvm-payment__qr-gate {
            position: relative;

            width: var(--gvm-qr-size);
            height: var(--gvm-qr-size);

            margin: 0 auto 17px;
        }

        .gvm-payment__qr {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 100%;
            height: 100%;

            border: 1px solid var(--gvm-border, #ddd);
            border-radius: var(--gvm-radius, 3px);

            background: var(--gvm-bg, #fff);

            overflow: hidden;

            transition: filter 0.2s ease;
        }

        /*
         * QR <img> is injected without dimensions (library default 300x300),
         * so force it to fit the box while keeping its aspect ratio.
         */
        .gvm-payment__qr img {
            display: block;

            width: 100%;
            height: 100%;

            object-fit: contain;
        }

        .gvm-payment__qr-gate.gvm-terms-blocked
        .gvm-payment__qr {
            filter: blur(10px);
        }

        .gvm-payment__qr-cover {
            display: none;

            position: absolute;
            inset: 0;
            z-index: 2;

            align-items: center;
            justify-content: center;

            padding: 20px;

            background: var(--gvm-bg-glass, rgba(255, 255, 255, 0.86));

            color: var(--gvm-fg, #111);

            font-size: 12px;
            line-height: 1.4;
            font-weight: 600;

            text-align: center;
        }

        .gvm-payment__qr-gate.gvm-terms-blocked
        .gvm-payment__qr-cover {
            display: flex;
        }

        .gvm-payment__qr [aria-busy="true"] {
            width: 25px;
            height: 25px;

            border: 2px solid var(--gvm-border, #ddd);
            border-top-color: var(--gvm-accent, #111);
            border-radius: 50%;

            animation: gvm-payment-spin 0.8s linear infinite;
        }

        @keyframes gvm-payment-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .gvm-payment__status {
            margin: 0;

            color: var(--gvm-fg-3, #444);

            font-size: 13px;
            line-height: 1.4;
            text-align: center;
        }

        .gvm-payment__time {
            color: var(--gvm-fg-subtle, #888);
            white-space: nowrap;
        }

        .gvm-payment__terms {
            display: flex;
            align-items: flex-start;
            gap: 8px;

            margin: 18px 0 0;

            color: var(--gvm-fg-muted, #555);

            font-size: 12px;
            line-height: 1.45;
        }

        .gvm-payment__terms input {
            width: 16px;
            height: 16px;

            flex: 0 0 auto;

            margin: 1px 0 0;
        }

        .gvm-payment__terms a {
            color: var(--gvm-fg, #111);
            text-decoration: underline;
        }

        .gvm-payment__promise {
            margin: 15px 0 0;
            padding: 11px 13px;

            border-left: 2px solid var(--gvm-accent, #111);

            background: var(--gvm-bg-muted, #f7f7f7);

            color: var(--gvm-fg-muted, #666);

            font-size: 12px;
            line-height: 1.5;
        }

        .gvm-payment__actions {
            display: grid;
            gap: 8px;

            margin-top: 20px;
        }

        .gvm-payment__actions button {
            width: 100%;
            min-height: 44px;

            margin: 0;
            padding: 10px 14px;

            border-radius: var(--gvm-radius, 3px);

            font-size: 14px;
            font-weight: 600;
        }

        .gvm-payment__actions button:focus-visible {
            outline: 2px solid var(--gvm-accent, #111);
            outline-offset: 2px;
        }

        .gvm-payment__send {
            border: 1px solid var(--gvm-accent, #111);
            background: var(--gvm-accent, #111);
            color: var(--gvm-accent-fg, #fff);
        }

        .gvm-payment__send.gvm-terms-blocked {
            background: var(--gvm-blocked, #777);
            border-color: var(--gvm-blocked, #777);
        }

        .gvm-payment__cancel {
            border: 1px solid var(--gvm-border, #d5d5d5);
            background: var(--gvm-bg, #fff);
            color: var(--gvm-fg-2, #333);
        }

        .gvm-payment__footer {
            margin: 0;
            padding: 12px 26px;

            border-top: 1px solid var(--gvm-border-subtle, #e1e1e1);

            background: var(--gvm-bg-subtle, #fafafa);

            text-align: center;
        }

        .gvm-payment__footer small {
            color: var(--gvm-fg-subtle, #888);
            font-size: 11px;
        }

        .gvm-payment__footer a {
            color: var(--gvm-fg-3, #444);
        }

        @media (max-width: 480px) {
            .gvm-payment-dialog {
                --gvm-overlay-pad: 10px;
            }

            .gvm-payment__header {
                padding: 20px;
            }

            .gvm-payment__body {
                padding: 18px 20px 20px;
            }

            .gvm-payment__footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .gvm-payment__price {
                margin-bottom: 16px;
            }
        }

        /* Short viewports (landscape phones, small laptops) */
        @media (max-height: 560px) {
            .gvm-payment-dialog {
                --gvm-overlay-pad: 10px;
            }

            .gvm-payment__header {
                padding: 16px 20px 14px;
            }

            .gvm-payment__body {
                padding: 14px 20px 16px;
            }

            .gvm-payment__price {
                margin-bottom: 12px;
                padding: 8px 12px;
            }

            .gvm-payment__qr-gate {
                margin-bottom: 12px;
            }

            .gvm-payment__actions {
                margin-top: 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .gvm-payment__qr [aria-busy="true"] {
                animation: none;
            }

            .gvm-payment__qr {
                transition: none;
            }
        }
    </style>

    <dialog
        open
        class="gvm-payment-dialog"
        aria-label="Płatność za dostęp do artykułu"
    >
        <article class="gvm-payment">

            <header class="gvm-payment__header">
                <p class="gvm-payment__eyebrow">
                    Jednorazowy dostęp
                </p>

                <h2 class="gvm-payment__title">
                    Odblokuj artykuł
                </h2>

                <p class="gvm-payment__subtitle">
                    Wyślij wiadomość SMS, aby uzyskać natychmiastowy
                    dostęp do pełnej treści.
                </p>
            </header>

            <section class="gvm-payment__body">

                <div class="gvm-payment__price">
                    <span class="gvm-payment__price-label">
                        Cena artykułu
                    </span>

                    <strong class="gvm-payment__price-value">
                        <span data-gvm-bind-price></span>
                        <span data-gvm-bind-currency></span>
                    </strong>
                </div>

                <div
                    class="gvm-payment__qr-gate gvm-terms-blocked"
                    data-gvm-terms-container
                >
                    <div
                        data-gvm-bind-qr
                        class="gvm-payment__qr"
                    >
                        <span aria-busy="true"></span>
                    </div>

                    <div class="gvm-payment__qr-cover">
                        Zaakceptuj regulamin,
                        aby wyświetlić kod QR.
                    </div>
                </div>

                <p class="gvm-payment__status">
                    <span
                        data-gvm-bind-status
                        data-gvm-bind-status-initializing="Przygotowywanie kodu QR…"
                        data-gvm-bind-status-waiting="Oczekiwanie na wiadomość SMS…"
                        data-gvm-bind-status-duplicated="Ten numer ma już odblokowany artykuł."
                        data-gvm-bind-status-resolved="Płatność potwierdzona. Odblokowujemy artykuł…"
                        data-gvm-bind-status-rejected="Nie udało się potwierdzić płatności. Spróbuj ponownie."
                    ></span>

                    <span class="gvm-payment__time">
                        (
                        <span data-gvm-bind-time-remaining>
                            00:00
                        </span>
                        )
                    </span>
                </p>

                <label class="gvm-payment__terms">
                    <input
                        type="checkbox"
                        data-gvm-accept-terms=".gvm-payment__qr-gate, [data-gvm-bind-send-sms]"
                        data-gvm-accept-terms-alert="Musisz zaakceptować regulamin, aby kontynuować."
                        data-gvm-accept-terms-label="Zaakceptuj regulamin, aby wysłać SMS"
                    />

                    <span>
                        Akceptuję
                        <a href="/regulamin/" target="_blank">
                            regulamin
                        </a>
                        i
                        <a href="/polityka-prywatnosci/" target="_blank">
                            politykę prywatności
                        </a>.
                    </span>
                </label>

                <p class="gvm-payment__promise">
                    Dostęp zostanie odblokowany natychmiast po
                    potwierdzeniu płatności.
                </p>

                <div class="gvm-payment__actions">

                    <button
                        type="button"
                        class="gvm-payment__send"
                        data-gvm-bind-send-sms
                    >
                        Wyślij SMS i odblokuj
                    </button>

                    <button
                        type="button"
                        class="gvm-payment__cancel"
                        data-gvm-bind-cancel
                    >
                        Anuluj
                    </button>

                </div>

            </section>

            <footer class="gvm-payment__footer">
                <small>
                    Płatność obsługiwana przez
                    <a href="https://getviamsg.wdft.ovh/">
                        GetViaMsg
                    </a>
                    · WDFT
                </small>
            </footer>

        </article>
    </dialog>
