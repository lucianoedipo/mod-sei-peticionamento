<?
/**
 * TRIBUNAL REGIONAL FEDERAL DA 4� REGI�O
 *
 * 29/03/2017 - criado por marcelo.cast
 *
 * Vers�o do Gerador de C�digo: 1.40.0
 */

require_once dirname(__FILE__) . '/../../../SEI.php';

class MdPetAgendamentoAutomaticoRN extends InfraRN
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    /* M�todo que realiza cumprimento autom�tico de intima��es por decurso de prazo */
    protected function CumprirPorDecursoPrazoTacitoControlado()
    {

        try {

            ini_set('max_execution_time', '0');
            ini_set('memory_limit', '1024M');

            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            //SessaoSEI::getInstance(false);     Nao precisa porque ja tem abaixo com mais parametros para simular login

            $objUsuarioPetRN = new MdPetIntUsuarioRN();
            $idUsuarioPet = $objUsuarioPetRN->getObjUsuarioPeticionamento(true);
            SessaoSEI::getInstance(false)->simularLogin(null, SessaoSEI::$UNIDADE_TESTE, $idUsuarioPet, null);

            $numSeg = InfraUtil::verificarTempoProcessamento();
            InfraDebug::getInstance()->gravar('CUMPRINDO INTIMACOES POR DECURSO DE PRAZO');

            $objMdPetIntAceiteRN = new MdPetIntAceiteRN();
            $intimacoesPendentes = $objMdPetIntAceiteRN->verificarIntimacoesPrazoExpirado();

            InfraDebug::getInstance()->gravar('Qtd. Intimacoes Pendentes: ' . count($intimacoesPendentes));

            if (count($intimacoesPendentes) > 0) {
                $arrIntimacoes = $objMdPetIntAceiteRN->realizarEtapasAceiteAgendado($intimacoesPendentes);

                InfraDebug::getInstance()->gravar('Qtd. Intimacoes Cumpridas: ' . $arrIntimacoes['cumpridas']);
                InfraDebug::getInstance()->gravar('Qtd. Intimacoes Nao Cumpridas: ' . $arrIntimacoes['naoCumpridas']);

                if(isset($arrIntimacoes['procedimentos'])) {
                    foreach ($arrIntimacoes['procedimentos'] as $procedimentos) {
                        InfraDebug::getInstance()->gravar('Processo n� ' . $procedimentos[0] . ' - Motivo: ' . $procedimentos[1]);
                    }
                }
                if($arrIntimacoes['erros']){
                    foreach ($arrIntimacoes['erros'] as $procedimentos) {
                        InfraDebug::getInstance()->gravar($procedimentos[0] . ' - Motivo: ' . $procedimentos[1]);
                    }
                }
            }
            
            $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
            InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: ' . $numSeg . ' s');
            InfraDebug::getInstance()->gravar('FIM');
            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(), InfraLog::$INFORMACAO);

            //InfraDebug::getInstance()->setBolLigado(false);
            //InfraDebug::getInstance()->setBolDebugInfra(false);
            //InfraDebug::getInstance()->setBolEcho(false);
        } catch (Exception $e) {
            //SessaoSEI::getInstance(false)->setBolHabilitada(true);
            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(true);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);

            throw new InfraException('Erro cumprindo intimacao por decurso de prazo.', $e);
        }

    }

    protected function AtualizarSituacaoProcuracaoSimplesVencidaControlado( ){

        try {
            
            ini_set('max_execution_time','0');
            ini_set('memory_limit','1024M');

            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);
           
            $numSeg = InfraUtil::verificarTempoProcessamento();
            InfraDebug::getInstance()->gravar('ALTERANDO SITUACAO DE PROCURACOES VENCIDAS');

            //Alterando o Status das Procura��es Eletr�nicas
            $objMdPetVincRepresentantDTO = new MdPetVincRepresentantDTO();
            $objMdPetVincRepresentantDTO->retDthDataLimite();
            $objMdPetVincRepresentantDTO->retStrStaEstado();
            $objMdPetVincRepresentantDTO->retNumIdMdPetVinculoRepresent();
            $objMdPetVincRepresentantRN = new MdPetVincRepresentantRN();
            $objMdPetVincRepresentantRN = $objMdPetVincRepresentantRN->listar($objMdPetVincRepresentantDTO);
            $contador = 0;
            foreach ($objMdPetVincRepresentantRN as $dto) {

                if($dto->getStrStaEstado() == MdPetVincRepresentantRN::$RP_ATIVO){
                    if(infraData::compararDatas(infraData::getStrDataAtual(),$dto->getDthDataLimite()) < 0){
                        
                        $objMdPetVincRepresentantDTO = new MdPetVincRepresentantDTO();
                        $objMdPetVincRepresentantDTO->setNumIdMdPetVinculoRepresent($dto->getNumIdMdPetVinculoRepresent());
                        $objMdPetVincRepresentantDTO->setStrStaEstado(MdPetVincRepresentantRN::$RP_VENCIDA);
                        $objMdPetVincRepresentantRN = new MdPetVincRepresentantRN();
                        $objMdPetVincRepresentantRN = $objMdPetVincRepresentantRN->alterar($objMdPetVincRepresentantDTO);
                        $contador += 1;

                }
            }
        }
           
            InfraDebug::getInstance()->gravar('Qtd. Procuracoes Vencidas: '.$contador.' ');

            $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
            InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: '.$numSeg.' s');
            InfraDebug::getInstance()->gravar('FIM');
            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(),InfraLog::$INFORMACAO);

            //InfraDebug::getInstance()->setBolLigado(false);
            //InfraDebug::getInstance()->setBolDebugInfra(false);
            //InfraDebug::getInstance()->setBolEcho(false);
        } catch (Exception $e) {
            //SessaoSEI::getInstance(false)->setBolHabilitada(true);
            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(true);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);

            throw new InfraException('Erro alterando situacao da procuracao eletronica.',$e);
        }

    }


    /* Metodo que reintera via email acerca de Intimacao Eletronica:
     *    - Com Tipo de Resposta que Exige Resposta pelo Usuario Externo 
     *    e Pendente de Resposta apos a Intimacao ter sido Cumprida.
     */
    protected function ReiterarIntimacaoExigeRespostaControlado()
    {

        try {

            ini_set('max_execution_time', '0');
            ini_set('memory_limit', '1024M');

            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);

            $numSeg = InfraUtil::verificarTempoProcessamento();
            InfraDebug::getInstance()->gravar('REITERANDO INTIMACOES PENDENTES EXIGE RESPOSTA');

            $objMdPetIntimacaoRN = new MdPetIntimacaoRN();

            $intimacoesExigeRespostaDTO = $objMdPetIntimacaoRN->getIntimacoesPossuemData(array(true, true));

            //Juridico DTO
            $intimacoesExigeRespostaJuridicoDTO = $objMdPetIntimacaoRN->getIntimacoesPossuemDataJuridico(array(true, true));

            InfraDebug::getInstance()->gravar('Qtd. Intimacoes Exige Resposta: ' . count($intimacoesExigeRespostaDTO));

            if (count($intimacoesExigeRespostaDTO) > 0) {
                $objMdPetIntEmailNotificacaoRN = new MdPetIntEmailNotificacaoRN();

                InfraDebug::getInstance()->setBolLigado(true);
                InfraDebug::getInstance()->setBolDebugInfra(false);
                InfraDebug::getInstance()->setBolEcho(false);

                SessaoSEI::getInstance(false);

                InfraDebug::getInstance()->gravar('REITERANDO INTIMACOES PENDENTES EXIGE RESPOSTA');
                InfraDebug::getInstance()->gravar('Qtd. Intimacoes Exige Resposta: ' . count($intimacoesExigeRespostaDTO));

                $qtdEnviadas = $objMdPetIntEmailNotificacaoRN->enviarEmailReiteracaoIntimacao(array($intimacoesExigeRespostaDTO, $pessoa = "F"));
                if (is_numeric($qtdEnviadas['qtdEnviadas'])) {
                    InfraDebug::getInstance()->gravar('Qtd. Intimacoes Reiteradas: ' . $qtdEnviadas['qtdEnviadas']);
                }
                if (is_numeric($qtdEnviadas['qtdN�oEnviadas'])) {
                    InfraDebug::getInstance()->gravar('Qtd. Intimacoes N�o Reiteradas: ' . $qtdEnviadas['qtdN�oEnviadas']);
                }
                if (is_array($qtdEnviadas['arrDadosEmailNaoEnviados']) && count($qtdEnviadas['arrDadosEmailNaoEnviados']) > 0) {
                    foreach($qtdEnviadas['arrDadosEmailNaoEnviados'] as $email) {
                        InfraDebug::getInstance()->gravar('N� Processo: ' . $email['processo'] . ' - Usu�rio Externo: ' . $email['nome_usuario_externo'] . ' - E-mail Usu�rio Externo: ' . $email['email_usuario_externo']);
                    }
                }
            }

            //Juridico
            InfraDebug::getInstance()->gravar('Qtd. Intimacoes Exige Resposta: ' . count($intimacoesExigeRespostaJuridicoDTO));

            if (count($intimacoesExigeRespostaJuridicoDTO) > 0) {
                $objMdPetIntEmailNotificacaoRN = new MdPetIntEmailNotificacaoRN();

                InfraDebug::getInstance()->setBolLigado(true);
                InfraDebug::getInstance()->setBolDebugInfra(false);
                InfraDebug::getInstance()->setBolEcho(false);

                SessaoSEI::getInstance(false);

                InfraDebug::getInstance()->gravar('REITERANDO INTIMACOES PENDENTES EXIGE RESPOSTA');
                InfraDebug::getInstance()->gravar('Qtd. Intimacoes Exige Resposta: ' . count($intimacoesExigeRespostaJuridicoDTO));

                $qtdEnviadasJuridico = $objMdPetIntEmailNotificacaoRN->enviarEmailReiteracaoIntimacaoJuridico(array($intimacoesExigeRespostaJuridicoDTO, $pessoa = "J"));
                if (is_numeric($qtdEnviadasJuridico['qtdEnviadas'])) {
                    InfraDebug::getInstance()->gravar('Qtd. Intimacoes Reiteradas: ' . $qtdEnviadasJuridico['qtdEnviadas']);
                }
                if (is_numeric($qtdEnviadasJuridico['qtdN�oEnviadas'])) {
                    InfraDebug::getInstance()->gravar('Qtd. Intimacoes N�o Reiteradas: ' . $qtdEnviadasJuridico['qtdN�oEnviadas']);
                }
                if (is_array($qtdEnviadasJuridico['arrDadosEmailNaoEnviados']) && count($qtdEnviadasJuridico['arrDadosEmailNaoEnviados']) > 0) {
                    foreach($qtdEnviadasJuridico['arrDadosEmailNaoEnviados'] as $email) {
                        InfraDebug::getInstance()->gravar('N� Processo: ' . $email['processo'] . ' - Usu�rio Externo: ' . $email['nome_usuario_externo'] . ' - E-mail Usu�rio Externo: ' . $email['email_usuario_externo']);
                    }
                }
            }


            $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
            InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: ' . $numSeg . ' s');
            InfraDebug::getInstance()->gravar('FIM');
            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(), InfraLog::$INFORMACAO);

        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(true);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);

            throw new InfraException('Erro reiterando intimacoes pendentes exige resposta.', $e);
        }

    }

    protected function atualizarEstadoIntimacoesPrazoExternoVencidoControlado()
    {

        try {

            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);

            $numSeg = InfraUtil::verificarTempoProcessamento();
            InfraDebug::getInstance()->gravar('ATUALIZANDO O ESTADO DE INTIMACOES VENCIDAS E SEM RESPOSTA');

            $objMdPetIntRelDestRN = new MdPetIntRelDestinatarioRN();

            $intimacoesVencidas = $objMdPetIntRelDestRN->retornaAtualizaIntimacoesSemRespostaVencidas();

            InfraDebug::getInstance()->gravar('Qtd. Intimacoes Vencidas e Sem Resposta: ' . $intimacoesVencidas);

            $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
            InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: ' . $numSeg . ' s');
            InfraDebug::getInstance()->gravar('FIM');

            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(), InfraLog::$INFORMACAO);

        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(true);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);

            throw new InfraException('Erro atualizando o estado das intimacoes vencidas e sem resposta.', $e);
        }

    }

    protected function atualizarEstadoTodasIntimacoesControlado()
    {
        try {

            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);

            $numSeg = InfraUtil::verificarTempoProcessamento();
            InfraDebug::getInstance()->gravar('ATUALIZANDO O ESTADO DE TODAS AS INTIMACOES JA REALIZADAS NO SEI');

            $objMdPetRegrasGeraisRN = new MdPetRegrasGeraisRN();
            $objMdPetIntRelDestRN = new MdPetIntRelDestinatarioRN();

            $objMdPetIntRelDestDTO = new MdPetIntRelDestinatarioDTO();
            $objMdPetIntRelDestDTO->retNumIdMdPetIntRelDestinatario();

            $count = $objMdPetIntRelDestRN->contar($objMdPetIntRelDestDTO);

            if ($count > 0) {
                $arrRetornoDTO = $objMdPetIntRelDestRN->listar($objMdPetIntRelDestDTO);
                $idsRelDest = InfraArray::converterArrInfraDTO($arrRetornoDTO, 'IdMdPetIntRelDestinatario');
                $arrSituacoesAtuais = $objMdPetRegrasGeraisRN->retornaSituacoesIntimacoes(array($idsRelDest, true));
                $arrSituacaoSeparado = $objMdPetRegrasGeraisRN->formatarArrSituacoes($arrSituacoesAtuais);

                $objMdPetIntRelDestRN->atualizarCadaEstadoIntimacao($arrSituacaoSeparado, MdPetIntimacaoRN::$INTIMACAO_PENDENTE);
                $objMdPetIntRelDestRN->atualizarCadaEstadoIntimacao($arrSituacaoSeparado, MdPetIntimacaoRN::$INTIMACAO_CUMPRIDA_PRAZO);
                $objMdPetIntRelDestRN->atualizarCadaEstadoIntimacao($arrSituacaoSeparado, MdPetIntimacaoRN::$INTIMACAO_CUMPRIDA_POR_ACESSO);
                $objMdPetIntRelDestRN->atualizarCadaEstadoIntimacao($arrSituacaoSeparado, MdPetIntimacaoRN::$INTIMACAO_RESPONDIDA);
                $objMdPetIntRelDestRN->atualizarCadaEstadoIntimacao($arrSituacaoSeparado, MdPetIntimacaoRN::$INTIMACAO_PRAZO_VENCIDO);
            }

            InfraDebug::getInstance()->gravar('Qtd. Intimacoes Atualizadas: ' . $count);

            $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
            InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: ' . $numSeg . ' s');
            InfraDebug::getInstance()->gravar('FIM');

            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(), InfraLog::$INFORMACAO);

        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(true);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false);

            throw new InfraException('Erro atualizando o estado das intimacoes.', $e);
        }


    }
}

?>