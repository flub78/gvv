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

            var year = parseInt($('[name="year"]').val());
            var first_year = parseInt($('[name="first_year"]').val());
            // alert("year=" + year + ", first_year=" + first_year);
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
                        
            var series = new Array();
            $('[name="machines[]"] ').each(function () {
                var id = $(this).val();
                series.push({label : id});
            });

            // 'Cumul annuel des heures de vol par machine'
            $.jqplot('chartdiv', jsonurl, {
                title : $('[name="title"]').val(),
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

                // axes:{yaxis:{min:-10, max:240}},
                // axes:{yaxis:{renderer: $.jqplot.LogAxisRenderer}},
                // series:[{color:'#5FAB78', renderer:$.jqplot.BarRenderer,
                // }],
                seriesColors : ["#FF0000", "#4bb2c5", "#c5b47f", "#EAA228", "#579575",
                        "#839557", "#958c12", "#953579", "#4b5de4", "#d8b83f",
                        "#ff5800", "#0085cc" ],
                        
                axes: {
                    xaxis: {
                        tickInterval: 1, 
                        tickOptions: {
                            formatString: "%d"
                    }}
                },
                        
                cursor:{show: true, zoom:true},
                
                highlighter : {
                    show : true,
                    showLabel : true,
                    tooltipAxes : 'y',
                    sizeAdjust : 10,
                    tooltipLocation : 'nw',
                    formatString: '#serieLabel# = %d'
                },
                

            });
        });
