{{--
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
 --}}

@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Rota do dia - Mapa</h1>
        <p class="text-gray-600 dark:text-gray-400">Visualização dos atendimentos no mapa, por dia.</p>
    </div>
    <a href="{{ route('plugin.atendimento.rota-do-dia', ['data' => $data]) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">← Voltar para lista</a>
</div>

<form method="GET" action="{{ route('plugin.atendimento.rota-do-dia.mapa') }}" class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Data</label>
        <input type="date" name="data" value="{{ $data }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
    </div>
    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
    <div class="ml-auto flex flex-wrap items-center gap-2">
        <a href="{{ route('plugin.atendimento.rota-do-dia.export.csv', ['data' => $data]) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-7-8h6a2 2 0 012 2v12a2 2 0 01-2 2H9a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
            Exportar CSV
        </a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4a1 1 0 011-1h10a1 1 0 011 1v5M6 18h12v-5H6v5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 14H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2"/></svg>
            Imprimir / PDF
        </button>
    </div>
</form>

@if(empty($pontos))
    <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
        Nenhum atendimento com endereço para esta data.
    </div>
@else
    <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div id="map" style="height: 600px; width: 100%; min-height: 400px;" class="rounded-lg border border-gray-200 dark:border-gray-700"></div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            O mapa é centralizado automaticamente para mostrar todos os pontos.
        </p>
    </div>

    <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Legenda — Endereços da rota</h2>
        <ul class="space-y-2">
            @foreach($pontos as $idx => $ponto)
            <li class="flex flex-wrap items-baseline gap-2 text-sm">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $idx + 1 }}</span>
                <span class="font-medium text-gray-800 dark:text-gray-100">{{ $ponto['cliente'] ?? '—' }}</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $ponto['endereco'] ?? '—' }}</span>
                @if(!isset($googleMapsApiKey) || !$googleMapsApiKey)
                    @if(empty($ponto['lat']) && empty($ponto['lng']))
                        <span class="text-amber-600 dark:text-amber-400">(não localizado no mapa)</span>
                    @endif
                @endif
            </li>
            @endforeach
        </ul>
    </div>

    <script>
        const ROTA_DIA_PONTOS = @json($pontos);
    </script>

    @if($googleMapsApiKey)
        <script>
            function initMap() {
                const hasPontos = Array.isArray(ROTA_DIA_PONTOS) && ROTA_DIA_PONTOS.length > 0;
                const fallbackCenter = { lat: -14.2350, lng: -51.9253 }; // Brasil

                const map = new google.maps.Map(document.getElementById('map'), {
                    zoom: hasPontos ? 6 : 4,
                    center: fallbackCenter,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                });

                if (!hasPontos) {
                    return;
                }

                const bounds = new google.maps.LatLngBounds();
                const geocoder = new google.maps.Geocoder();
                let geocodedCount = 0;
                let anyLocated = false;

                ROTA_DIA_PONTOS.forEach((ponto, index) => {
                    geocoder.geocode({ address: ponto.endereco }, function(results, status) {
                        geocodedCount++;
                        if (status === 'OK' && results[0]) {
                            const loc = results[0].geometry.location;
                            const num = index + 1;
                            const enderecoTexto = (ponto.endereco || '').trim();
                            const marker = new google.maps.Marker({
                                map: map,
                                position: loc,
                                icon: {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    scale: 22,
                                    fillColor: '#2563eb',
                                    fillOpacity: 0.95,
                                    strokeColor: '#1d4ed8',
                                    strokeWeight: 3,
                                },
                                label: {
                                    text: String(num),
                                    color: '#ffffff',
                                    fontSize: '14px',
                                    fontWeight: 'bold',
                                },
                                title: enderecoTexto ? (num + ' · ' + enderecoTexto) : String(num),
                            });

                            const infoHtml = `
                                <div class="text-sm">
                                    <div class="font-semibold mb-1">${ponto.cliente || 'Cliente não informado'}</div>
                                    <div class="mb-1 text-xs text-gray-600">${ponto.tecnico ? 'Técnico: ' + ponto.tecnico + ' · ' : ''}${ponto.hora || ''}</div>
                                    <div class="mb-1 text-xs text-gray-600">${ponto.endereco}</div>
                                    ${ponto.status ? '<div class="mb-1 text-xs">Status: ' + ponto.status + '</div>' : ''}
                                    ${ponto.prioridade ? '<div class="mb-1 text-xs">Prioridade: ' + ponto.prioridade + '</div>' : ''}
                                    ${ponto.maps_url ? '<a href="' + ponto.maps_url + '" target="_blank" rel="noopener noreferrer" class="text-xs text-brand-600 hover:underline">Abrir no Google Maps</a>' : ''}
                                </div>
                            `;

                            const infoWindow = new google.maps.InfoWindow({ content: infoHtml });
                            marker.addListener('click', () => infoWindow.open(map, marker));

                            bounds.extend(loc);
                            anyLocated = true;
                        }

                        if (geocodedCount === ROTA_DIA_PONTOS.length && anyLocated) {
                            map.fitBounds(bounds);

                            // Zoom bem próximo (nível rua); mínimo 17 para os marcadores aparecerem
                            google.maps.event.addListenerOnce(map, 'idle', function() {
                                var z = map.getZoom();
                                if (z < 17) map.setZoom(17);
                                if (z > 18) map.setZoom(18);
                            });
                        }
                    });
                });
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&callback=initMap" async defer></script>
    @else
        {{-- Fallback sem chave: Leaflet + OpenStreetMap (coordenadas já vêm do servidor) --}}
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <style>
            .rota-marker-label {
                background: transparent !important;
                border: none !important;
                font-weight: 700;
                font-size: 16px;
                color: #fff;
                text-shadow: 0 0 3px #000, 0 1px 3px #000;
            }
            .rota-marker-endereco {
                background: rgba(255,255,255,0.95) !important;
                border: 2px solid #1d4ed8 !important;
                border-radius: 6px !important;
                padding: 6px 10px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                color: #1e293b !important;
                white-space: nowrap !important;
                max-width: 260px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important;
            }
        </style>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function() {
                var pontos = typeof ROTA_DIA_PONTOS !== 'undefined' ? ROTA_DIA_PONTOS : [];

                function initLeafletMap() {
                    if (typeof L === 'undefined') {
                        var el = document.getElementById('map');
                        if (el) el.innerHTML = '<p class="p-4 text-gray-500">Falha ao carregar o mapa. Tente recarregar a página.</p>';
                        return;
                    }

                    var mapEl = document.getElementById('map');
                    if (!mapEl) return;

                    var map = L.map('map').setView([-23.5505, -46.6333], 10);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    }).addTo(map);

                    var comCoordenadas = pontos.filter(function(p) { return p && p.lat != null && p.lng != null && !isNaN(parseFloat(p.lat)) && !isNaN(parseFloat(p.lng)); });
                    if (comCoordenadas.length === 0) {
                        if (pontos.length > 0) {
                            mapEl.insertAdjacentHTML('afterend', '<p class="mt-2 text-sm text-amber-600 dark:text-amber-400">Nenhum endereço foi localizado para exibir no mapa. Confira a legenda abaixo; endereços completos (rua, número, cidade, UF) têm mais chance de serem encontrados. Ou configure a chave do Google Maps nas configurações do plugin.</p>');
                        }
                        return;
                    }

                    var bounds = [];
                    comCoordenadas.forEach(function(ponto, index) {
                        var lat = parseFloat(ponto.lat);
                        var lng = parseFloat(ponto.lng);
                        if (isNaN(lat) || isNaN(lng)) return;

                        var loc = L.latLng(lat, lng);
                        bounds.push(loc);
                        var num = (pontos.indexOf(ponto) >= 0 ? pontos.indexOf(ponto) : index) + 1;
                        var marker = L.circleMarker(loc, {
                            radius: 28,
                            fillColor: '#2563eb',
                            color: '#1d4ed8',
                            weight: 3,
                            fillOpacity: 0.95
                        }).addTo(map);
                        var enderecoTexto = (ponto.endereco || '').trim().replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var labelConteudo = enderecoTexto
                            ? (num + ' · ' + (enderecoTexto.length > 45 ? enderecoTexto.substring(0, 42) + '…' : enderecoTexto))
                            : String(num);
                        marker.bindTooltip(labelConteudo, {
                            permanent: true,
                            direction: enderecoTexto ? 'top' : 'center',
                            offset: enderecoTexto ? [0, -32] : [0, 0],
                            className: enderecoTexto ? 'rota-marker-endereco' : 'rota-marker-label'
                        });
                        var cliente = (ponto.cliente || 'Cliente não informado').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var endereco = (ponto.endereco || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        var html = '<div style="min-width: 200px; font-size: 13px;">' +
                            '<div style="font-weight: 600; margin-bottom: 4px;">' + cliente + '</div>' +
                            (ponto.tecnico || ponto.hora ? '<div style="color: #666; font-size: 12px; margin-bottom: 4px;">' + (ponto.tecnico ? 'Técnico: ' + ponto.tecnico + ' · ' : '') + (ponto.hora || '') + '</div>' : '') +
                            '<div style="color: #666; font-size: 12px;">' + endereco + '</div>' +
                            (ponto.maps_url ? '<a href="' + ponto.maps_url + '" target="_blank" rel="noopener" style="font-size: 12px;">Abrir no Google Maps</a>' : '') +
                            '</div>';
                        marker.bindPopup(html);
                    });

                    if (bounds.length === 1) {
                        map.setView(bounds[0], 17);
                    } else if (bounds.length > 1) {
                        var b = L.latLngBounds(bounds);
                        map.fitBounds(b, { padding: [100, 100], maxZoom: 19 });
                        var z = map.getZoom();
                        if (z < 17) map.setZoom(17);
                        if (z > 18) map.setZoom(18);
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initLeafletMap);
                } else {
                    initLeafletMap();
                }
            })();
        </script>
    @endif
@endif
@endsection

