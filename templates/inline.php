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

        /*
         * Inline paywall card for the `hide` strategy, rendered directly in
         * the article flow.
         *
         * For the `blur` strategy it doubles as a sticky layer: because it is
         * `position: sticky; bottom: 0` it stays pinned to the bottom of the
         * viewport while you scroll through the locked container, then settles
         * and travels to the end of that container — never outside of it.
         *
         * Note: sticky needs a scroll container without `overflow: hidden` on
         * the ancestors. If a theme clips the article, the card falls back to
         * a normal inline block (which is exactly what `hide` wants anyway).
         */
        .gvm-paywall-inline {
            position: sticky;
            bottom: 0;
            z-index: 5;

            display: block;

            margin: 28px 0;
            padding: 20px;

            background: var(--gvm-bg, #fff);
            color: var(--gvm-fg, #111);

            border: 1px solid var(--gvm-border, #d9d9d9);
            border-radius: var(--gvm-radius, 10px);

            box-shadow: 0 16px 40px var(--gvm-shadow-color, rgba(0, 0, 0, 0.10));
        }

        .gvm-paywall-inline *,
        .gvm-paywall-inline *::before,
        .gvm-paywall-inline *::after {
            box-sizing: border-box;
        }

        .gvm-paywall-inline__body {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .gvm-paywall-inline__copy {
            flex: 1 1 auto;
            min-width: 0;
        }

        .gvm-paywall-inline__eyebrow {
            margin: 0 0 6px;

            color: var(--gvm-fg-subtle, #777);

            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .gvm-paywall-inline__title {
            margin: 0;

            color: var(--gvm-fg, #111);

            font-size: 17px;
            line-height: 1.25;
            font-weight: 600;
        }

        .gvm-paywall-inline__meta {
            margin: 6px 0 0;

            color: var(--gvm-fg-subtle, #777);

            font-size: 12px;
            line-height: 1.4;
        }

        .gvm-paywall-inline__aside {
            display: flex;
            align-items: center;
            gap: 16px;

            flex: 0 0 auto;
        }

        .gvm-paywall-inline__price {
            color: var(--gvm-fg, #111);

            font-size: 20px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .gvm-paywall-inline__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 44px;

            margin: 0;
            padding: 10px 18px;

            background: var(--gvm-accent, #111);
            color: var(--gvm-accent-fg, #fff);

            border: 1px solid var(--gvm-accent, #111);
            border-radius: var(--gvm-radius, 4px);

            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.2;

            white-space: nowrap;
            cursor: pointer;
        }

        .gvm-paywall-inline__button:hover {
            background: var(--gvm-accent-hover, #333);
            border-color: var(--gvm-accent-hover, #333);
        }

        .gvm-paywall-inline__button:focus-visible {
            outline: 2px solid var(--gvm-accent, #111);
            outline-offset: 2px;
        }

        .gvm-paywall-inline__hint {
            margin: 14px 0 0;

            color: var(--gvm-fg-faint, #999);

            font-size: 11px;
            line-height: 1.4;
        }

        @media (max-width: 640px) {
            .gvm-paywall-inline {
                margin: 20px 0;
                padding: 16px;

                border-radius: var(--gvm-radius, 8px);
            }

            .gvm-paywall-inline__body {
                flex-direction: column;
                align-items: stretch;
                gap: 14px;
            }

            .gvm-paywall-inline__aside {
                justify-content: space-between;
            }

            .gvm-paywall-inline__title {
                font-size: 16px;
            }

            .gvm-paywall-inline__button {
                flex: 0 0 auto;
            }
        }

        @media (max-width: 360px) {
            .gvm-paywall-inline__aside {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .gvm-paywall-inline__price {
                text-align: center;
            }
        }
    </style>

    <div
        class="gvm-paywall-inline"
        role="note"
        aria-label="Odblokuj pełną treść artykułu"
    >
        <div class="gvm-paywall-inline__body">
            <div class="gvm-paywall-inline__copy">
                <p class="gvm-paywall-inline__eyebrow">
                    Dostęp jednorazowy
                </p>

                <p class="gvm-paywall-inline__title">
                    Odblokuj pełną treść artykułu
                </p>

                <p class="gvm-paywall-inline__meta">
                    <span data-gvm-bind-reading-time></span>
                    min czytania
                    ·
                    <span data-gvm-bind-reading-words></span>
                    słów
                </p>
            </div>

            <div class="gvm-paywall-inline__aside">
                <span class="gvm-paywall-inline__price">
                    <span data-gvm-bind-price></span>
                    <span data-gvm-bind-currency></span>
                </span>

                <button
                    type="button"
                    class="gvm-paywall-inline__button"
                    data-gvm-bind-pay
                >
                    Odblokuj
                </button>
            </div>
        </div>

        <p class="gvm-paywall-inline__hint">
            Bez karty · Bez konta · Bez odnawiania
        </p>
    </div>
