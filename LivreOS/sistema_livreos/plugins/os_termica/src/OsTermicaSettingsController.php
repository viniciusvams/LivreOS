<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OsTermicaSettingsController extends Controller
{
    public function index()
    {
        $formatoImpressao = get_option('os_termica_formato_impressao', '80mm', 'os_termica');
        
        return erp_view('os_termica::settings', [
            'title' => 'Configurações de Impressão Térmica (OS)',
            'formatoImpressao' => $formatoImpressao,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'os_termica_formato_impressao' => 'required|in:80mm,58mm',
        ]);

        update_option('os_termica_formato_impressao', $request->os_termica_formato_impressao, 'os_termica');

        return redirect()->route('plugin.os_termica.settings')
            ->with('success', 'Configurações de impressão salvas com sucesso!');
    }
}
