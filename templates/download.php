<style>
	.gvm-download-box {
		border: 1px solid rgba(255, 185, 3, 0.45);
		border-radius: 14px;
		padding: 24px;
		max-width: 560px;
		margin: 16px auto;
		text-align: center;
		background: linear-gradient(135deg, rgba(255, 185, 3, 0.12) 0%, rgba(123, 44, 255, 0.08) 100%);
	}
	.gvm-download-box button {
		margin-top: 16px;
		padding: 0.9rem 1.5rem;
		font-size: 1.15rem;
		font-weight: 700;
		color: #1a1a1a;
		background: linear-gradient(135deg, #ffb903 0%, #ffd75e 100%);
		border: 0;
		border-radius: 12px;
		cursor: pointer;
		box-shadow: 0 6px 20px rgba(255, 185, 3, 0.35);
	}
	.gvm-download-box button:hover {
		filter: brightness(1.06);
		transform: translateY(-1px);
	}
	.gvm-download-box button:active {
		transform: translateY(0);
	}
</style>
<div class="gvm-download-box">
	<p>Download this file after a one-time payment.</p>
	<button data-gvm-bind-pay>
		Download for
		<span data-gvm-bind-price></span>
		<span data-gvm-bind-currency></span>
	</button>
</div>
