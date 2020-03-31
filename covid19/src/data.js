/*
 * Copyright 2005-2020 Centreon
 * Centreon is developed by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give CENTREON
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of CENTREON choice, provided that
 * CENTREON also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

// Load Page
function loadPage()
{
    let frameheight = 400;
    parent.iResize(window.name, frameheight);
    if (autoRefresh) {
        if (timeout) {
            clearTimeout(timeout);
        }
        timeout = setTimeout(loadPage, (autoRefresh * 1000));
    }
}

let widgetId = "{$widgetId}";
let autoRefresh = "{$autoRefresh}";
let timeout;



function debug() {
    var options = {
        series: [76, 67, 61, 90],
        chart: {
            height: 390,
            type: 'radialBar',
        },
        plotOptions: {
            radialBar: {
                offsetY: 0,
                startAngle: 0,
                endAngle: 270,
                hollow: {
                    margin: 5,
                    size: '30%',
                    background: 'transparent',
                    image: undefined,
                },
                dataLabels: {
                    name: {
                        show: false,
                    },
                    value: {
                        show: false,
                    }
                }
            }
        },
        colors: ['#1ab7ea', '#0084ff', '#39539E', '#0077B5'],
        labels: ['Vimeo', 'Messenger', 'Facebook', 'LinkedIn'],
        legend: {
            show: true,
            floating: true,
            fontSize: '16px',
            position: 'left',
            offsetX: 160,
            offsetY: 15,
            labels: {
                useSeriesColors: true,
            },
            markers: {
                size: 0
            },
            formatter: function(seriesName, opts) {
                return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex]
            },
            itemMargin: {
                vertical: 3
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    show: false
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();
}


jQuery(function () {
    loadPage();
    console.log(labelTitles);
});




























/* -------
    V2 ->
 */
    let customScale = "{$ratio}";
console.log(customScale);


let countryName = "Data for : {$data.country}";
let labelTitles = {$titles};
let dataValues = {$values};
let userPalette = "{$userPalette}";

{literal}
let options = {
    title: {
        text: countryName,
        align: 'Center',
        margin: 10,
        floating: true,
        style: {
            fontSize:  '16px',
            fontWeight:  'bold',
            color:  '#263238'
        },
    },
    chart: {
        height: 380,
        type: 'radialBar',
    },
    plotOptions: {
        radialBar: {
            offsetY: 0,
            startAngle: 0,
            endAngle: 270,
            hollow: {
                margin: 5,
                size: '30%',
                background: 'transparent',
                image: undefined,
            },
            dataLabels: {
                name: {
                    show: true,
                },
                value: {
                    show: true,
                    formatter: function(val) {
                        return (Math.round(val / customScale))
                    },
                }
            }
        }
    },
    legend: {
        show: true,
        floating: true,
        fontSize: '14px',
        position: 'left',
        offsetX: 135,
        offsetY: 30,
        itemMargin: {
            vertical: 15
        },
        labels: {
            useSeriesColors: true,
        },
        /*formatter: function(seriesName, opts) {
            let revertScale = Math.round(opts.w.globals.series[opts.seriesIndex] / customScale);
            console.log("value revert = " + revertScale);
            let toto = seriesName + ":  " + revertScale;
            return toto;
        },*/
    },
    responsive: [{
        breakpoint: 480,
        options: {
            legend: {
                show: true
            }
        }
    }],
    labels: labelTitles,
    series: dataValues,
    theme: {
        palette: userPalette
    }
};

const chart = new ApexCharts(document.querySelector("#chartWidget"), options);
chart.render();