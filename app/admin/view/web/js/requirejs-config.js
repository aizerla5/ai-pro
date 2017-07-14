// +----------------------------------------------------------------------
// | AiCms - Shop
// +----------------------------------------------------------------------
// | Copyright (c) 2017
// +----------------------------------------------------------------------
// | Author: Ai Ye
// +----------------------------------------------------------------------
(function (require) {
    (function () {
        var config = {
            paths: {
                'jquery': 'js/jquery-2.2.4.min',
                'bootstrap': 'js/bootstrap.min',
                'nifty': 'js/nifty',
                'layer': 'js/layer/layer'
            },
            shim: {
                'jquery': {
                    exports: 'jquery'
                },
                'nifty': {
                    exports: "nifty"
                },
                'layer': {
                    exports: "layer"
                }
            }
        };
        require.config(config);
    })();

    /**
     * Plugins
     */
    (function () {
        var config = {
            paths: {
                'plugin/nano-scroll': 'plugins/nano-scroller/js/jquery.nanoscroller.min',
                'plugin/metismenu': 'plugins/metismenu/metisMenu.min',
                'plugin/resize-end': 'plugins/resize-end/jquery.resizeend.min',
                // form validator
                'plugin/validator': 'plugins/bootstrap-validator/js/bootstrapValidator.min',
                // masked-input
                'plugin/masked-input': 'plugins/masked-input/jquery.maskedinput.min'
            },
            shim: {
                'plugin/nano-scroll': {
                    deps: ['jquery']
                },
                'plugin/metismenu': {
                    deps: ['jquery']
                },
                'plugin/resize-end': {
                    deps: ['jquery']
                },
                'plugin/validator': {
                    deps: ['jquery']
                },
                'plugin/masked-input': {
                    deps: ['jquery']
                }
            }
        };
        require.config(config);
    })();

    /**
     * Demo
     */
    (function () {
        var config = {
            paths: {
                'bg-image': 'js/demo/bg-images'
            },
            shim: {
                'bg-image': {
                    deps: ['bootstrap']
                }
            }
        };
        require.config(config);
    })();
})(require);