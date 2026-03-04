# **Teste 02 — Gestão de Despesas Internacionais**

Objetivo: Criar um sistema onde o usuário registra despesas em moedas estrangeiras e o sistema converte automaticamente o valor para Real (BRL) utilizando API de câmbio.

## **1\) Cadastro de Usuário (CPF \+ CEP)**

* Usuário informa Nome, E-mail, CPF e CEP.  
* Validar CPF pelo cálculo dos dígitos verificadores.  
* Bloquear cadastro se CPF inválido.  
* Bloquear cadastro se CPF já existir no sistema.  
* Consultar API de CEP para preencher Rua, Bairro, Cidade e Estado automaticamente.  
* Bloquear cadastro se CEP for inválido ou inexistente.  
* Impedir formatos incorretos (CEP com letras, tamanho inválido etc.).

## **2\) Registro de Despesa com Conversão Automática**

* Usuário informa valor e moeda (USD, EUR, etc.).  
* Sistema consulta API de câmbio.  
* Salvar valor original, cotação utilizada e valor convertido em BRL.  
* Utilizar tipo decimal para valores monetários

## **3\) Privacidade e Segurança**

* Usuário visualiza apenas suas próprias despesas.  
* Bloquear acesso indevido a registros de terceiros.

## **4\) Robustez do Sistema**

* Tratamento de falhas das APIs.  
* Tratamento de falhas internas para usuário inserir os dados corretamente no banco  
* Permitir salvar como pendente ou exibir mensagem amigável.  
* Impedir formatos inválidos e campos obrigatórios vazios.

## **5\) Tecnologias necessárias para o desenvolvimento**

* Laravel PHP  
* Postgres  
* JavaScript

## **6\) Para enviar link o git do projeto**

* Enviar para o e-mail: [modernizacao.ac@gmail.com](mailto:modernizacao.ac@gmail.com)  
* Envie com o título: Teste-dirmod-Nome-Completo