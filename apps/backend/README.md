# Management System

Sistema de gestão empresarial com foco em entidades (clientes/fornecedores), propostas, encomendas, financeiro, acessos, auditoria e calendário, com arquitetura web moderna (`Vue + Laravel API`), segurança por padrão e suporte a escala operacional.

---

## Visão Geral

Este projeto implementa um sistema de gestão para operação diária de negócio, cobrindo:

- ciclo comercial (`Proposal -> Client Order -> Supplier Orders -> Supplier Invoices`);
- gestão de entidades e contactos;
- controlo de permissões e rastreabilidade de ações;
- geração de documentos e comunicação assíncrona (PDF/e-mail);
- integração com serviços externos (VIES, SMTP, observabilidade).

Objetivos principais:

- simplicidade operacional;
- consistência transacional em fluxos críticos;
- segurança e conformidade (ACL, CSRF, CORS, private storage);
- extensibilidade para novos módulos.

---

## Arquitetura

Arquitetura em camadas, com separação de responsabilidades:

- **Frontend**: `Vue 3 + Vite + shadcn-vue` (UI, formulários, tabelas, calendário);
- **Backend API**: `Laravel 12` com `Sanctum/Fortify`, middleware, serviços de aplicação;
- **Dados**: `MySQL` com constraints e integridade referencial;
- **Assíncrono**: `Queue Worker` para tarefas pesadas;
- **Infra**: `Nginx/HTTPS`, storage privado, SMTP, VIES, Sentry.

Padrão de backend:

- `Controllers` (entrada HTTP/API);
- `Services` (regras de negócio, transições, consistência);
- `Models` (domínio e persistência);
- `Jobs/Events` (processamento assíncrono);
- `Policies/ACL` (autorização por função/permissão).

---

Em desenvolvimento. contém mudanças.
