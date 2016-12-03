<?php
/**
* Classe do servidor REST
*
* @author Gabriela Pilot <gabiipilot@gmail.com>
* @version 0.1
* @copyright GPL © 2016, Gabs Corp
* @access public
* @example Class WS
*/
Class WS
{

	private $host;
	private $port;
	private $user;
	private $pass;
	private $conn;
	private $dbname;


	// Construtor da classe
	public function __construct()
	{
		$this->host = "192.168.25.126";
		$this->port = "3306";
		$this->user = "root";
		$this->pass = "1234";
		$this->dbname = "projetotcc";

		$this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->user, $this->pass);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}

	// Método que traz as informações a partir do ID do cartão.
	/**
	* Metodo para retornar os dados referentes a ID do cartão e a porta
	* @access private
	* @param String $idCartao
	* @param int $codPorta
	* @return String
	*/
	public function getDadosCartao( $idCartao, $codPorta)
	{
		// Testo todo o comando abaixo
		try {

			// Preparo a query que vou utilizar
			$query = "
			SELECT cartao.cod_cartao,
			       cartao.id,
			       cartao.situacao,
			       rel_cartao_porta.cod_porta,
			       funcionarios.cod_func,
			       funcionarios.nome_func
			       
			  FROM cartao

			  JOIN rel_cartao_porta
			    ON rel_cartao_porta.cod_cartao = cartao.cod_cartao

			  JOIN funcionarios
			    ON funcionarios.cod_cartao = cartao.cod_cartao

			 WHERE cartao.id = :idcartao
			   AND rel_cartao_porta.cod_porta = :codporta
			";

			// Vou trabalhar essa query aqui
			$stmt = $this->conn->prepare($query);

			// Vou atribuir o valor do parâmetro da query :idcartao
			$stmt->bindValue(":idcartao", $idCartao);

			// Vou atribuir o valor do parâmetro da query :codporta
			$stmt->bindValue(":codporta", $codPorta);

			// Vou executar o comando SELECT que fiz acima
			$stmt->execute();

			// caso retorne pelo menos um registro
			if ($stmt->rowCount() > 0)
			{
				// Array com todas as informações retornadas do select.
				$arr = $stmt->fetch(\PDO::FETCH_ASSOC);
				// Adiciono mais um elemento no array
				$arr['acesso'] = true;
				// Retorna o OBJETO que o select trouxe no padrão JSON
				// usando o método do PHP chamado JSON_ENCODE
				return $this->indent_json(json_encode($arr));
			} else {
				return '{"acesso":false}';
			}

		// em caso de erro, lanço uma excessão, onde retorna erro = true
		} catch (PDOException $ex) 
		{
			return '{ "erro":"true" }';
		}
	}

	/**
	* Função para identar o retorno JSON
	* @access private
	* @param String $json
	* @return String
	*/
	private function indent_json ($json) 
	{
		$result = '';
		$pos = 0;
		$strLen = strlen($json);
		$indentStr = "\t";
		$newLine = "\n";
		$prevChar = '';
		$outOfQuotes = true;
		for ($i=0; $i<=$strLen; $i++):
			$char = substr($json, $i, 1);
			if ($char == '"' && $prevChar != '\\'):
				$outOfQuotes = !$outOfQuotes;
			elseif(($char == '}' || $char == ']') && $outOfQuotes):
				$result .= $newLine;
				$pos --;
				for ($j=0; $j<$pos; $j++):
					$result .= $indentStr;
				endfor;
			endif;
			$result .= $char;
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes):
				$result .= $newLine;
				if ($char == '{' || $char == '['):
					$pos ++;
				endif;
				
				for ($j = 0; $j < $pos; $j++):
					$result .= $indentStr;
				endfor;
			endif;
			$prevChar = $char;
		endfor;
		return $result;
	}
	
	public function insertDadosBanco ($idcartao, $codporta)
	{

		try {

			// select para buscar o código do cartão
			$query = "SELECT cartao.cod_cartao, 
			                 funcionarios.cod_func
			            FROM cartao

			            JOIN funcionarios
	                      ON funcionarios.cod_cartao = cartao.cod_cartao

	                   WHERE cartao.id = :id
			            ";

			$stmt = $this->conn->prepare($query);
			$stmt->bindValue(":id", $idcartao);
			$stmt->execute();

			$aux = $stmt->fetch(\PDO::FETCH_ASSOC);

			$codigo_cartao = $aux['cod_cartao'];
			$codigo_funcionario = $aux['cod_func'];

			// Select para buscar a próxima sequencia da tabela de log
			$query = "SELECT COALESCE(MAX(sequencia),0) + 1 FROM ctl_acesso";
			$stmt = $this->conn->prepare($query);
			$stmt->execute();

			$proxima_sequencia = $stmt->fetchColumn(0);
		
			// Preparo a query que vou utilizar
				$query = "
				INSERT INTO ctl_acesso (sequencia, data, hora, cod_cartao, cod_func, cod_porta)
				     VALUES (:sequencia, CURRENT_DATE, CURRENT_TIME, :cod_cartao, :cod_func, :cod_porta)";
				
			$stmt = $this->conn->prepare($query);

			// Atribuindo os valores na query
			$stmt->bindValue(":sequencia", $proxima_sequencia);
			$stmt->bindValue(":cod_cartao", $codigo_cartao);
			$stmt->bindValue(":cod_func", $codigo_funcionario);
			$stmt->bindValue(":cod_porta", $codporta);

			// Executando a query acima
			$stmt->execute();

			return '{"erro":false}';
			
		} catch (PDOException $e) {
			return '{"erro":true}';
		}
	}

	// Parametro -> Array
	public function GravarArquivo($filtro)
	{
		try {

			$var_where = '';

			// caso for um array, verifica os filtros
			if (is_array($filtro))
			{
				if (isset($filtro['nome_func']))
				{
					$f_nome_func = str_replace(" ","%", $filtro['nome_func']);
					$var_where .= " AND lower(funcionarios.nome_func) LIKE '%".$f_nome_func."%' ";
				} 

				if (isset($filtro['id']))
				{
					$f_id_cartao = $filtro['id'];
					$var_where .= " AND cartao.id = '".$f_id_cartao."' ";
				}

				if ( isset($filtro['data_menor']) && isset($filtro['data_maior']) )
				{
					$f_dt_menor = $filtro['data_menor'];
					$f_dt_maior = $filtro['data_maior'];
					$var_where .= " AND ctl_acesso.data >= '".$f_dt_menor."' AND ctl_acesso.data <= '".$f_dt_maior."' ";
				}
			}

			$query = "  SELECT ctl_acesso.cod_cartao,
						       cartao.id,
						       ctl_acesso.cod_func,
						       funcionarios.nome_func,
						       ctl_acesso.cod_porta,
						       porta.descporta,
						       ctl_acesso.data,
						       ctl_acesso.hora

						  FROM ctl_acesso

						JOIN porta
						ON porta.cod_porta = ctl_acesso.cod_porta

						JOIN funcionarios
						ON funcionarios.cod_func = ctl_acesso.cod_func

						JOIN cartao
						ON cartao.cod_cartao = ctl_acesso.cod_cartao

				     WHERE 1 = 1

				     ".$var_where."

						ORDER BY ctl_acesso.data, 
						         ctl_acesso.hora,
						         ctl_acesso.cod_func,
						         ctl_acesso.cod_porta";

			$stmt = $this->conn->prepare($query);
			$stmt->execute();
			
			if($stmt->rowCount() == 0){
				return '{"erro":true}';
			} else {

			$text = "Relatório de Acessos" . chr(13) . chr(10);

			$text .= "----------------------------------------------------------------------------------------------------------------------------------------" . chr(13) . chr(10);

			$text .= "Código Cartão | ID Cartão            | Cod Func | Nome Func                     | Código Porta | Descr Porta    | Data        | Hora    " . chr(13) . chr(10);

			$text .= "----------------------------------------------------------------------------------------------------------------------------------------" . chr(13) . chr(10);

			while ($ln = $stmt->fetch(\PDO::FETCH_ASSOC))
			{
				$cod_cartao = str_pad($ln['cod_cartao'], '13', ' ', STR_PAD_LEFT) . '   ';
				$id_cartao = str_pad($ln['id'], '20', ' ', STR_PAD_LEFT) . '   ';
				$cod_func = str_pad($ln['cod_func'], '8', ' ', STR_PAD_LEFT) . '   ';
				$nome_func = substr(str_pad($ln['nome_func'], '29', ' ', STR_PAD_RIGHT),0,29) . '   ';
				$cod_porta = str_pad($ln['cod_porta'], '12', ' ', STR_PAD_LEFT) . '   ';
				$descporta = substr(str_pad($ln['descporta'], '15', ' ', STR_PAD_RIGHT),0,15) . '   ';
				$data = str_pad($ln['data'], '10', ' ', STR_PAD_LEFT) . '   ';
				$hora = str_pad($ln['hora'], '8', ' ', STR_PAD_LEFT) . '   ';

				$text .= $cod_cartao;
				$text .= $id_cartao;
				$text .= $cod_func;
				$text .= $nome_func;
				$text .= $cod_porta;
				$text .= $descporta;
				$text .= $data;
				$text .= $hora;

				$text .= chr(13) . chr(10);
			}

			$filedir = $_SERVER['DOCUMENT_ROOT'] . '/TCC/rel/';
			$filename = "Relatorio_" . date('Y') . '_' . date('m') . '_' . date('d') . '_' . date('H') . '_' . date('i') . '_' . date('s') . '.txt';

			$file = $filedir.$filename;

			$link = $_SERVER['HTTP_HOST'] . '/TCC/rel/'.$filename;

			$fp = fopen($file, "w", 'Big5');

			fwrite($fp, mb_convert_encoding($text, 'UTF-8'));

			fclose($fp);

			return $this->indent_json('{"erro":false, "link_acesso":"'.$link.'"}');
			}
		} catch (PDOException $e) {
			return '{"erro":true}';
		}
	}
}

?>