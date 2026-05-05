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


export function initChartEight() {
    const chartEightEl = document.querySelector('#chartEight');
    if (chartEightEl) {
        const chartEightOptions = {
            series: [
                {
                    name: "Sales",
                    data: [180, 190, 170, 160, 175, 165, 170, 205, 230, 210, 240, 235],
                },
                {
                    name: "Revenue",
                    data: [40, 30, 50, 40, 55, 40, 70, 100, 110, 120, 150, 140],
                },
            ],
            legend: {
                show: false,
                position: "top",
                horizontalAlign: "left",
            },
            colors: ["#465FFF", "#9CB9FF"],
            chart: {
                fontFamily: "Outfit, sans-serif",
                height: 310,
                type: "area",
                toolbar: {
                    show: false,
                },
            },
            fill: {
                gradient: {
                    enabled: true,
                    opacityFrom: 0.55,
                    opacityTo: 0,
                },
            },
            stroke: {
                curve: "smooth",
                width: ["2", "2"],
            },

            markers: {
                size: 0,
            },
            labels: {
                show: false,
                position: "top",
            },
            grid: {
                xaxis: {
                    lines: {
                        show: false,
                    },
                },
                yaxis: {
                    lines: {
                        show: true,
                    },
                },
            },
            dataLabels: {
                enabled: false,
            },
            tooltip: {
                x: {
                    format: "dd MMM yyyy",
                },
            },
            xaxis: {
                type: "category",
                categories: [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "May",
                    "Jun",
                    "Jul",
                    "Aug",
                    "Sep",
                    "Oct",
                    "Nov",
                    "Dec",
                ],
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
                tooltip: false,
            },
            yaxis: {
                title: {
                    style: {
                        fontSize: "0px",
                    },
                },
            },
        };

        const chartEight = new ApexCharts(chartEightEl, chartEightOptions);
        chartEight.render();

        return chartEight;
    }
}
export default initChartEight;
