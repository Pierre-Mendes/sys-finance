<template>
  <MainLayout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Exportação de Relatórios</h2>
        <p class="text-gray-500 mt-1 sm:text-lg">Gere balanços e compilados financeiros para impressão ou análise profunda.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- PDF Card -->
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition">
            <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Relatório Completo (A4)</h3>
            <p class="text-gray-500 text-sm mb-8 leading-relaxed">Gera um arquivo PDF bem formatado contendo o histórico transacional inteiro finalizando com o balanço consolidado verde ou vermelho.</p>
            <button @click="generate('pdf')" :disabled="isGeneratingPdf" class="mt-auto w-full max-w-[250px] bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition shadow-sm overflow-hidden relative">
                <div v-if="isGeneratingPdf" class="absolute inset-0 bg-red-600 flex items-center justify-center z-10 gap-2">
                    <svg class="animate-spin w-5 h-5 text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Processando...
                </div>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span v-if="!isGeneratingPdf">Visualizar PDF</span>
            </button>
        </div>

        <!-- CSV Card -->
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition">
            <div class="w-20 h-20 bg-green-50 text-green-500 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Exportação de Planilha</h3>
            <p class="text-gray-500 text-sm mb-8 leading-relaxed">Estrutura fria e tabular voltada para softwares de contabilidade empresarial (Excel, Numbers, LibreOffice) para cruzar dados profundos.</p>
            <button @click="generate('csv')" :disabled="isGeneratingCsv" class="mt-auto w-full max-w-[250px] bg-green-500 hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition shadow-sm overflow-hidden relative">
                <div v-if="isGeneratingCsv" class="absolute inset-0 bg-green-600 flex items-center justify-center z-10 gap-2">
                    <svg class="animate-spin w-5 h-5 text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Gerando Planilha...
                </div>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span v-if="!isGeneratingCsv">Fazer Download (CSV)</span>
            </button>
        </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
import MainLayout from '@/components/layout/MainLayout.vue'

const isGeneratingPdf = ref(false)
const isGeneratingCsv = ref(false)

const generate = async (type: 'pdf' | 'csv') => {
    if (type === 'pdf') isGeneratingPdf.value = true;
    else isGeneratingCsv.value = true;

    try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/reports/${type}`, {
            headers: { Authorization: `Bearer ${token}` },
            responseType: 'blob' // Important to handle binaries gracefully
        });

        // Add an artificial timeout to demonstrate the beautiful UX Loading state since domPDF is FAST
        setTimeout(() => {
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `meu_relatorio_gestao.${type}`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            toast.success('Prontinho! O download foi ejetado pro seu browser.');

            if (type === 'pdf') isGeneratingPdf.value = false;
            else isGeneratingCsv.value = false;
        }, 800)

    } catch (e) {
        toast.error('Ocorreu um erro gerando o relatório.');
        if (type === 'pdf') isGeneratingPdf.value = false;
        else isGeneratingCsv.value = false;
    }
}
</script>
