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
                'plugin/nano-scroll': 'plugins/nanoScroller/js/jquery.nanoscroller.min',
                'plugin/metis-menu': 'plugins/metisMenu/metisMenu.min',
                // form validator
                'plugin/validator': 'plugins/bootstrap-validator/bootstrapValidator.min',
                // masked-input
                'plugin/masked-input': 'plugins/masked-input/jquery.maskedinput.min',
                // charts
                'plugin/charts': 'plugins/flot-charts/jquery.flot.min',
                'plugin/charts-resize': 'plugins/flot-charts/jquery.flot.resize.min',
                'plugin/charts-pipe': 'plugins/easy-pie-chart/jquery.easypiechart.min',
                // gauge
                'plugin/gauge': 'plugins/gauge-js/gauge.min',
                'plugin/morris': 'plugins/morris-js/morris.min',
                'plugin/raphael': 'plugins/morris-js/raphael-js/raphael.min',
                'plugin/spark-line': 'plugins/sparkline/jquery.sparkline.min'
            },
            shim: {
                'plugin/nano-scroll': {
                    deps: ['jquery']
                },
                'plugin/metis-menu': {
                    deps: ['jquery']
                },
                'plugin/validator': {
                    deps: ['jquery']
                },
                'plugin/masked-input': {
                    deps: ['jquery']
                },
                'plugin/charts': {
                    deps: ['jquery']
                },
                'plugin/charts-resize': {
                    deps: ['jquery']
                },
                'plugin/charts-pipe': {
                    deps: ['jquery']
                },
                'plugin/gauge': {
                    deps: ['jquery']
                },
                'plugin/morris': {
                    deps: ['jquery']
                },
                'plugin/raphael': {
                    deps: ['jquery']
                },
                'plugin/spark-line': {
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