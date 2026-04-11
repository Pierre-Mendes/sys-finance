# 🚀 Gerenciador Financeiro Pessoal (SaaS Multi-Tenant)

> Um ecossistema de elite para gestão financeira, projetado para o controle absoluto do seu patrimônio, seja ele individual ou compartilhado via visões colaborativas.

---

## 📌 A Nova Era do Controle Financeiro
O sistema transcende o simples rastreamento de gastos. Ele é construído sobre uma base de **Soberania de Dados** e **Arquitetura Industrial (Clean Arch)**, unindo a agilidade do **Vue.js 3** com a robustez e segurança de uma API **PHP 8.4 (Slim Framework)**.

O coração do projeto é o **Cockpit Financeiro**, uma engine inteligente que sintetiza seu fluxo de caixa, investimentos e metas em uma visão 360º unificada por Workspaces.

## ✨ Funcionalidades de Elite (Features)

1.  **Cockpit Inteligente (Dashboard)**:
    - Visão consolidada de Patrimônio Líquido entre múltiplos ambientes.
    - Card de "Dinheiro Guardado" dinâmico: Prioriza suas **Metas Favoritas** ou exibe o total acumulado.
2.  **Extrato Bancário com Saldo Progressivo**:
    - Timeline detalhada de cada conta bancária.
    - Cálculo automático de saldo acumulado (Running Balance) para auditoria precisa de fluxos.
3.  **Multi-Tenancy Workspaces (SaaS Ready)**:
    - Isole completamente sua vida "Pessoal", "Familiar" e "Profissional".
    - Navegação instantânea entre visões sem recarga de página.
4.  **Protocolo de Rateio (Recursive Splitting)**:
    - Pague no seu cartão pessoal e repasse frações da despesa para o Workspace da empresa ou casal automaticamente via *Shadow Records*.
5.  **Metas & Simulação de Conquistas**:
    - Engine de projeção que calcula a data exata da vitória baseada no seu superávit histórico.
    - Vínculo direto com contas bancárias e histórico de aportes auditável.
6.  **Gestão de Cartões de Crédito**:
    - Controle de limites, faturas futuras e parcelamentos inteligentes com interface Mobile-First.
7.  **Investimentos (Clean Design Patterns)**:
    - Integração via `Strategy Pattern` para cotações de ativos em tempo real (Brapi/Sicoob).
8.  **Orçamentos (Budgets Termométricos)**:
    - Controle de "Burn Rate" com alertas visuais de criticidade em barras termométricas.

## 🏗️ Stack Tecnológica & Arquitetura

### Frontend: Modernidade & Performance
- **Vite + Vue 3 (Composition API)**: Velocidade extrema de desenvolvimento e execução.
- **Clean Architecture Frontend**: Divisão rigorosa em `core` (domínio), `data` (repositórios/API) e `presentation` (Vue/Components).
- **TailwindCSS + UX Premium**: Interface baseada em Glassmorphism, micro-animações e scrollbars customizadas.

### Backend: Segurança & Robustez
- **PHP 8.4 + Slim 4**: API de alta performance com tipagem estrita.
- **Security Headers (OWASP)**: Middlewares de segurança ativos (CSP, HSTS, XSS Protection).
- **Phinx Migrations**: Controle de versão do banco de dados MySQL para escalabilidade segura.

---

## 🚀 Guia de Onboarding (Quick Start)

Se você é novo por aqui, siga a "Trilha do Sucesso":

1.  **Crie suas Contas**: No módulo `Contas Bancárias`, cadastre onde seu dinheiro vive (Ex: Nubank, Investimentos, Espécie).
2.  **Categorize-se**: Em `Categorias`, defina os baldes onde seu dinheiro flui.
3.  **Lance o Passado**: Importe ou lance seus últimos gastos para dar vida ao Dashboard.
4.  **Trace Objetivos**: Crie sua primeira **Meta**, favorite-a e veja o progresso no Cockpit.

---

## 💻 Instalação Local (Developer Setup)

**Requisitos**: Docker, NodeJS v20+

1.  **Infraestrutura**: `docker compose up -d` (MySQL/MariaDB).
2.  **Banco de Dados**: `vendor/bin/phinx migrate -e development`.
3.  **API**: `php -S 0.0.0.0:8081 -t public/`.
4.  **Frontend**: `cd frontend && npm install && npm run dev`.

Acesse em: `http://localhost:5173`

---

*Desenvolvido com foco em precisão matemática e design de alta fidelidade. MIT License.*
