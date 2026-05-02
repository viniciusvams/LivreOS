<?php

use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class OsTermicaPrintController extends Controller
{
    public function imprimirOs($id)
    {
        $ordemServico = OrdemServico::with([
            'cliente',
            'unidade',
            'contato',
            'endereco',
            'equipamento',
            'produtos.produto.composicoes.componente',
            'produtos.variacao',
            'servicos.servico',
            'servicos.tecnico',
            'checklists.checklistModel',
        ])->findOrFail($id);

        $empresa = Empresa::first();
        $formato = get_option('os_termica_formato_impressao', '80mm', 'os_termica');
        $osImpressaoObservacaoAntesAssinatura = (string) (\App\Models\Configuracao::getValue('os_impressao_observacao_antes_assinatura', '') ?? '');
        $osImpressaoObservacaoAntesAssinaturaAtivo = \App\Models\Configuracao::osImpressaoObservacaoAntesAssinaturaAtivo();
        
        $html = view('os_termica::pdf-os', [
            'ordem' => $ordemServico,
            'empresa' => $empresa,
            'emitidoEm' => now()->format('d/m/Y \à\s H:i'),
            'userName' => auth()->user()?->name ?? 'Sistema',
            'formato' => $formato,
            'osImpressaoObservacaoAntesAssinatura' => $osImpressaoObservacaoAntesAssinatura,
            'osImpressaoObservacaoAntesAssinaturaAtivo' => $osImpressaoObservacaoAntesAssinaturaAtivo
        ])->render();

        $pdf = Pdf::loadHTML($html);
        
        // Configuração de bobina
        if ($formato === '58mm') {
            $pdf->setPaper([0, 0, 164.4, 1500]); // Aproximadamente 58mm
        } else {
            $pdf->setPaper([0, 0, 226.77, 1500]); // Aproximadamente 80mm
        }

        return $pdf->stream('os-termica-' . $ordemServico->id . '.pdf');
    }

    public function imprimirComprovante($id)
    {
        $ordemServico = OrdemServico::with([
            'cliente',
            'unidade',
            'contato',
            'endereco',
            'equipamento',
            'produtos.produto',
            'servicos.servico',
            'assinaturaTecnico',
            'assinaturaCliente',
        ])->findOrFail($id);

        $empresa = Empresa::first();
        $formato = get_option('os_termica_formato_impressao', '80mm', 'os_termica');
        $comprovanteHtmlAntesAssinatura = '';
        if (\App\Models\Configuracao::osComprovanteObservacaoAntesAssinaturaAtivo()) {
            $textoCfg = trim((string) (\App\Models\Configuracao::getValue('os_comprovante_observacao_antes_assinatura', '') ?? ''));
            $comprovanteHtmlAntesAssinatura = $textoCfg !== ''
                ? $textoCfg
                : \App\Models\Configuracao::textoComprovanteOsEncerradaPadrao();
        }
        
        $html = view('os_termica::pdf-comprovante', [
            'ordem' => $ordemServico,
            'empresa' => $empresa,
            'emitidoEm' => now()->format('d/m/Y \à\s H:i'),
            'userName' => auth()->user()?->name ?? 'Sistema',
            'formato' => $formato,
            'comprovanteHtmlAntesAssinatura' => $comprovanteHtmlAntesAssinatura
        ])->render();

        $pdf = Pdf::loadHTML($html);
        
        // Configuração de bobina
        if ($formato === '58mm') {
            $pdf->setPaper([0, 0, 164.4, 1500]); // Aproximadamente 58mm
        } else {
            $pdf->setPaper([0, 0, 226.77, 1500]); // Aproximadamente 80mm
        }

        return $pdf->stream('os-comprovante-termico-' . $ordemServico->id . '.pdf');
    }
}
