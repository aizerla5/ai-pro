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
                'nifty': 'js/nifty.min'
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
            }
        };
        require.config(config);
    })();
})(require);