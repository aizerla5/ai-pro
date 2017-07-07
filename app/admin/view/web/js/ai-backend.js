// +----------------------------------------------------------------------
// | AiCms - Shop
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
var CHARS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
var GLOBAL_LAYER_INDEX = 0;
var EL_CAPTCHA = '#captcha';
var FA_ICON = {
    valid: 'fa fa-check-circle fa-lg text-success',
    invalid: 'fa fa-times-circle fa-lg',
    validating: 'fa fa-refresh'
};
require([
    'jquery',
    'layer',
    'nifty',
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
    var effectList = 'easeInQuart easeOutQuart easeInBack easeOutBack easeInOutBack steps jumping rubber';
    var niftyContainer = $('#container'),
        niftyMainNav = $('#mainnav-container'),
        niftyAside = $('#aside-container');

    // Refresh the aside, to enable or disable the "Bootstrap Affix" when the navbar in a "static position".
    niftyMainNav.niftyAffix('update');
    niftyAside.niftyAffix('update');

    var navigationMode = function () {
        var query = window.location.search.substring(1);
        var vars = query.split("&");
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split("=");
            if (pair[0] === "offcanvas") {
                return pair[1];
            }
        }
        return (false);
    }();
    if (navigationMode === "push" || navigationMode === "slide" || navigationMode === "reveal") {
        $('.mainnav-toggle').removeClass('push slide reveal').addClass(navigationMode);
        niftyContainer.removeClass('mainnav-lg mainnav-sm').addClass('mainnav-out ' + navigationMode);
    }
    /** ------------------------------------------------------------------------------------ **/

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
        if (GLOBAL_LAYER_INDEX) {
            layer.close(GLOBAL_LAYER_INDEX);
        }
        // success
        if (data.code === 1) {
            layer.alert(data.msg, {
                skin: 'layui-layer-molv',
                closeBtn: 0,
                anim: 1
            }, function (index) {
                layer.close(index);
                if (data.url !== undefined) {
                    location.href = data.url;
                }
            });
        } else {
            // click to refresh & set empty value.
            if ($(EL_CAPTCHA).size()) {
                $(EL_CAPTCHA).val('').trigger('click');
            }
            layer.alert(data.msg, {
                skin: 'layui-layer-lan',
                closeBtn: 0,
                anim: 2
            }, function (index) {
                layer.close(index);
                if (data.url !== undefined) {
                    location.href = data.url;
                }
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
     * ajax form with captcha
     */
    $('.ajaxForm').bootstrapValidator({
        excluded: [':disabled'],
        feedbackIcons: FA_ICON
    }).on('success.form.bv', function (e) {
        e.preventDefault();
        var $form = $(e.target);
        beforeSubmitCallback();
        $.post($form.attr('action'), $form.serialize(), successCallback, 'json');
    });
});