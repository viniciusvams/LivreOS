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

import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world';
import 'jsvectormap/dist/jsvectormap.min.css';

export const initMap = () => {
    const mapSelectorOne = document.querySelectorAll('#mapOne');

    if (mapSelectorOne.length) {
        const mapOne = new jsVectorMap({
            selector: "#mapOne",
            map: "world",
            zoomButtons: false,
            regionStyle: {
                initial: {
                    fontFamily: "Outfit",
                    fill: "#D9D9D9",
                },
                hover: {
                    fillOpacity: 1,
                    fill: "#465fff",
                },
            },
            markers: [
                {
                    name: "Egypt",
                    coords: [26.8206, 30.8025],
                },
                {
                    name: "United Kingdom",
                    coords: [55.3781, 3.436],
                },
                {
                    name: "United States",
                    coords: [37.0902, -95.7129],
                },
            ],

            markerStyle: {
                initial: {
                    strokeWidth: 1,
                    fill: "#465fff",
                    fillOpacity: 1,
                    r: 4,
                },
                hover: {
                    fill: "#465fff",
                    fillOpacity: 1,
                },
                selected: {},
                selectedHover: {},
            },

            onRegionTooltipShow: function (event, tooltip, code) {
                tooltip.text(
                    tooltip.text() + (code === "EG" ? " <b>(Hello Russia)</b>" : ""),
                    true // This second parameter enables HTML
                );
            },
        });
    }
};

export default initMap;
