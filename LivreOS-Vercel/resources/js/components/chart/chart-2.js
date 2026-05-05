/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */


export const initChartTwo = () => {
    const chartElement = document.querySelector('#chartTwo');

    if (chartElement) {
        const chartTwoOptions = {
            series: [75.55],
            colors: ["#465FFF"],
            chart: {
                fontFamily: "Outfit, sans-serif",
                type: "radialBar",
                height: 330,
                sparkline: {
                    enabled: true,
                },
            },
            plotOptions: {
                radialBar: {
                    startAngle: -90,
                    endAngle: 90,
                    hollow: {
                        size: "80%",
                    },
                    track: {
                        background: "#E4E7EC",
                        strokeWidth: "100%",
                        margin: 5, // margin is in pixels
                    },
                    dataLabels: {
                        name: {
                            show: false,
                        },
                        value: {
                            fontSize: "36px",
                            fontWeight: "600",
                            offsetY: 60,
                            color: "#1D2939",
                            formatter: function (val) {
                                return val + "%";
                            },
                        },
                    },
                },
            },
            fill: {
                type: "solid",
                colors: ["#465FFF"],
            },
            stroke: {
                lineCap: "round",
            },
            labels: ["Progress"],
        };

        const chart = new ApexCharts(chartElement, chartTwoOptions);
        chart.render();
        return chart;
    }
}

export default initChartTwo;
