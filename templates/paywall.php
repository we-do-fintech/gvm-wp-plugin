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

        .gvm-paywall-root {
            all: initial;

            position: fixed;
            z-index: 999999;

            left: 0;
            right: 0;
            bottom: 0;

            display: block;

            width: auto;
            max-width: none;
            height: auto;

            margin: 0;
            padding: 0;

            box-sizing: border-box;

            font-family: var(--gvm-font, system-ui, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif);

            color: var(--gvm-fg, #111);
        }

        .gvm-paywall-root *,
        .gvm-paywall-root *::before,
        .gvm-paywall-root *::after {
            box-sizing: border-box;
        }

        .gvm-paywall-bar {
            display: flex;

            align-items: center;
            justify-content: center;

            gap: clamp(12px, 2.5vw, 20px);

            width: 100%;

            margin: 0;
            padding:
                12px
                max(20px, env(safe-area-inset-right))
                calc(12px + env(safe-area-inset-bottom))
                max(20px, env(safe-area-inset-left));

            background: var(--gvm-bg, #fff);

            border-top: 1px solid var(--gvm-border, #d9d9d9);

            box-shadow:
                0 -4px 16px var(--gvm-shadow-color, rgba(0, 0, 0, 0.08));
        }

        .gvm-paywall-copy {
            display: block;

            margin: 0;
            padding: 0;

            min-width: 0;
        }

        .gvm-paywall-title {
            display: block;

            margin: 0;
            padding: 0;

            color: var(--gvm-fg, #111);

            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.35;
        }

        .gvm-paywall-meta {
            display: block;

            margin: 3px 0 0;
            padding: 0;

            color: var(--gvm-fg-subtle, #777);

            font-family: inherit;
            font-size: 12px;
            font-weight: 400;
            line-height: 1.3;
        }

        .gvm-paywall-price {
            display: block;

            margin: 0;
            padding: 0;

            color: var(--gvm-fg, #111);

            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.3;

            white-space: nowrap;
        }

        .gvm-paywall-button {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            flex: 0 0 auto;

            width: auto;
            min-width: 0;
            min-height: 40px;

            margin: 0;
            padding: 9px 16px;

            border: 1px solid var(--gvm-accent, #111);
            border-radius: var(--gvm-radius, 3px);

            background: var(--gvm-accent, #111);
            color: var(--gvm-accent-fg, #fff);

            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.2;

            text-align: center;
            text-decoration: none;

            cursor: pointer;
        }

        .gvm-paywall-button:hover {
            background: var(--gvm-accent-hover, #333);
            color: var(--gvm-accent-fg, #fff);
        }

        .gvm-paywall-button:focus-visible {
            outline: 2px solid var(--gvm-accent, #111);
            outline-offset: 2px;
        }

        @media (max-width: 640px) {
            .gvm-paywall-bar {
                justify-content: space-between;
                gap: 12px;

                padding:
                    10px
                    max(14px, env(safe-area-inset-right))
                    calc(10px + env(safe-area-inset-bottom))
                    max(14px, env(safe-area-inset-left));
            }

            .gvm-paywall-meta,
            .gvm-paywall-price {
                display: none;
            }

            .gvm-paywall-copy {
                overflow: hidden;
            }

            .gvm-paywall-title {
                font-size: 13px;

                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .gvm-paywall-button {
                min-height: 44px;
                padding: 9px 14px;

                font-size: 13px;
                white-space: nowrap;
            }
        }

        @media (max-width: 360px) {
            .gvm-paywall-title {
                font-size: 12px;
            }

            .gvm-paywall-button {
                padding: 9px 12px;

                font-size: 12px;
            }
        }
    </style>

    <div class="gvm-paywall-root">
        <div class="gvm-paywall-bar">

            <div class="gvm-paywall-copy">
                <div class="gvm-paywall-title">
                    Odblokuj pełną treść artykułu
                </div>

                <div class="gvm-paywall-meta">
                    <span data-gvm-bind-reading-time></span>
                    min czytania
                    ·
                    <span data-gvm-bind-reading-words></span>
                    słów
                </div>
            </div>

            <div class="gvm-paywall-price">
                <span data-gvm-bind-price></span>
                <span data-gvm-bind-currency></span>
            </div>

            <button
                type="button"
                class="gvm-paywall-button"
                data-gvm-bind-pay
            >
                Odblokuj
            </button>

        </div>
    </div>
