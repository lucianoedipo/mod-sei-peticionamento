<?
/**
* ANATEL
*
* 29/06/2016 - criado por marcelo.bezerra - CAST
*
*/

require_once dirname(__FILE__).'/../../../SEI.php';

class GerirTipoContextoPeticionamentoRN extends InfraRN {
	
	public function __construct() {
		parent::__construct ();
	}
	
	protected function inicializarObjInfraIBanco() {
		return BancoSEI::getInstance ();
	}
		
	/**
	 * Short description of method excluirControlado
	 *
	 * @access protected
	 * @author Marcelo Bezerra <marcelo.bezerra@castgroup.com.br>
	 * @param $objDTO
	 * @return void
	 */
	protected function excluirControlado($objDTO){
		
		try {
	
			//Valida Permissao
			SessaoSEI::getInstance ()->validarAuditarPermissao ('gerir_tipo_contexto_peticionamento_cadastrar', __METHOD__, $objDTO );
			
			$objExtArqPermBD = new RelTipoContextoPeticionamentoBD($this->getObjInfraIBanco());
			for($i=0;$i<count($objDTO);$i++){
				$objExtArqPermBD->excluir($objDTO[$i]);
			}
	
			//Auditoria
	
		}catch(Exception $e){
			throw new InfraException('Erro excluindo Extens�o.',$e);
		}
	}
	
	/**
	 * Short description of method listarConectado
	 *
	 * @access protected
	 * @author Marcelo Bezerra <marcelo.bezerra@castgroup.com.br>
	 * @param $objDTO
	 * @return mixed
	 */
	protected function listarConectado(RelTipoContextoPeticionamentoDTO  $objDTO) {
	
		try {
	
			//Regras de Negocio
			$objRelTipoContextoPeticionamentoBD = new RelTipoContextoPeticionamentoBD($this->getObjInfraIBanco());
			$ret = $objRelTipoContextoPeticionamentoBD->listar($objDTO);				
			return $ret;
			
		} catch (Exception $e) {
			throw new InfraException ('Erro listando Tipo de Interessado.', $e);
		}
	}
		
	/**
	 * Short description of method consultarConectado
	 *
	 * @access protected
	 * @author Marcelo Bezerra <marcelo.bezerra@castgroup.com.br>
	 * @param  $objDTO
	 * @return mixed
	 */
	protected function consultarConectado(RelTipoContextoPeticionamentoDTO  $objDTO) {
		
		try {
			
			// Valida Permissao			
		    $objTamanhoArquivoBD = new RelTipoContextoPeticionamentoBD($this->getObjInfraIBanco());
			$ret = $objTamanhoArquivoBD->consultar($objTamanhoArquivoDTO);			
			return $ret;
			
		} catch ( Exception $e ) {
			throw new InfraException('Erro consultando Tipo de Interessado.', $e);
		}
	}
	
	/**
	 * Short description of method cadastrarControlado
	 *
	 * @access protected
	 * @author Marcelo Bezerra <marcelo.bezerra@castgroup.com.br>
	 * @param  $objDTO
	 * @return mixed
	 */
	protected function cadastrarControlado(RelTipoContextoPeticionamentoDTO  $objDTO) {
		
		try {
			// Valida Permissao
			SessaoSEI::getInstance ()->validarAuditarPermissao ('gerir_tipo_contexto_peticionamento_cadastrar', __METHOD__, $objDTO );	
			$objExtArqPermBD = new RelTipoContextoPeticionamentoBD($this->getObjInfraIBanco());
			$ret = $objExtArqPermBD->cadastrar($objDTO);
			return $ret;
			
		} catch ( Exception $e ) {
			throw new InfraException ('Erro cadastrando Tipo de Interessado.', $e );
		}
	}
		
	private function validarTiposReservados( $arrRelTipoContextoPeticionamentoDTO, InfraException $objInfraException){
	
		$strMensagem = "";
		
		if( is_array( $arrRelTipoContextoPeticionamentoDTO ) && count( $arrRelTipoContextoPeticionamentoDTO ) > 0 ){
			
			$arrIdTipo = array();
			
			foreach( $arrRelTipoContextoPeticionamentoDTO as $itemDTO ){
				
				$idTipo = $itemDTO->getNumIdTipoContextoContato();
				array_push( $arrIdTipo , $idTipo);
			}
			
			$objTipoContatoRN = new TipoContatoRN();
			$objTipoContatoDTO = new TipoContatoDTO();
			$objTipoContatoDTO->retTodos();
						
			$objTipoContatoDTO->adicionarCriterio(array('SinSistema', 'IdTipoContato'),
					array( InfraDTO::$OPER_IGUAL , InfraDTO::$OPER_IN ),
					array( 'S', $arrIdTipo ) , 
					InfraDTO::$OPER_LOGICO_AND
			);
						
			$arrTipoContatoReservado = $objTipoContatoRN->listarRN0337( $objTipoContatoDTO );
						
			//se tiver tipos do sistema, monta mensagem de erro
			if( is_array( $arrTipoContatoReservado ) && count( $arrTipoContatoReservado ) > 0 ){
				
				foreach( $arrTipoContatoReservado as $itemTipoContatoDTO ){
					
					$strMensagem .= $itemTipoContatoDTO->getStrNome() . "\n" ;
				}
				
			}
			
		}
				
		if( $strMensagem != ""){
		  $objInfraException->adicionarValidacao( " Nao permitido adicionar tipos de interessado reservados do sistema. Os seguintes tipos de interessado n�o s�o permitidos: \n ". $strMensagem );
		}
		
	}
	
	protected function cadastrarMultiploControlado( $arrPrincipal ){
		
		$objInfraException = new InfraException();
					
		// excluindo registros anteriores
		$objDTO = new RelTipoContextoPeticionamentoDTO();
		$objDTO->retTodos();
		$cadastro = $arrPrincipal['cadastro'];
		
		if( $cadastro == 'S'){
		  $objDTO->setStrSinCadastroInteressado('S');
		  $objDTO->setStrSinSelecaoInteressado('N');
		
		} else if( $cadastro == 'N'){
		  $objDTO->setStrSinCadastroInteressado('N');
		  $objDTO->setStrSinSelecaoInteressado('S');
		}
						
		unset( $arrPrincipal['cadastro'] );
		
		$lista = $this->listar($objDTO);
		$this->validarTiposReservados( $lista, $objInfraException );
		
		$this->excluir( $lista );
		
		//$arrPrincipal = PaginaSEI::getInstance()->getArrValuesSelect($_POST['hdnPrincipal']);
		
		if(!$arrPrincipal) {
			$objInfraException->adicionarValidacao('Informe pelo menos um tipo de interessado.');
		}
		
		$objInfraException->lancarValidacoes();
		
		foreach($arrPrincipal as $numPrincipal){
			
			$objDTO = new RelTipoContextoPeticionamentoDTO();
			$objDTO->setNumIdTipoContextoContato($numPrincipal);
			
			if( $cadastro == 'S'){
				$objDTO->setStrSinCadastroInteressado('S');
				$objDTO->setStrSinSelecaoInteressado('N');
			
			} else if( $cadastro == 'N'){
				$objDTO->setStrSinCadastroInteressado('N');
				$objDTO->setStrSinSelecaoInteressado('S');
			}
			
			$objDTO = $this->cadastrar($objDTO);
		}
		
	}
	
}
?>