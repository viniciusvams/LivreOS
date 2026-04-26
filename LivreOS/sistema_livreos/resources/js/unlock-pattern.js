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

/**
 * Componente de Desenho de Padrão de Desbloqueio
 * Suporta grid dinâmico (padrão 3x3, mas aceita outros tamanhos)
 */
class UnlockPattern {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} não encontrado`);
            return;
        }

        this.rows = options.rows || 3;
        this.cols = options.cols || 3;
        this.readonly = options.readonly || false;
        this.pattern = options.pattern || []; // Array de índices [0, 4, 8]
        this.onPatternChange = options.onPatternChange || null;

        this.isDrawing = false;
        this.currentPath = [];
        this.canvas = null;
        this.ctx = null;
        this.cellSize = 0;
        this.padding = 20;
        this.radius = 15;
        /** @type {number|null} */
        this._activePointerId = null;

        this.init();
    }

    /**
     * Raio de acerto em pixels de canvas: só entra no padrão se o toque estiver perto do centro
     * do ponto (como no Android), evitando capturar células laterais ao traçar diagonal.
     */
    getHitRadius() {
        return Math.min(Math.max(this.cellSize * 0.46, 26), 52);
    }

    init() {
        this.container.innerHTML = '';
        
        // Criar canvas
        this.canvas = document.createElement('canvas');
        this.canvas.style.width = '100%';
        this.canvas.style.maxWidth = '320px';
        this.canvas.style.height = 'auto';
        this.canvas.style.border = '2px solid #e5e7eb';
        this.canvas.style.borderRadius = '8px';
        this.canvas.style.cursor = this.readonly ? 'default' : 'pointer';
        this.canvas.style.touchAction = 'none';
        
        this.container.appendChild(this.canvas);
        
        // Configurar canvas
        this.resizeCanvas();
        this.ctx = this.canvas.getContext('2d');
        
        // Se tiver padrão, desenhar (tanto para readonly quanto para edição)
        if (this.pattern.length > 0) {
            this.drawPattern(this.pattern);
        }
        
        // Se não for readonly, adicionar event listeners
        if (!this.readonly) {
            this.setupEventListeners();
            // Se não tiver padrão, desenhar apenas o grid
            if (this.pattern.length === 0) {
                this.drawGrid();
            }
        }
    }

    resizeCanvas() {
        const containerWidth = this.container.offsetWidth || 300;
        const size = Math.min(containerWidth - this.padding * 2, 320);
        this.canvas.width = size;
        this.canvas.height = size;
        this.cellSize = (size - this.padding * 2) / Math.max(this.rows, this.cols);
    }

    getCellPosition(index) {
        const row = Math.floor(index / this.cols);
        const col = index % this.cols;
        const x = this.padding + col * this.cellSize + this.cellSize / 2;
        const y = this.padding + row * this.cellSize + this.cellSize / 2;
        return { x, y, row, col, index };
    }

    /**
     * Converte coordenadas de tela para canvas (pixels reais do bitmap).
     */
    clientToCanvas(x, y) {
        const rect = this.canvas.getBoundingClientRect();
        const scaleX = this.canvas.width / rect.width;
        const scaleY = this.canvas.height / rect.height;
        return {
            cx: (x - rect.left) * scaleX,
            cy: (y - rect.top) * scaleY,
        };
    }

    /**
     * Retorna o índice do ponto cujo centro está mais próximo do toque, se estiver dentro do raio.
     * Não usa retângulos de célula (isso gerava saltos para vizinhos laterais em diagonais).
     */
    getCellFromPoint(x, y) {
        const { cx, cy } = this.clientToCanvas(x, y);
        const hitR = this.getHitRadius();
        const hitRSq = hitR * hitR;
        let best = -1;
        let bestDistSq = Infinity;

        for (let i = 0; i < this.rows * this.cols; i++) {
            const pos = this.getCellPosition(i);
            const dx = cx - pos.x;
            const dy = cy - pos.y;
            const d2 = dx * dx + dy * dy;
            if (d2 <= hitRSq && d2 < bestDistSq) {
                bestDistSq = d2;
                best = i;
            }
        }
        return best;
    }

    /**
     * Ponto intermediário obrigatório (como no desbloqueio Android): ao ligar dois pontos
     * em linha reta com um ponto no meio da grade, esse meio entra no padrão mesmo se o
     * dedo/mouse não passar por cima dele (ex.: diagonal 0→8 inclui o 4).
     */
    getIntermediateCell(a, b) {
        const cols = this.cols;
        const r1 = Math.floor(a / cols);
        const c1 = a % cols;
        const r2 = Math.floor(b / cols);
        const c2 = b % cols;
        const dr = r2 - r1;
        const dc = c2 - c1;
        if (dr === 0 && dc === 0) {
            return -1;
        }
        // Só retas: horizontal, vertical ou diagonal 45° (mesmo |dr| e |dc| quando ambos ≠ 0)
        if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
            return -1;
        }
        const steps = Math.max(Math.abs(dr), Math.abs(dc));
        if (steps !== 2) {
            return -1;
        }
        const mr = r1 + dr / 2;
        const mc = c1 + dc / 2;
        if (!Number.isInteger(mr) || !Number.isInteger(mc)) {
            return -1;
        }
        if (mr < 0 || mr >= this.rows || mc < 0 || mc >= this.cols) {
            return -1;
        }
        return mr * cols + mc;
    }

    drawGrid() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Desenhar linhas do grid
        this.ctx.strokeStyle = '#d1d5db';
        this.ctx.lineWidth = 1;
        
        for (let i = 0; i <= this.rows; i++) {
            const y = this.padding + i * this.cellSize;
            this.ctx.beginPath();
            this.ctx.moveTo(this.padding, y);
            this.ctx.lineTo(this.padding + this.cols * this.cellSize, y);
            this.ctx.stroke();
        }
        
        for (let i = 0; i <= this.cols; i++) {
            const x = this.padding + i * this.cellSize;
            this.ctx.beginPath();
            this.ctx.moveTo(x, this.padding);
            this.ctx.lineTo(x, this.padding + this.rows * this.cellSize);
            this.ctx.stroke();
        }
        
        // Desenhar pontos
        for (let i = 0; i < this.rows * this.cols; i++) {
            const pos = this.getCellPosition(i);
            this.ctx.fillStyle = '#9ca3af';
            this.ctx.beginPath();
            this.ctx.arc(pos.x, pos.y, 8, 0, 2 * Math.PI);
            this.ctx.fill();
        }
    }

    drawPattern(pattern) {
        this.drawGrid();
        
        if (pattern.length === 0) return;
        
        // Desenhar linhas conectando os pontos
        this.ctx.strokeStyle = '#3b82f6';
        this.ctx.lineWidth = 3;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        
        for (let i = 0; i < pattern.length - 1; i++) {
            const from = this.getCellPosition(pattern[i]);
            const to = this.getCellPosition(pattern[i + 1]);
            
            this.ctx.beginPath();
            this.ctx.moveTo(from.x, from.y);
            this.ctx.lineTo(to.x, to.y);
            this.ctx.stroke();
        }
        
        // Desenhar pontos do padrão
        pattern.forEach((index, order) => {
            const pos = this.getCellPosition(index);
            
            // Círculo externo
            this.ctx.fillStyle = '#3b82f6';
            this.ctx.beginPath();
            this.ctx.arc(pos.x, pos.y, this.radius, 0, 2 * Math.PI);
            this.ctx.fill();
            
            // Círculo interno
            this.ctx.fillStyle = '#ffffff';
            this.ctx.beginPath();
            this.ctx.arc(pos.x, pos.y, this.radius - 4, 0, 2 * Math.PI);
            this.ctx.fill();
            
            // Número da ordem (opcional, para debug)
            if (!this.readonly) {
                this.ctx.fillStyle = '#3b82f6';
                this.ctx.font = 'bold 12px Arial';
                this.ctx.textAlign = 'center';
                this.ctx.textBaseline = 'middle';
                this.ctx.fillText((order + 1).toString(), pos.x, pos.y);
            }
        });
    }

    setupEventListeners() {
        const startEvent = (e) => {
            e.preventDefault();
            this.isDrawing = true;
            this.currentPath = [];
            if (typeof e.pointerId === 'number') {
                this._activePointerId = e.pointerId;
                try {
                    this.canvas.setPointerCapture(e.pointerId);
                } catch (err) {
                    /* ignore */
                }
            }
            const point = this.getPointFromEvent(e);
            const cellIndex = this.getCellFromPoint(point.x, point.y);
            if (cellIndex >= 0) {
                this.addToPath(cellIndex);
            }
        };

        const moveEvent = (e) => {
            if (!this.isDrawing) return;
            if (this._activePointerId !== null && typeof e.pointerId === 'number' && e.pointerId !== this._activePointerId) {
                return;
            }
            e.preventDefault();
            const point = this.getPointFromEvent(e);
            const cellIndex = this.getCellFromPoint(point.x, point.y);
            if (cellIndex >= 0 && !this.currentPath.includes(cellIndex)) {
                this.addToPath(cellIndex);
            }
        };

        const endEvent = (e) => {
            if (!this.isDrawing) return;
            if (this._activePointerId !== null && typeof e.pointerId === 'number' && e.pointerId !== this._activePointerId) {
                return;
            }
            e.preventDefault();
            this.isDrawing = false;
            if (this._activePointerId !== null && typeof e.pointerId === 'number') {
                try {
                    this.canvas.releasePointerCapture(e.pointerId);
                } catch (err) {
                    /* ignore */
                }
            }
            this._activePointerId = null;

            // Sempre salvar o caminho atual como padrão final
            if (this.currentPath.length > 0) {
                this.pattern = [...this.currentPath];
            } else {
                this.pattern = [];
            }

            setTimeout(() => {
                if (this.onPatternChange) {
                    this.onPatternChange([...this.pattern]);
                }
            }, 10);
        };

        if (typeof window.PointerEvent !== 'undefined') {
            this.canvas.addEventListener('pointerdown', startEvent);
            this.canvas.addEventListener('pointermove', moveEvent);
            this.canvas.addEventListener('pointerup', endEvent);
            this.canvas.addEventListener('pointercancel', endEvent);
            // Com setPointerCapture o traço segue fora do retângulo do canvas; não usar pointerleave (evita cortar o desenho).
        } else {
            this.canvas.addEventListener('mousedown', startEvent);
            this.canvas.addEventListener('mousemove', moveEvent);
            this.canvas.addEventListener('mouseup', endEvent);
            this.canvas.addEventListener('mouseleave', endEvent);

            this.canvas.addEventListener('touchstart', startEvent, { passive: false });
            this.canvas.addEventListener('touchmove', moveEvent, { passive: false });
            this.canvas.addEventListener('touchend', endEvent, { passive: false });
            this.canvas.addEventListener('touchcancel', endEvent, { passive: false });
        }
    }

    getPointFromEvent(e) {
        if (e.touches && e.touches.length > 0) {
            return { x: e.touches[0].clientX, y: e.touches[0].clientY };
        }
        if (typeof e.clientX === 'number' && typeof e.clientY === 'number') {
            return { x: e.clientX, y: e.clientY };
        }
        return { x: 0, y: 0 };
    }

    addToPath(cellIndex) {
        if (this.currentPath.includes(cellIndex)) {
            return;
        }

        if (this.currentPath.length > 0) {
            const last = this.currentPath[this.currentPath.length - 1];
            const mid = this.getIntermediateCell(last, cellIndex);
            if (mid >= 0 && !this.currentPath.includes(mid)) {
                this.currentPath.push(mid);
                this.drawPattern(this.currentPath);
                if (this.onPatternChange) {
                    this.onPatternChange([...this.currentPath]);
                }
            }
        }

        if (this.currentPath.includes(cellIndex)) {
            return;
        }

        this.currentPath.push(cellIndex);
        this.drawPattern(this.currentPath);
        if (this.onPatternChange) {
            this.onPatternChange([...this.currentPath]);
        }
    }

    setPattern(pattern) {
        this.pattern = pattern || [];
        this.drawPattern(this.pattern);
    }

    getPattern() {
        // Retornar o pattern finalizado, ou o currentPath se ainda estiver desenhando
        if (this.pattern && this.pattern.length > 0) {
            return this.pattern;
        }
        // Se não houver pattern finalizado, retornar o currentPath (pode estar desenhando)
        return this.currentPath && this.currentPath.length > 0 ? [...this.currentPath] : [];
    }

    clear() {
        this.pattern = [];
        this.currentPath = [];
        this.drawGrid();
        if (this.onPatternChange) {
            this.onPatternChange([]);
        }
    }
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.UnlockPattern = UnlockPattern;
}
