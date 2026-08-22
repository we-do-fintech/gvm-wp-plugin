<style>
	.gvm-paywall-box {
		border: 1px solid rgba(255, 185, 3, 0.45);
		border-radius: 14px;
		padding: 24px;
		max-width: 940px;
		margin: 0 auto;
		text-align: center;
		background: linear-gradient(135deg, rgba(255, 185, 3, 0.12) 0%, rgba(123, 44, 255, 0.08) 100%);
	}
	.gvm-paywall-box .gvm-meta {
		font-size: 0.95rem;
		opacity: 0.85;
	}
	.gvm-paywall-box [data-gvm-bind-reading-time],
	.gvm-paywall-box [data-gvm-bind-reading-words] {
		font-weight: 700;
	}
	.gvm-paywall-box button {
		margin-top: 8px;
		font-weight: 700;
	}
</style>
<div class="gvm-paywall-box">
	<p>Unlock this article in about 10 seconds with GetViaMsg.</p>
	<p class="gvm-meta">
		Session size:
		<span data-gvm-bind-reading-time></span> minutes or
		<span data-gvm-bind-reading-words></span> words.
	</p>
	<button data-gvm-bind-pay>
		Unlock now for
		<span data-gvm-bind-price></span>
		<span data-gvm-bind-currency></span>
	</button>
</div>
