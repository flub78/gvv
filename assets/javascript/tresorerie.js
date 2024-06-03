/**
 * GVV Gestion vol à voile Copyright (C) 2011 Philippe Boissel & Frédéric
 * Peignot
 * 
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later
 * version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http: *www.gnu.org/licenses/>.
 * 
 * @package javascript
 * 
 * Fonctions Javascript d'affichage des cumuls
 * 
 */

$(document).ready(
        function() {

            var year = $('[name="year"]').val();
            var first_year = $('[name="first_year"]').val();
            var jsonurl = $('[name="jsonurl"]').val();
                        
            // Our ajax data renderer which here retrieves a text file.
            // it could contact any source and pull data, however.
            // The options argument isn't used in this renderer.
            var ajaxDataRenderer = function(url, plot, options) {
                var ret = null;
                $.ajax({
                    // have to use synchronous here, else the function
                    // will return before the data is fetched
                    async : false,
                    url : url,
                    dataType : "json",
                    success : function(data) {
                        ret = data;
                    }
                });
                return ret;
            };

            // Can specify a custom tick Array.
            // Ticks should match up one for each y value (category) in the
            // series.                        
            var series = 
                [{label : label_cumul_depenses}, 
                 {label : label_cumul_recettes}, 
                 {label : label_depenses, renderer: $.jqplot.BarRenderer,
                     rendererOptions: {
                         // Speed up the animation a little bit.
                         // This is a number of milliseconds. 
                         // Default for bar series is 3000. 
                         animation: {
                             speed: 2500
                         },
                         barWidth: 15,label_recettes,
                         barPadding: -20,
                         barMargin: 0,
                         highlightMouseOver: false
                     }}, 
                 {label : label_recettes, renderer: $.jqplot.BarRenderer,
                         rendererOptions: {
                             // Speed up the animation a little bit.
                             // This is a number of milliseconds. 
                             // Default for bar series is 3000. 
                             animation: {
                                 speed: 2500
                             },
                             barWidth: 15,
                             barPadding: -10,
                             barMargin: 0,
                             highlightMouseOver: false
                         }}];

            $.jqplot('chartdiv', jsonurl, {
                title : title_cumul,
                stackSeries : false,
                animate : true,

                dataRenderer : ajaxDataRenderer,
                dataRendererOptions : {
                    unusedOptionalUrl : jsonurl
                },

                seriesDefaults : {
                    showMarker : true, // show the dots on the lines
                    shadow : true, // show shadow or not.
                    shadowAngle : 45, // angle (degrees) of the shadow,
                    // clockwise from x axis.
                    shadowOffset : 1.25, // offset from the line of the
                    // shadow.
                    shadowDepth : 3, // Number of strokes to make when
                    // drawing shadow. Each
                    // stroke offset by shadowOffset from the last.
                    shadowAlpha : 0.1, // Opacity of the shadow.

                },

                // Custom labels for the series are specified with the "label"
                // option on the series option. Here a series option object
                // is specified for each series.
                series : series,
                // Show the legend and put it outside the grid, but inside the
                // plot container, shrinking the grid to accomodate the legend.
                // A value of "outside" would not shrink the grid and allow
                // the legend to overflow the container.
                legend : {
                    show : true,
                    placement : 'outsideGrid'
                },

                axes : {
                    // Use a category axis on the x axis and use our custom
                    // ticks.
                    xaxis : {
                        renderer : $.jqplot.CategoryAxisRenderer,
                        ticks : months
                    },
                    // Pad the y axis just a little so bars can get close to,
                    // but
                    // not touch, the grid boundaries. 1.2 is the default
                    // padding.
                    yaxis : {
                        pad : 1.2,
                        tickOptions : {
                            formatString : '%d'
                        }, min : 0
                    }
                },

                // axes:{yaxis:{min:-10, max:240}},
                // axes:{yaxis:{renderer: $.jqplot.LogAxisRenderer}},
                // series:[{color:'#5FAB78', renderer:$.jqplot.BarRenderer,
                // }],
                seriesColors : [ "#EAA228", "#4bb2c5", "#EAA228", "#4bb2c5", "#c5b47f", "#579575",
                        "#839557", "#958c12", "#953579", "#4b5de4", "#d8b83f",
                        "#ff5800", "#0085cc" ],
                        
                highlighter : {
                    show : true,
                    showLabel : true,
                    tooltipAxes : 'y',
                    sizeAdjust : 10,
                    tooltipLocation : 'nw'
                },

            });
        });
