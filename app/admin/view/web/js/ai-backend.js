// +----------------------------------------------------------------------
// | AiCms - Shop
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
var CHARS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
var GLOBAL_LAYER_INDEX = 0;
var EL_CAPTCHA = '.captcha';
var CURRENT_FORM = null;
var FA_ICON = {
    valid: 'fa fa-check-circle fa-lg text-success',
    invalid: 'fa fa-times-circle fa-lg',
    validating: 'fa fa-refresh'
};
require([
    'jquery',
    'layer',
    'plugin/nano-scroll',
    'plugin/metismenu',
    'plugin/validator',
    'bg-image'
], function ($, layer) {
    /**
     * loading layer style path.
     */
    layer.config({
        path: baseUrl + '/js/layer/'
    });
    /** ------------------------------------------------------------------------------------ **/
    /** VARS ----------------------------------------------------------------------------- **/
    /** ------------------------------------------------------------------------------------ **/
    var navContainer = '#navbar-container',
        navTopLinks = navContainer + ' .navbar-top-links';

    /** ------------------------------------------------------------------------------------ **/
    /** FUNCTIONS --------------------------------------------------------------------- **/
    /** ------------------------------------------------------------------------------------ **/

    /**
     * refresh captcha
     */
    var refreshCaptcha = function () {
        if (CURRENT_FORM !== null && $(EL_CAPTCHA, CURRENT_FORM).size()) {
            $(EL_CAPTCHA, CURRENT_FORM).trigger('click');
            $('.captcha-text', CURRENT_FORM).val('');
            CURRENT_FORM = null;
        }
    };

    /**
     * before submit callback
     * @TODO
     * @returns {boolean}
     */
    var beforeSubmitCallback = function () {
        GLOBAL_LAYER_INDEX = layer.load(1, {
            shade: [0.25, '#000']
        });
        return true;
    };

    /**
     * success callback
     * @param data
     * @returns {boolean}
     */
    var successCallback = function (data) {
        var isRedirect = true;
        if (GLOBAL_LAYER_INDEX) {
            layer.close(GLOBAL_LAYER_INDEX);
        }
        if (CURRENT_FORM !== null) {
            if (CURRENT_FORM.find(EL_CAPTCHA).size()) {
                isRedirect = false;
            }
        }
        // success
        if (data.code === 1) {
            layer.alert(data.msg, {
                skin: 'layui-layer-molv',
                closeBtn: 0,
                anim: 1
            }, function (index) {
                layer.close(index);
                location.href = data.url;
            });
        } else {
            layer.alert(data.msg, {
                skin: 'layui-layer-lan',
                closeBtn: 0,
                anim: 2
            }, function (index) {
                layer.close(index);
                if (isRedirect) {
                    location.href = data.url;
                }
                refreshCaptcha();
            });
        }
        return false;
    };

    /**
     * random number
     * @param len
     * @returns {string}
     */
    var randNum = function (len) {
        if (typeof len === 'undefined') len = 10;
        var res = "";
        for (var i = 0; i < len; i++) {
            var id = Math.ceil(Math.random() * 35);
            res += CHARS[id];
        }
        return res;
    };

    /**
     * bind click events
     */
    if ($(EL_CAPTCHA).size()) {
        $(EL_CAPTCHA).attr('data-src', $(EL_CAPTCHA).attr('src'));
        $(EL_CAPTCHA).click(function () {
            var src = $(this).attr('data-src') + '?' + randNum();
            $(this).attr('src', src);
        });
    }

    /**
     * Listener all classes events.
     */
    var tooltip = $('.add-tooltip');
    if (tooltip.size()) tooltip.tooltip();
    var popover = $('.add-popover');
    if (popover.size()) popover.popover();

    $(navTopLinks).on('shown.bs.dropdown', '.dropdown', function () {
        $(this).find('.nano').nanoScroller({
            preventPageScrolling: true
        });
    });

    /**
     * ajax form with captcha
     */
    $('.ajaxForm').bootstrapValidator({
        excluded: [':disabled'],
        feedbackIcons: FA_ICON
    }).on('success.form.bv', function (e) {
        e.preventDefault();
        var $form = $(e.target);
        CURRENT_FORM = $form;
        beforeSubmitCallback();
        $.post($form.attr('action'), $form.serialize(), successCallback, 'json');
    });
});