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
     * Plugins
     */
    (function () {
        var config = {
            paths: {
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
                'plugin/validator': {
                    deps: ['bootstrap']
                },
                'plugin/masked-input': {
                    deps: ['bootstrap']
                },
                'plugin/charts': {
                    deps: ['nifty']
                },
                'plugin/charts-resize': {
                    deps: ['plugin/charts']
                },
                'plugin/charts-pipe': {
                    deps: ['plugin/charts-resize']
                },
                'plugin/gauge': {
                    deps: ['nifty']
                },
                'plugin/morris': {
                    deps: ['nifty']
                },
                'plugin/raphael': {
                    deps: ['nifty']
                },
                'plugin/spark-line': {
                    deps: ['nifty']
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