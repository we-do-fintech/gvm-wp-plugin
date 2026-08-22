<dialog class="gvm-dialog" open>
	<style>
		.gvm-dialog {
			border: 0;
			border-radius: 14px;
			padding: 0;
			max-width: 420px;
			width: 92%;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
		}
		.gvm-dialog::backdrop {
			background: rgba(0, 0, 0, 0.5);
		}
		.gvm-payment {
			padding: 24px;
			font-family: inherit;
		}
		.gvm-payment h3 {
			margin: 0 0 4px;
		}
		.gvm-payment .gvm-price {
			font-size: 1.5rem;
			font-weight: 700;
		}
		.gvm-payment .gvm-qr {
			display: flex;
			justify-content: center;
			margin: 16px 0;
		}
		.gvm-payment .gvm-status {
			min-height: 1.2em;
			text-align: center;
			margin: 8px 0;
		}
		.gvm-payment .gvm-actions {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.gvm-payment button {
			width: 100%;
		}
	</style>
	<article class="gvm-payment">
		<header>
			<h3>Unlock in seconds</h3>
			<p>Send a text to get instant access.</p>
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
			></p>
			<div class="gvm-actions">
				<button data-gvm-bind-send-sms>Send SMS to unlock</button>
				<button data-gvm-bind-cancel>Cancel</button>
			</div>
		</section>
	</article>
</dialog>
