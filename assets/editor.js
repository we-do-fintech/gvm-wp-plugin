/**
 * Gutenberg integration for gvm-wp-plugin.
 *
 * - Registers the "GetViaMsg Paywall" block (server-rendered, inline template).
 * - Registers the "GetViaMsg Paid download" block.
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

	var PluginDocumentSettingPanel = (wp.editPost && wp.editPost.PluginDocumentSettingPanel) || (wp.editor && wp.editor.PluginDocumentSettingPanel);
	var registerPlugin = wp.plugins && wp.plugins.registerPlugin;

	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;

	var config = window.gvmEditorConfig || { defaults: {} };
	var DEFAULTS = config.defaults || {};
	var CATEGORIES = config.categories || [];

	var HIDE_STRATEGY = DEFAULTS.hideStrategy || 'mangle-blur';
	var POST_CATEGORY = DEFAULTS.category || 'article';
	var DOWNLOAD_CATEGORY = DEFAULTS.downloadCategory || 'report_pdf';

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
		category: '_gvm_category'
	};

	var CATEGORY_SEQ = 0;

	var STRATEGIES = [
		{ value: 'none', label: __('None', 'gvm-wp') },
		{ value: 'hide', label: __('Hide', 'gvm-wp') },
		{ value: 'blur', label: __('Blur', 'gvm-wp') },
		{ value: 'mangle-blur', label: __('Mangle blur', 'gvm-wp') }
	];

	/* ------------------------------------------------------------------ *
	 * Shared controls
	 * ------------------------------------------------------------------ */

	function CategoryControl(props) {
		var listRef = wp.element.useRef(null);
		if (!listRef.current) {
			CATEGORY_SEQ++;
			listRef.current = 'gvm-category-list-' + CATEGORY_SEQ;
		}

		var listId = listRef.current;
		var value = props.value || '';

		return el('div', { className: 'components-base-control' },
			el('label', { className: 'components-base-control__label' }, __('Category', 'gvm-wp')),
			el('input', {
				className: 'components-text-control__input',
				type: 'text',
				list: listId,
				value: value,
				placeholder: props.placeholder || POST_CATEGORY,
				onChange: function (event) { props.onChange(event.target.value); }
			}),
			el('datalist', { id: listId },
				CATEGORIES.map(function (category) {
					return el('option', { key: category.name, value: category.name },
						category.prettyName || category.name);
				})
			),
			props.help ? el('p', { className: 'components-base-control__help' }, props.help) : null
		);
	}

	function ConditionControl(props) {
		return el(TextControl, {
			label: __('Condition', 'gvm-wp'),
			value: props.value || '',
			onChange: props.onChange,
			help: __('Optional gvm condition (data-gvm-cond), applied at page level. Evaluated last, at the very end of the settings.', 'gvm-wp')
		});
	}

	function HideDetailControls(props) {
		var atts = props.attributes;
		var setAttributes = props.setAttributes;

		return el(PanelBody, { title: __('Advanced — how much to hide', 'gvm-wp'), initialOpen: false },
			el(TextControl, {
				label: __('Hide after sections', 'gvm-wp'),
				type: 'number',
				value: atts.hide_sections,
				onChange: function (value) { setAttributes({ hide_sections: parseInt(value, 10) || 0 }); },
				help: __('Keep the first N content blocks visible.', 'gvm-wp')
			}),
			el(TextControl, {
				label: __('Hide after percent (1-100)', 'gvm-wp'),
				type: 'number',
				value: atts.hide_percent,
				onChange: function (value) { setAttributes({ hide_percent: parseInt(value, 10) || 0 }); }
			}),
			el(TextControl, {
				label: __('Hide after words', 'gvm-wp'),
				type: 'number',
				value: atts.hide_words,
				onChange: function (value) { setAttributes({ hide_words: parseInt(value, 10) || 0 }); },
				help: __('Only one is applied, in this order: sections, percent, words.', 'gvm-wp')
			})
		);
	}

	/* ------------------------------------------------------------------ *
	 * Block registration
	 * ------------------------------------------------------------------ */

	registerBlockType('gvm/paywall', {
		title: __('GetViaMsg — Paid content', 'gvm-wp'),
		description: __('Wrap content in an inline GetViaMsg paywall.', 'gvm-wp'),
		icon: 'lock',
		category: 'common',
		supports: { html: false, reusable: false },
		attributes: {
			price: { type: 'string', default: '' },
			hide_strategy: { type: 'string', default: HIDE_STRATEGY },
			hide_percent: { type: 'integer', default: 0 },
			hide_sections: { type: 'integer', default: 6 },
			hide_words: { type: 'integer', default: 0 },
			reference: { type: 'string', default: '' },
			title: { type: 'string', default: '' },
			cond: { type: 'string', default: '' },
			category: { type: 'string', default: POST_CATEGORY }
		},
		edit: function (props) {
			var blockProps = useBlockProps();
			var atts = props.attributes;
			var setAttributes = props.setAttributes;

			return el('div', blockProps,
				el(InspectorControls, {},
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
							onChange: function (value) { setAttributes({ reference: value }); },
							help: __('Leave empty to auto-generate from slug or post ID. 3-59 characters.', 'gvm-wp')
						}),
						el(TextControl, {
							label: __('Title', 'gvm-wp'),
							value: atts.title,
							onChange: function (value) { setAttributes({ title: value }); }
						}),
						el(SelectControl, {
							label: __('Hide strategy', 'gvm-wp'),
							value: atts.hide_strategy,
							options: STRATEGIES,
							onChange: function (value) { setAttributes({ hide_strategy: value }); }
						}),
						el(CategoryControl, {
							value: atts.category,
							placeholder: POST_CATEGORY,
							onChange: function (value) { setAttributes({ category: value }); },
							help: __('Category sent with the commitment (data-gvm-category, max 64 chars).', 'gvm-wp')
						}),
						el(ConditionControl, {
							value: atts.cond,
							onChange: function (value) { setAttributes({ cond: value }); }
						})
					),
					el(HideDetailControls, props)
				),
				el('div', { className: 'gvm-block-placeholder' },
					el('p', { className: 'gvm-block-notice' }, __('Content below is wrapped in an inline GetViaMsg paywall.', 'gvm-wp')),
					el(InnerBlocks)
				)
			);
		},
		save: function () {
			return null;
		}
	});

	registerBlockType('gvm/download', {
		title: __('GetViaMsg — Paid download', 'gvm-wp'),
		description: __('Sell a file download via GetViaMsg.', 'gvm-wp'),
		icon: 'download',
		category: 'common',
		supports: { html: false, reusable: false },
		attributes: {
			file: { type: 'string', default: '' },
			price: { type: 'string', default: '' },
			reference: { type: 'string', default: '' },
			cond: { type: 'string', default: '' },
			category: { type: 'string', default: DOWNLOAD_CATEGORY }
		},
		edit: function (props) {
			var blockProps = useBlockProps();
			var atts = props.attributes;
			var setAttributes = props.setAttributes;

			return el('div', blockProps,
				el(InspectorControls, {},
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
							label: __('File', 'gvm-wp'),
							value: atts.file,
							onChange: function (value) { setAttributes({ file: value }); },
							help: __('Filename in uploads/gvm/.', 'gvm-wp')
						}),
						el(DownloadUpload, {
							onUploaded: function (filename) { setAttributes({ file: filename }); }
						}),
						el(TextControl, {
							label: __('Reference', 'gvm-wp'),
							value: atts.reference,
							onChange: function (value) { setAttributes({ reference: value }); },
							help: __('Optional. Overrides the auto-generated reference (post reference + file name). 3-59 characters; required if the file name is long.', 'gvm-wp')
						}),
						el(CategoryControl, {
							value: atts.category,
							placeholder: DOWNLOAD_CATEGORY,
							onChange: function (value) { setAttributes({ category: value }); },
							help: __('Category sent with the commitment (data-gvm-category, max 64 chars).', 'gvm-wp')
						}),
						el(ConditionControl, {
							value: atts.cond,
							onChange: function (value) { setAttributes({ cond: value }); }
						})
					)
				),
				el('div', { className: 'gvm-block-placeholder' },
					el('p', { className: 'gvm-block-notice' }, __('Paid file download via GetViaMsg.', 'gvm-wp'))
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
			el('label', { className: 'components-button is-secondary gvm-upload-label' },
				__('Upload file', 'gvm-wp'),
				el('input', { type: 'file', style: { display: 'none' }, onChange: onChange })
			),
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
		var category = useMetaValue(META.category);

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
				el(SelectControl, {
					label: __('Hide strategy', 'gvm-wp'),
					value: hideStrategy || HIDE_STRATEGY,
					options: STRATEGIES,
					onChange: function (value) { setMeta(META.hide_strategy, value); }
				}),
				el(TextControl, {
					label: __('Reference', 'gvm-wp'),
					value: reference || '',
					onChange: function (value) { setMeta(META.reference, value); },
					help: __('Leave empty to auto-generate from slug or post ID. 3-59 characters.', 'gvm-wp')
				}),
				el(CategoryControl, {
					value: category || POST_CATEGORY,
					placeholder: POST_CATEGORY,
					onChange: function (value) { setMeta(META.category, value); },
					help: __('Category sent with the commitment (data-gvm-category, max 64 chars).', 'gvm-wp')
				}),
				el(ConditionControl, {
					value: cond || '',
					onChange: function (value) { setMeta(META.cond, value); }
				})
			),
			el(PanelBody, { title: __('Advanced — how much to hide', 'gvm-wp'), initialOpen: false },
				el(TextControl, {
					label: __('Hide after sections', 'gvm-wp'),
					type: 'number',
					value: hideSections ? String(hideSections) : '',
					onChange: function (value) { setMeta(META.hide_sections, value === '' ? null : parseInt(value, 10)); },
					help: __('Keep the first N content blocks visible.', 'gvm-wp')
				}),
				el(TextControl, {
					label: __('Hide after percent (1-100)', 'gvm-wp'),
					type: 'number',
					value: hidePercent ? String(hidePercent) : '',
					onChange: function (value) { setMeta(META.hide_percent, value === '' ? null : parseInt(value, 10)); }
				}),
				el(TextControl, {
					label: __('Hide after words', 'gvm-wp'),
					type: 'number',
					value: hideWords ? String(hideWords) : '',
					onChange: function (value) { setMeta(META.hide_words, value === '' ? null : parseInt(value, 10)); },
					help: __('Only one is applied, in this order: sections, percent, words.', 'gvm-wp')
				})
			)
		);
	}

	if (PluginDocumentSettingPanel && registerPlugin) {
		registerPlugin('gvm-paywall-panel', { render: DocumentPanel });
	}
})(window.wp);