<?php
	// PARAMETROS
	define ('QTD_DIAS', '30');
	define ('DATA_INICIAL', '2017-01-01');
	define ('QTD_REGISTRO_DIAS', '200');
	define ('QTD_TESTES_EXECUTADOS', QTD_DIAS * QTD_REGISTRO_DIAS);

	// funcao para somar data
	function SomarData($data, $dias)
	{
		return date('Y-m-d', strtotime("+".$dias." days",strtotime($data))); 
	}
	
	// funcao para gerar horario aleatorio
	function HorarioAleatorio()
	{
		return str_pad(rand(1,24), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0,59), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0,59), 2, '0', STR_PAD_LEFT);
	}
	
	// Conexao com o banco
	$host = "192.168.25.126";
	$port = "3306";
	$user = "root";
	$pass = "1234";
	$dbname = "projetotcc";

	$conn = new PDO("mysql:host=" . $host . ";dbname=" . $dbname, $user, $pass);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	
	// array cartao
	$arrCodCartao = Array(1, 2, 3, 4, 5, 6);
	$arrCodFunc = Array(1, 2, 3, 4, 5, 6);
	$arrCodPorta = Array(1, 2, 3);
	
	// Cria a variavel DATA para poder fazer a soma dos dias
	$vData = DATA_INICIAL;
	
	// Controle dos logs
	$logCartaoInexistente = 0;
	$logFuncionarioInexistente = 0;
	$logPortaInexistente = 0;
	$logRegistrosIncluidos = 0;
	
	for ( $i = 0; $i < QTD_DIAS; $i++)
	{
		for ( $j = 0; $j < QTD_REGISTRO_DIAS; $j++ )
		{
			// Gera numeros randomicos para array
			$rndCartao = rand(1,count($arrCodCartao));
			$rndFuncionario = rand(1,count($arrCodFunc));
			$rndPorta = rand(1,count($arrCodPorta));
			
			$vCodCartao = $arrCodCartao[$rndCartao-1];
			$vCodFunc = $arrCodFunc[$rndFuncionario-1];
			$vCodPorta = $arrCodPorta[$rndPorta-1];
			
			$selectCartao = $conn->query("SELECT * FROM cartao WHERE cod_cartao = " . $vCodCartao);
			$selectFuncionario = $conn->query("SELECT * FROM funcionarios WHERE cod_func = " . $vCodFunc);
			$selectPorta = $conn->query("SELECT * FROM porta WHERE cod_porta = " . $vCodPorta);
			
			// select da sequencia
			$proxSequencia = $conn->query("SELECT coalesce(max(sequencia), 0) + 1 FROM ctl_acesso");
			$proxSequencia = $proxSequencia->fetchColumn(0);
			
			// Caso nao tenha o cartao, soma no log
			if ($selectCartao->rowCount() == 0)
			{
				$logCartaoInexistente++;
			}
			
			// Caso nao tenha o funcionario, soma no log
			if ($selectFuncionario->rowCount() == 0)
			{
				$logFuncionarioInexistente++;
			}
			
			// Caso nao tenha a porta, soma no log
			if ($selectPorta->rowCount() == 0)
			{
				$logPortaInexistente++;
			}
			
			// Caso tenha todos os registros, insere no banco
			if ( ($selectCartao->rowCount() > 0) && ($selectFuncionario->rowCount() > 0) && ($selectPorta->rowCount() > 0) )
			{
				$logRegistrosIncluidos++;
				$vHora = HorarioAleatorio();
				$conn->query("INSERT INTO ctl_acesso (sequencia, data, hora, cod_cartao, cod_func, cod_porta)
			                    VALUES({$proxSequencia}, '{$vData}', '{$vHora}', {$vCodCartao}, {$vCodFunc}, {$vCodPorta})");
			}
			
		}
		
		// Soma mais um dia na data
		$vData = SomarData($vData, 1);
		
		
	}
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset='utf-8'>
		<title>Teste</title>
	</head>
	<body style='font-family: "courier new"; text-align:left;'>
		<h1>===> Resultado dos Testes</h1>		
		<hr>
		<h2>Parametros Configurados</h2>
		<h3>Data Inicial...................................: <b style="color:red"><?php echo DATA_INICIAL; ?></b></h3>
		<h3>Quantidade de Dias.............................: <b style="color:red"><?php echo QTD_DIAS; ?></b></h3>
		<h3>Quantidade Registros por dia...................: <b style="color:red"><?php echo QTD_REGISTRO_DIAS; ?></b></h3>
		<h3>Quantidade de acessos testados.................: <b style="color:red"><?php echo QTD_TESTES_EXECUTADOS; ?></b></h3>
		<hr>
		<h3>Cartões não encontrados na base de dados.......: <b style="color:red"><?php echo $logCartaoInexistente; ?></b></h3>
		<h3>Funcionarios não encontrados na base de dados..: <b style="color:red"><?php echo $logFuncionarioInexistente; ?></b></h3>
		<h3>Portas não encontradas na base de dados........: <b style="color:red"><?php echo $logPortaInexistente; ?></b></h3>
		<hr>
		<h3>Total de registros incluidos na base de dados..: <b style="color:red"><?php echo $logRegistrosIncluidos ?></b></h3>
		<hr>
	</body>
</html>