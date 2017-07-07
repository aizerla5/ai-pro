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
                'nifty': 'js/nifty.min',
                'layer': 'js/layer/layer'
            },
            shim: {
                'jquery': {
                    exports: 'jquery'
                },
                'bootstrap': {
                    deps: ['jquery']
                },
                'nifty': {
                    deps: ['bootstrap']
                },
                'layer': {
                    deps: ['jquery'],
                    exports: "layer"
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

    /**
     * Plugins
     */
    (function () {
        var config = {
            paths: {
                // form validator
                'plugin/validator': 'plugins/bootstrap-validator/bootstrapValidator.min',
                // masked-input
                'plugin/masked-input': 'plugins/masked-input/jquery.maskedinput.min'
            },
            shim: {
                'plugin/validator': {
                    deps: ['bootstrap']
                },
                'plugin/masked-input': {
                    deps: ['bootstrap']
                }
            }
        };
        require.config(config);
    })();
})(require);