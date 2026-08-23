/**
 * Gutenberg integration for gvm-wp-plugin.
 *
 * - Registers the "GetViaMsg Paywall" block (server-rendered).
 * - Adds a "GetViaMsg Paywall" document sidebar panel editing post meta.
 *
 * No build step: plain ES, WordPress globals.
 */
(function (wp) {
	'use strict';

	if (!wp || !wp.blocks) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var useRef = wp.element.useRef;
	var useState = wp.element.useState;

	var registerBlockType = wp.blocks.registerBlockType;
	var InnerBlocks = wp.blockEditor.InnerBlocks;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;

	var PanelBody = wp.components.PanelBody;
	var PanelRow = wp.components.PanelRow;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;

	var PluginDocumentSettingPanel = (wp.editPost && wp.editPost.PluginDocumentSettingPanel) || (wp.editor && wp.editor.PluginDocumentSettingPanel);
	var registerPlugin = wp.plugins && wp.plugins.registerPlugin;

	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;

	var config = window.gvmEditorConfig || { defaults: {} };

	var META = {
		enabled: '_gvm_enabled',
		price: '_gvm_price',
		hide_strategy: '_gvm_hide_strategy',
		hide_percent: '_gvm_hide_percent',
		hide_sections: '_gvm_hide_sections',
		hide_words: '_gvm_hide_words',
		reference: '_gvm_reference',
		cond: '_gvm_cond',
		redirect: '_gvm_redirect',
		download: '_gvm_download'
	};

	var STRATEGIES = [
		{ value: 'none', label: __('None', 'gvm-wp') },
		{ value: 'hide', label: __('Hide', 'gvm-wp') },
		{ value: 'blur', label: __('Blur', 'gvm-wp') },
		{ value: 'mangle-blur', label: __('Mangle blur', 'gvm-wp') }
	];

	/* ------------------------------------------------------------------ *
	 * Shared block attribute controls
	 * ------------------------------------------------------------------ */

	function BlockControls(props) {
		var atts = props.attributes;
		var setAttributes = props.setAttributes;

		return el(InspectorControls, {},
			el(PanelBody, { title: __('Paywall settings', 'gvm-wp'), initialOpen: true },
				el(TextControl, {
					label: __('Price', 'gvm-wp'),
					type: 'number',
					step: '0.01',
					min: '0.01',
					max: '10',
					value: atts.price,
					onChange: function (value) { setAttributes({ price: value }); }
				}),
				el(TextControl, {
					label: __('Reference', 'gvm-wp'),
					value: atts.reference,
					onChange: function (value) { setAttributes({ reference: value }); }
				}),
				el(TextControl, {
					label: __('Title', 'gvm-wp'),
					value: atts.title,
					onChange: function (value) { setAttributes({ title: value }); }
				}),
				el(TextControl, {
					label: __('Condition', 'gvm-wp'),
					value: atts.cond,
					onChange: function (value) { setAttributes({ cond: value }); },
					help: __('Optional gvm condition (data-gvm-cond), applied at page level.', 'gvm-wp')
				}),
				el(SelectControl, {
					label: __('Hide strategy', 'gvm-wp'),
					value: atts.hide_strategy,
					options: STRATEGIES,
					onChange: function (value) { setAttributes({ hide_strategy: value }); }
				}),
				el(TextControl, {
					label: __('Hide sections', 'gvm-wp'),
					type: 'number',
					value: atts.hide_sections,
					onChange: function (value) { setAttributes({ hide_sections: parseInt(value, 10) || 0 }); }
				}),
				el(TextControl, {
					label: __('Hide percent (1-100)', 'gvm-wp'),
					type: 'number',
					value: atts.hide_percent,
					onChange: function (value) { setAttributes({ hide_percent: parseInt(value, 10) || 0 }); }
				}),
				el(TextControl, {
					label: __('Hide words', 'gvm-wp'),
					type: 'number',
					value: atts.hide_words,
					onChange: function (value) { setAttributes({ hide_words: parseInt(value, 10) || 0 }); }
				})
			)
		);
	}

	/* ------------------------------------------------------------------ *
	 * Block registration
	 * ------------------------------------------------------------------ */

	registerBlockType('gvm/paywall', {
		title: __('GetViaMsg Paywall', 'gvm-wp'),
		description: __('Wrap content in a GetViaMsg paywall.', 'gvm-wp'),
		icon: 'lock',
		category: 'common',
		supports: { html: false, reusable: false },
		attributes: {
			price: { type: 'string', default: '' },
			hide_strategy: { type: 'string', default: 'hide' },
			hide_percent: { type: 'integer', default: 0 },
			hide_sections: { type: 'integer', default: 6 },
			hide_words: { type: 'integer', default: 0 },
			reference: { type: 'string', default: '' },
			title: { type: 'string', default: '' },
			cond: { type: 'string', default: '' }
		},
		edit: function (props) {
			var blockProps = useBlockProps();

			return el('div', blockProps,
				el(BlockControls, props),
				el('div', { className: 'gvm-block-placeholder' },
					el('p', { className: 'gvm-block-notice' }, __('Content below is wrapped in a GetViaMsg paywall.', 'gvm-wp')),
					el(InnerBlocks)
				)
			);
		},
		save: function () {
			return null;
		}
	});

	/* ------------------------------------------------------------------ *
	 * Document sidebar panel (per-article post meta)
	 * ------------------------------------------------------------------ */

	function useMetaValue(key) {
		return useSelect(function (select) {
			var meta = select('core/editor').getEditedPostAttribute('meta') || {};
			return meta[key];
		}, [key]);
	}

	function DownloadUpload(props) {
		var inputRef = useRef(null);
		var status = useState('');
		var setStatus = status[1];
		var uploadCfg = (config.upload || {});

		function onChange(e) {
			var file = e.target.files && e.target.files[0];
			if (!file) {
				return;
			}

			var fd = new FormData();
			fd.append('action', uploadCfg.action || 'gvm_upload_download');
			fd.append('nonce', uploadCfg.nonce);
			fd.append('file', file);

			setStatus(__('Uploading…', 'gvm-wp'));

			fetch(uploadCfg.ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res && res.success) {
						props.onUploaded(res.data.filename);
						setStatus(__('Uploaded', 'gvm-wp'));
					} else {
						setStatus((res && res.data && res.data.message) || __('Upload failed', 'gvm-wp'));
					}
				})
				.catch(function () { setStatus(__('Upload failed', 'gvm-wp')); })
				.finally(function () { e.target.value = ''; });
		}

		return el('div', { className: 'gvm-upload-row' },
			el('input', { type: 'file', ref: inputRef, style: { display: 'none' }, onChange: onChange }),
			el(Button, { isSecondary: true, onClick: function () { inputRef.current.click(); } }, __('Upload file', 'gvm-wp')),
			status[0] ? el('p', { className: 'description' }, status[0]) : null
		);
	}

	function DocumentPanel() {
		var editPost = useDispatch('core/editor').editPost;

		function setMeta(key, value) {
			var update = {};
			update[key] = value;
			editPost({ meta: update });
		}

		var enabled = useMetaValue(META.enabled);
		var price = useMetaValue(META.price);
		var hideStrategy = useMetaValue(META.hide_strategy);
		var hideSections = useMetaValue(META.hide_sections);
		var hidePercent = useMetaValue(META.hide_percent);
		var hideWords = useMetaValue(META.hide_words);
		var reference = useMetaValue(META.reference);
		var cond = useMetaValue(META.cond);
		var redirect = useMetaValue(META.redirect);
		var download = useMetaValue(META.download);

		return el(PluginDocumentSettingPanel, {
			name: 'gvm-paywall-panel',
			title: __('GetViaMsg Paywall', 'gvm-wp'),
			className: 'gvm-panel'
		},
			el(PanelBody, { initialOpen: true },
				el(PanelRow, {},
					el(ToggleControl, {
						label: __('Enable paywall', 'gvm-wp'),
						checked: !!enabled,
						onChange: function (value) { setMeta(META.enabled, value); }
					})
				),
				el(PanelRow, {},
					el(ToggleControl, {
						label: __('Redirect after payment', 'gvm-wp'),
						checked: !!redirect,
						onChange: function (value) { setMeta(META.redirect, value); },
						help: __('Show a teaser, then redirect to a verified URL that renders the full article server-side.', 'gvm-wp')
					})
				),
				el(TextControl, {
					label: __('Price', 'gvm-wp'),
					type: 'number',
					step: '0.01',
					min: '0.01',
					max: '10',
					value: price ? String(price) : '',
					onChange: function (value) { setMeta(META.price, value); }
				}),
				el(TextControl, {
					label: __('Download file', 'gvm-wp'),
					value: download || '',
					onChange: function (value) { setMeta(META.download, value); },
					help: __('Protected file (in uploads/gvm/) to sell via download. Empty = download strategy disabled.', 'gvm-wp')
				}),
				el(DownloadUpload, {
					onUploaded: function (filename) { setMeta(META.download, filename); }
				}),
				el(SelectControl, {
					label: __('Hide strategy', 'gvm-wp'),
					value: hideStrategy || 'hide',
					options: STRATEGIES,
					onChange: function (value) { setMeta(META.hide_strategy, value); }
				}),
				el(TextControl, {
					label: __('Hide sections', 'gvm-wp'),
					type: 'number',
					value: hideSections ? String(hideSections) : '',
					onChange: function (value) { setMeta(META.hide_sections, value === '' ? null : parseInt(value, 10)); }
				}),
				el(TextControl, {
					label: __('Hide percent (1-100)', 'gvm-wp'),
					type: 'number',
					value: hidePercent ? String(hidePercent) : '',
					onChange: function (value) { setMeta(META.hide_percent, value === '' ? null : parseInt(value, 10)); }
				}),
				el(TextControl, {
					label: __('Hide words', 'gvm-wp'),
					type: 'number',
					value: hideWords ? String(hideWords) : '',
					onChange: function (value) { setMeta(META.hide_words, value === '' ? null : parseInt(value, 10)); }
				}),
				el(TextControl, {
					label: __('Reference', 'gvm-wp'),
					value: reference || '',
					onChange: function (value) { setMeta(META.reference, value); },
					help: __('Leave empty to auto-generate from slug or post ID.', 'gvm-wp')
				}),
				el(TextControl, {
					label: __('Condition', 'gvm-wp'),
					value: cond || '',
					onChange: function (value) { setMeta(META.cond, value); },
					help: __('Optional gvm condition (data-gvm-cond), applied at page level.', 'gvm-wp')
				})
			)
		);
	}

	if (PluginDocumentSettingPanel && registerPlugin) {
		registerPlugin('gvm-paywall-panel', { render: DocumentPanel });
	}
})(window.wp);
