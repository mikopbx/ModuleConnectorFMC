/*
 * Copyright (C) MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Nikolay Beketov, 11 2018
 *
 */
const idUrl     = 'module-connector-f-m-c';
const idForm    = 'module-connectorfmc-form';
const className = 'ModuleConnectorFMC';
const inputClassName = 'mikopbx-module-input';

/* global globalRootUrl, globalTranslate, Form, Config, $ */
const ModuleConnectorFMC = {
	$formObj: $('#'+idForm),
	$checkBoxes: $('#'+idForm+' .ui.checkbox'),
	$dropDowns: $('#'+idForm+' .ui.dropdown'),
	$disabilityFields: $('#'+idForm+'  .disability'),

	/**
	 * Field validation rules
	 * https://semantic-ui.com/behaviors/form.html
	 */
	validateRules: {
		incomingEndpointPort: {
			identifier: 'incomingEndpointPort',
			rules: [
				{
					type: 'integer[1..65000]',
					prompt: globalTranslate.module_connectorfmc_incomingEndpointPortIsEmpty,
				},
			],
		},
		incomingEndpointLogin: {
			identifier: 'incomingEndpointLogin',
			rules: [
				{
					type   : 'empty',
					prompt: globalTranslate.module_connectorfmc_incomingEndpointLoginIsEmpty,
				},
			],
		},
		incomingEndpointSecret: {
			identifier: 'incomingEndpointSecret',
			rules: [
				{
					type   : 'empty',
					prompt: globalTranslate.module_connectorfmc_incomingEndpointSecretIsEmpty,
				},
			],
		},
		incomingEndpointHost: {
			identifier: 'incomingEndpointHost',
			rules: [
				{
					type   : 'empty',
					prompt: globalTranslate.module_connectorfmc_incomingEndpointHostIsEmpty,
				},
			],
		},
		sipPort: {
			identifier: 'sipPort',
			rules: [
				{
					type   : 'integer[5160..65000]',
					prompt: globalTranslate.module_connectorfmc_sipPortIsEmpty,
				},
			],
		},
		amiPort: {
			identifier: 'amiPort',
			rules: [
				{
					type   : 'integer[55000..60000]',
					prompt: globalTranslate.module_connectorfmc_amiPortIsEmpty,
				},
			],
		},
		rtpPortStart: {
			identifier: 'rtpPortStart',
			rules: [
				{
					type   : 'integer[40000..65000]',
					prompt: globalTranslate.module_connectorfmc_rtpPortStartIsEmpty,
				},
			],
		},
		rtpPortEnd: {
			identifier: 'rtpPortEnd',
			rules: [
				{
					type   : 'integer[40000..65000]',
					prompt: globalTranslate.module_connectorfmc_rtpPortEndIsEmpty,
				},
			],
		},
	},
	/**
	 * On page load we init some Semantic UI library
	 */
	initialize() {
		// инициализируем чекбоксы и выподающие менюшки
		window[className].$checkBoxes.checkbox();

		$('#useDelayedResponse').parent().checkbox()

		window[className].$dropDowns.dropdown();
		$('.menu .item').tab();
		$(".accordion").accordion();
		window[className].initializeForm();

		$('#outputEndpoint').parent().find('i').on('click', function() {
			$(this).parent().find('input').attr('value', 'SIP-FMC-'+window[className].generateUID(8).toUpperCase());
			$(this).parent().find('input').trigger('change');
		});
		$('#outputEndpointSecret').parent().find('i').on('click', function() {
			$(this).parent().find('input').val(window[className].generateUID(32));
			$(this).parent().find('input').trigger('change');
		});
	},

	/**
	 * Create new password
	 * @param length
	 * @returns {string}
	 */
	generateUID(length) {
		const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		let password = "";
		for (let i = 0; i < length; i++) {
			const randomIndex = Math.floor(Math.random() * charset.length);
			password += charset[randomIndex];
		}
		return password;
	},
	/**
	 * We can modify some data before form send
	 * @param settings
	 * @returns {*}
	 */
	cbBeforeSendForm(settings) {
		const result = settings;
		result.data = window[className].$formObj.form('get values');
		result.data.extensions = result.data.peers.join(',');
		delete result.data.peers;
		return result;
	},
	/**
	 * Some actions after forms send
	 */
	cbAfterSendForm() {
	},
	/**
	 * Initialize form parameters
	 */
	initializeForm() {
		Form.$formObj = window[className].$formObj;
		Form.url = `${globalRootUrl}${idUrl}/save`;
		Form.validateRules = window[className].validateRules;
		Form.cbBeforeSendForm = window[className].cbBeforeSendForm;
		Form.cbAfterSendForm = window[className].cbAfterSendForm;
		Form.initialize();
	},
};

$(document).ready(() => {
	window[className].initialize();
});

