<?php

// SETA O TIPO DE CONTEUDO JSON
header("Content-type: application/json");

// DIZ QUE O ARQUIVO SERVER.CLASS.PHP É NECESSÁRIO AQUI
require_once 'server.class.php';

// CASO PASSE O PARAMETRO GETDADOSCARTAO NO NAVEGADOR, ENTRA NESTE IF
if (filter_input(INPUT_GET, 'getDadosCartao'))
{
	// PEGO OS DADOS DIGITADOS NO NAVEGADOR E CONVERTO EM OBJETO (JSON_DECODE)
	$dados = json_decode(filter_input(INPUT_GET, 'getDadosCartao'));

	// MOVO OS VALORES DO JSON PARA UMA VARIAVEL
	$id = $dados->id;
	$porta = $dados->porta;

	// INSTANCIO A CLASSE
	$classe = new WS();
	
	// EXECUTO O METODO GETDADOSCARTAO E MOSTRO NA TELA
	echo $classe->getDadosCartao($id, $porta);
}
else if (filter_input(INPUT_GET, 'insertDadosBanco'))
{
	// PEGO OS DADOS DIGITADOS NO NAVEGADOR E CONVERTO EM OBJETO (JSON_DECODE)
	$dados = json_decode(filter_input(INPUT_GET, 'insertDadosBanco'));

	// MOVO OS VALORES DO JSON PARA UMA VARIAVEL
	$id = $dados->id;
	$porta = $dados->porta;

	$classe = new WS();

	echo $classe->insertDadosBanco($id, $porta);
}
else if (filter_input(INPUT_GET, 'GravarArquivo'))
{
	$classe = new WS();

	// Cria um array para passar os parametros do filtro
	$filtro = array();

	if (filter_input(INPUT_GET, 'nome_func'))
	{
		$filtro['nome_func'] = filter_input(INPUT_GET, 'nome_func');
	}

	if (filter_input(INPUT_GET, 'id'))
	{
		$filtro['id'] = filter_input(INPUT_GET, 'id');
	}

	// @@GABRIELA
	if ( filter_input(INPUT_GET, 'data_menor') && filter_input(INPUT_GET, 'data_maior') )
	{
		$filtro['data_menor'] = filter_input(INPUT_GET, 'data_menor');
		$filtro['data_maior'] = filter_input(INPUT_GET, 'data_maior');
	}
	// @@ FIM

	echo $classe->GravarArquivo($filtro);
}