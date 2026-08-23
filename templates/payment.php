<style>
	.gvm-payment-overlay {
		position: fixed;
		inset: 0;
		z-index: 99999;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 16px;
		background: rgba(0, 0, 0, 0.62);
	}
	.gvm-payment-card {
		width: 100%;
		max-width: 420px;
		background: #fff;
		color: #1a1a1a;
		border-radius: 16px;
		padding: 28px 24px;
		box-shadow: 0 24px 70px rgba(0, 0, 0, 0.4);
		font-family: inherit;
	}
	.gvm-payment-card h3 {
		margin: 0 0 6px;
		font-size: 1.35rem;
	}
	.gvm-payment-card .gvm-sub {
		margin: 0 0 16px;
		opacity: 0.7;
	}
	.gvm-payment-card .gvm-price {
		font-size: 1.6rem;
		font-weight: 800;
		margin: 0 0 8px;
	}
	.gvm-payment-card .gvm-qr {
		display: flex;
		justify-content: center;
		margin: 16px 0;
	}
	.gvm-payment-card .gvm-qr img {
		max-width: 200px;
	}
	.gvm-payment-card .gvm-status {
		min-height: 1.2em;
		text-align: center;
		margin: 8px 0;
		font-size: 0.95rem;
	}
	.gvm-payment-card .gvm-time {
		text-align: center;
		color: #8a6d00;
		font-weight: 700;
		font-variant-numeric: tabular-nums;
		margin: 0 0 8px;
	}
	.gvm-payment-card .gvm-actions {
		display: flex;
		flex-direction: column;
		gap: 10px;
		margin-top: 16px;
	}
	.gvm-payment-card button {
		width: 100%;
		padding: 0.85rem 1rem;
		font-size: 1.05rem;
		font-weight: 700;
		border-radius: 12px;
		cursor: pointer;
	}
	.gvm-payment-card button[data-gvm-bind-send-sms] {
		color: #1a1a1a;
		background: linear-gradient(135deg, #ffb903 0%, #ffd75e 100%);
		border: 0;
		box-shadow: 0 6px 20px rgba(255, 185, 3, 0.35);
	}
	.gvm-payment-card button[data-gvm-bind-send-sms]:hover {
		filter: brightness(1.06);
		transform: translateY(-1px);
	}
	.gvm-payment-card button[data-gvm-bind-cancel] {
		background: transparent;
		color: #555;
		border: 1px solid #ddd;
	}
	.gvm-payment-card button[data-gvm-bind-cancel]:hover {
		background: #f5f5f5;
	}
</style>
<div class="gvm-payment-overlay">
	<div class="gvm-payment-card">
		<header>
			<h3>Unlock in seconds</h3>
			<p class="gvm-sub">Send a text to get instant access.</p>
		</header>
		<section>
			<p class="gvm-price">
				<span data-gvm-bind-price></span>
				<span data-gvm-bind-currency></span>
			</p>
			<div class="gvm-qr">
				<span data-gvm-bind-qr></span>
			</div>
			<p
				class="gvm-status"
				data-gvm-bind-status
				data-gvm-bind-status-initializing="Preparing QR code"
				data-gvm-bind-status-waiting="Waiting for SMS"
				data-gvm-bind-status-duplicated="Already unlocked on this number"
				data-gvm-bind-status-resolved="Payment confirmed. Unlocking now..."
				data-gvm-bind-status-rejected="Payment could not be verified. Please try again."
			>
			</p>
			<p class="gvm-time">
				<span data-gvm-bind-time-remaining>00:00</span>
			</p>
			<div class="gvm-actions">
				<button data-gvm-bind-send-sms>Send SMS to unlock</button>
				<button data-gvm-bind-cancel>Cancel</button>
			</div>
		</section>
	</div>
</div>
