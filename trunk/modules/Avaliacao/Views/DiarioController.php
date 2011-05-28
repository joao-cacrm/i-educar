<?php

#error_reporting(E_ALL);
#ini_set("display_errors", 1);

/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *           <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author   ?
 * @category  i-Educar
 * @license   @@license@@
 * @package   Avaliacao
 * @subpackage  Modules
 * @since     Arquivo disponível desde a versão ?
 * @version   $Id$
 */

require_once 'CoreExt/View/Helper/UrlHelper.php';
require_once 'CoreExt/View/Helper/TableHelper.php';
require_once 'Core/Controller/Page/EditController.php';
require_once 'Core/Controller/Page/ListController.php';
require_once 'App/Model/IedFinder.php';
require_once 'Avaliacao/Model/NotaAlunoDataMapper.php';
require_once 'Avaliacao/Model/FaltaAlunoDataMapper.php';
require_once 'Avaliacao/Service/Boletim.php';
require_once 'RegraAvaliacao/Model/TipoPresenca.php';
require_once 'App/Model/MatriculaSituacao.php';

require_once 'include/clsDetalhe.inc.php';
require_once 'include/clsBase.inc.php';
require_once 'include/clsListagem.inc.php';
require_once 'include/clsBanco.inc.php';
require_once 'include/pmieducar/geral.inc.php';
require_once 'CoreExt/View/Helper/UrlHelper.php';

require_once 'include/pmieducar/clsPmieducarEscola.inc.php';
require_once 'include/pmieducar/clsPmieducarMatricula.inc.php';
require_once 'include/pmieducar/clsPmieducarMatriculaTurma.inc.php';
require_once 'include/pmieducar/clsPmieducarTurma.inc.php';
require_once 'include/pmieducar/clsPmieducarAluno.inc.php';

require_once 'include/portabilis_utils.php';

/**
 * BoletimController class.
 *
 * @author    Eriksen Costa Paixão <eriksen.paixao_bs@cobra.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   Avaliacao
 * @subpackage  Modules
 * @since     Classe disponível desde a versão 1.1.0
 * @version   @@package_version@@
 */

class DiarioController extends Core_Controller_Page_ListController
{
  protected $_dataMapper = 'Avaliacao_Model_NotaAlunoDataMapper';
  protected $_titulo   = 'Novo boletim';
//  protected $_processoAp = 946;
  protected $_processoAp = 644;

  protected $_formMap  = array();

  public function Gerar()
  {
    $this->ref_cod_aluno = $_GET['ref_cod_aluno'];
    if ($this->ref_cod_aluno)
    {
      $_a = new clsPmieducarAluno();
      $_a = $_a->lista($int_cod_aluno = $this->ref_cod_aluno);
      $this->nm_aluno = $_a[0]['nome_aluno'];
    }

    $this->ref_cod_instituicao = $_GET['ref_cod_instituicao'];
    $this->ref_cod_escola = $_GET['ref_cod_escola'];
    $this->ref_cod_curso = $_GET['ref_cod_curso'];
    $this->ref_cod_turma = $_GET['ref_cod_turma'];
    $this->ref_ref_cod_serie = $this->ref_cod_serie = $_GET['ref_ref_cod_serie'];
    $this->ano_escolar = $_GET['ano_escolar'];
    $this->ref_cod_componente_curricular = $_GET['ref_cod_componente_curricular'];
    $this->etapa = $_GET['etapa'];

    $get_escola = $escola_obrigatorio = $listar_escolas_alocacao_professor = TRUE;
    $get_ano_escolar = $ano_escolar_obrigatorio = TRUE;
    $get_curso = $curso_obrigatorio = $listar_somente_cursos_funcao_professor = TRUE;
    #$sem_padrao       = TRUE;
    $get_escola_curso_serie = $escola_curso_serie_obrigatorio = TRUE;
    $get_turma = $turma_obrigatorio = $listar_turmas_periodo_alocacao_professor = TRUE;
    $get_componente_curricular = $componente_curricular_obrigatorio = $listar_componentes_curriculares_professor = TRUE;
    $get_etapa = $etapa_obrigatorio = TRUE;
    include 'include/pmieducar/educar_campo_lista.php';

    $this->campoTexto('nm_aluno', 'Aluno', $this->nm_aluno, 30, 255, FALSE,
      FALSE, FALSE, '', "<img border=\"0\" onclick=\"pesquisa_aluno();\" id=\"ref_cod_aluno_lupa\" name=\"ref_cod_aluno_lupa\" src=\"imagens/lupa.png\"\/>", '', '', TRUE);
    $this->campoOculto('ref_cod_aluno', $this->ref_cod_aluno);

    /*
      $opcoes = array(
        '' => 'Selecione',
        1  => 'Aprovado',
        2  => 'Reprovado',
        3  => 'Em Andamento',
        4  => 'Não iniciado',
        );

      $this->campoLista('aprovado', 'Situa&ccedil;&atilde;o', $opcoes,
                        $this->aprovado, '', '', '', '', FALSE, FALSE);
    */

    if ($this->ref_cod_escola && $this->ref_cod_curso && $this->ref_cod_turma && $this->ref_ref_cod_serie && $this->ano_escolar && $this->ref_cod_componente_curricular && $this->etapa)
    {
      $obj_nota_aluno = new clsPmieducarMatriculaTurma();

      #TODO ordenar pelo nome do aluno
      $obj_nota_aluno->setOrderby('nome');

      $alunos = $obj_nota_aluno->lista(
        $this->ref_cod_matricula,
        $this->ref_cod_turma,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        1,
        $this->ref_ref_cod_serie,
        $this->ref_cod_curso,
        $this->ref_cod_escola,
        $this->ref_cod_instituicao,
        $this->ref_cod_aluno,
        NULL,
        $this->aprovado,
        NULL,
        NULL,
        $this->ano_escolar,
        TRUE,
        FALSE,
        NULL,
        1,
        TRUE,
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL
      );

      // monta a lista
      if (is_array($alunos) && count($alunos)) {
        $ref_cod_serie  = '';
        $nm_serie     = '';
        $ref_cod_escola = '';
        $nm_escola    = '';

        foreach ($alunos as $aluno) {
          $service = new Avaliacao_Service_Boletim(array(
            'matricula' =>   $aluno['ref_cod_matricula'],
            'usuario'   => $this->getSession()->id_pessoa
        ));

        $headers = array(
          "Matr&iacute;cula",
          "Aluno",
          "Situa&ccedil;&atilde;o");

        if ($service->getRegra()->get('tipoNota') != RegraAvaliacao_Model_Nota_TipoValor::NENHUM)
        {
          $headers[] ="Nota";
        }
  
        $headers[] =   "Falta *";

        // Itens a mostrar na listagem de alunos
        $listagem_alunos = array();

        $listagem_alunos[] =   $aluno['ref_cod_matricula'];
        $listagem_alunos[] =   $aluno['ref_cod_aluno'] . ' - ' .  $aluno['nome'];

        $situacao = App_Model_MatriculaSituacao::getInstance()->getValue(
              $service->getSituacaoComponentesCurriculares()->componentesCurriculares[$this->ref_cod_componente_curricular]->situacao);

        $listagem_alunos[] = sprintf('<span id="situacao-matricula:%s">%s</span>',   $aluno['ref_cod_matricula'],$situacao);

        if ($service->getRegra()->get('tipoNota') != RegraAvaliacao_Model_Nota_TipoValor::NENHUM)
        {

          $onChangeSelectNota = sprintf("setAtt(att='nota', matricula=%s, etapa=%s, componente_curricular=%s);",
                            $aluno['ref_cod_matricula'], $this->etapa, $this->ref_cod_componente_curricular);
          // Valores de arredondamento
          $valoresArredondamento = $service->getRegra()->tabelaArredondamento->findTabelaValor();
          $valores = array();
          foreach ($valoresArredondamento as $valor) {
            if ($service->getRegra()->get('tipoNota') == RegraAvaliacao_Model_Nota_TipoValor::NUMERICA) {
              $valores[(string) $valor->nome] = $valor->nome;
            }
            else {
              $valores[(string) $valor->valorMaximo] = $valor->nome . ' (' . $valor->descricao .  ')';
            }
          }

          $_notaAtual = urldecode($service->getNotaComponente($this->ref_cod_componente_curricular, $this->etapa)->nota);
          $_notaAtual = str_replace(',', '.', $_notaAtual);
          $notas = "<option></option>";
          foreach ($valores as $k => $v) {
            $k = str_replace(',', '.', urldecode($k));
            if ($_notaAtual > -1 && $k == $_notaAtual)
            {
              $notas .= "\n<option value='$k' selected='selected'>$v</option>";
            }
            else
            $notas .= "\n<option value='$k'>$v</option>";
          }
          $listagem_alunos[] = sprintf('<select id="nota-matricula:%s" class="notas" onchange="%s">%s</select>',
                    $aluno['ref_cod_matricula'], $onChangeSelectNota, $notas);
        }

        $onChangeSelectFalta = sprintf("setAtt(att='falta', matricula=%s, etapa=%s, componente_curricular=%s);",
                      $aluno['ref_cod_matricula'], $this->etapa, $this->ref_cod_componente_curricular);

        if ($service->getRegra()->get('tipoPresenca') == RegraAvaliacao_Model_TipoPresenca::POR_COMPONENTE)
            $_faltaAtual = $service->getFalta($this->etapa, $this->ref_cod_componente_curricular)->quantidade;
        elseif ($service->getRegra()->get('tipoPresenca') == RegraAvaliacao_Model_TipoPresenca::GERAL)
            $_faltaAtual = $service->getFalta($this->etapa)->quantidade;

        $faltas = "<option></option>";
        foreach (range(0, 100, 1) as $f) {
          if ($_faltaAtual > -1 && $f == $_faltaAtual)
            $faltas .= "\n<option value='$f' selected='selected'>$f</option>";
          else
            $faltas .= "\n<option value='$f'>$f</option>";
        }
        $listagem_alunos[] = sprintf('<select id="falta-matricula:%s" class="faltas" onchange="%s">%s</select>',
                  $aluno['ref_cod_matricula'], $onChangeSelectFalta, $faltas);

        if ($service->getRegra()->get('parecerDescritivo') != RegraAvaliacao_Model_TipoParecerDescritivo::NENHUM)
        {
          if ($service->getRegra()->get('parecerDescritivo') == RegraAvaliacao_Model_TipoParecerDescritivo::ANUAL_DESCRITOR or
            $service->getRegra()->get('parecerDescritivo') == RegraAvaliacao_Model_TipoParecerDescritivo::ANUAL_COMPONENTE or
            $service->getRegra()->get('parecerDescritivo') == RegraAvaliacao_Model_TipoParecerDescritivo::ANUAL_GERAL)
          {
            $etapa_parecer = 'An';
            $onChangeParecer = sprintf("setAtt(att='parecer', matricula=%s, etapa='%s', componente_curricular=%s);",
                             $aluno['ref_cod_matricula'], $etapa_parecer, $this->ref_cod_componente_curricular);
          }
          else
          {
            $etapa_parecer = $this->etapa;
            $onChangeParecer = sprintf("setAtt(att='parecer', matricula=%s, etapa=%s, componente_curricular=%s);",
                             $aluno['ref_cod_matricula'], $etapa_parecer, $this->ref_cod_componente_curricular);
          }

          if ($service->getRegra()->get('parecerDescritivo') == RegraAvaliacao_Model_TipoParecerDescritivo::ETAPA_COMPONENTE or
            $service->getRegra()->get('parecerDescritivo') == RegraAvaliacao_Model_TipoParecerDescritivo::ANUAL_COMPONENTE)
          {
            $parecer = $service->getParecerDescritivo($etapa_parecer, $this->ref_cod_componente_curricular);
          }
          else
          {
            $parecer = $service->getParecerDescritivo($etapa_parecer);
          }

          $listagem_alunos[] = sprintf('<textarea id="parecer-matricula:%s" class="parecer" onchange="%s" cols="40" rows="10">%s</textarea>',
                        $aluno['ref_cod_matricula'], $onChangeParecer, utf8_decode($parecer));
        }

        $listagem_alunos[] = sprintf('<span id="status_alteracao-matricula:%s"</spam>',   $aluno['ref_cod_matricula']);

        $this->addLinhas($listagem_alunos);
        }

      $this->titulo = "Encontrado(s) " . count($alunos) . " aluno(s).";

      if ($service->getRegra()->get('parecerDescritivo') != RegraAvaliacao_Model_TipoParecerDescritivo::NENHUM)
        $headers[] = "Parecer descritivo **";

      $headers[] = "Status altera&ccedil;&atilde;o";
      $this->addCabecalhos($headers);

      $_tipoParecer = RegraAvaliacao_Model_TipoParecerDescritivo::getInstance()->getValue($service->getRegra()->get('parecerDescritivo'));
      if ($_tipoParecer)
        $_tipoParecer = '<br />** ' . $_tipoParecer;

      $_tipoPresenca = RegraAvaliacao_Model_TipoPresenca::getInstance()->getValue($service->getRegra()->get('tipoPresenca'));
      $this->rodape = "* $_tipoPresenca $_tipoParecer";
      }
    }
    else
    {
      $this->rodape = "<strong>N&atilde;o est&aacute; sendo listado as op&ccedil;&otilde;es de filtro que voc&ecirc; espera ?</strong> solicite a(o) secret&aacute;rio(a) da escola que verifique a aloca&ccedil;&atilde;o do seu usu&aacute;rio.";
    }

    $this->largura = '100%';
    $a = <<<EOT



        <style type="text/css">
          #formcadastro #nm_aluno, #formcadastro select {
            min-width: 400px;
          }

          .parecer {
            height: 80px;
          }
        </style>

        <script type="text/javascript">

            document.getElementById('botao_busca').value = 'Carregar';

            function pesquisa_aluno()
            {
              pesquisa_valores_popless('/intranet/educar_pesquisa_aluno.php')
            }

            var __bBusca = document.getElementById('botao_busca');
            var __old_event = __bBusca.onclick;
            __bBusca.onclick = function()
            {
              var __not_empty_fields = document.getElementsByClassName('obrigatorio');
              var __all_filled = true;
              for (var i = 0; i < __not_empty_fields.length; i++)
              {
                if (! __not_empty_fields[i].value)
                {
                  var __all_filled = false;
                  break;
                }
              }
              if (! __all_filled)
                alert('Selecione um valor em todos os campos, antes de continuar.');
              else
              {
                __bBusca.disable();
                __bBusca.value = 'Carregando...';
                var form = document.getElementById('form_resultado');
                var parent = form.parentNode;
                form.remove();                                
                t = document.createElement('p');
                t.align = 'center';
                //t.textContent = 'Por favor aguarde, carregando dados...'; 

                parent.appendChild(t);
                __old_event();
                //__bBusca.onclick = __old_event;
                //__bBusca.click(); bug no ie ?
              }
            }

            var __a = document.createElement('a');
            __a.innerHTML = 'Limpar filtros';
            __a.href = document.location.href.split('?')[0];
            __bBusca.parentNode.appendChild(__a);

              document.getElementById('ref_cod_instituicao').onchange = function()
              {
                clearSelect(entity = 'ano_escolar', disable = false, text = '', multipleId = false);
                clearSelect(entity = 'curso', disable = false, text = '', multipleId = true);
                clearSelect(entity = 'serie', disable = false, text = '', multipleId = true);
                clearSelect(entity = 'turma', disable = false, text = '', multipleId = true);
                clearSelect(entity = 'componente_curricular', disable = false, text = '', multipleId = true);
                clearSelect(entity = 'etapa', disable = false, text = '', multipleId = false);
                //getDuploEscolaCurso();
                getEscola();
              }

              document.getElementById('ref_cod_escola').onchange = function()
              {
                clearSelect(entity = 'ano_escolar', disable = false, text = '', multipleId = false);
                clearSelect(entity = 'curso', disable = false, text = '', multipleId = true);
                clearSelect(entity = 'serie', disable = false, text = '', multipleId = true);
                clearSelect(entity = 'turma', disable = false, text = '', multipleId = true);
                clearSelect(entity = 'componente_curricular', disable = false, text = '', multipleId = true);
                clearSelect(entity = 'etapa', disable = false, text = '', multipleId = false);
                getEscolaCurso();
              }


            document.getElementById('ref_cod_curso').onchange = function()



            {
              clearSelect(entity = 'ano_escolar', disable = false, text = '', multipleId = false);
              clearSelect(entity = 'serie', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'turma', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'componente_curricular', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'etapa', disable = false, text = '', multipleId = false);
              getAnoEscolar();
            }

            document.getElementById('ano_escolar').onchange = function()
            {
              clearSelect(entity = 'serie', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'turma', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'componente_curricular', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'etapa', disable = false, text = '', multipleId = false);
              getEscolaCursoSerie();
            }

            document.getElementById('ref_ref_cod_serie').onchange = function()
            {
              clearSelect(entity = 'turma', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'componente_curricular', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'etapa', disable = false, text = '', multipleId = false);
              getTurma();
            }

            document.getElementById('ref_cod_turma').onchange = function()
            {
              clearSelect(entity = 'componente_curricular', disable = false, text = '', multipleId = true);
              clearSelect(entity = 'etapa', disable = false, text = '', multipleId = false);
              getComponenteCurricular();
              getEtapa();
            }

        </script>

        <script type="text/javascript" src="/modules/Avaliacao/Static/ajax.js"> </script>
        <script type="text/javascript" src="/modules/Avaliacao/Static/dom_utils.js"> </script>
        <script type="text/javascript">

          var ajaxReq = new AjaxRequest();

          function setAtt(att, matricula, etapa, componente_curricular)
          {
            try
            {
              var attElement = document.getElementById(att + '-matricula:' + matricula);
              var attValue = attElement.value;

              //fix for bug in service boletim
              if (att == 'parecer' && ((/^\d+\.\d+$/.test(attValue)) || (/^\d+$/.test(attValue)) || (/^\.\d+$/.test(attValue)) || (/^\d+\.$/.test(attValue))))
                document.getElementById('status_alteracao-matricula:' + matricula).innerHTML = '<span class="error" style="color: red;">Informe pelo menos uma letra.</span>';
              else if (attValue.length)
              {
                var _c = ['notas', 'faltas', 'parecer'];
                for (var i = 0; i < _c.length; i++)
                {
                  var _e = document.getElementsByClassName(_c[i]);
                  for (var j = 0; j < _e.length; j++)
                    _e[j].disabled = true;
                }

                document.getElementById('status_alteracao-matricula:'+matricula).innerHTML = 'Atualizando... <img src="/modules/Avaliacao/Static/images/min-wait.gif"/>';
                var vars = "att="+att+"&matricula=" + matricula + "&etapa=" + etapa + "&componente_curricular=" + componente_curricular+"&att_value=" + attValue;
                //alert(vars);
                ajaxReq.send("POST", "/module/Avaliacao/DiarioAjax", handleRequest, "application/x-www-form-urlencoded; charset=UTF-8", vars);
              }
              else
                document.getElementById('status_alteracao-matricula:' + matricula).innerHTML = '<span class="error" style="color: red;">Selecione um valor v&aacute;lido.</span>';
            }
            catch(err)
            {
              try
              {
                document.getElementById('status_alteracao-matricula:' + matricula).innerHTML = '<span class="error" style="color: red;">ERRO1: Ocorreu um erro inesperado, por favor tente novamente.</span>';
                window.location.reload();
              }
              catch(err)
              {
                alert('ERRO2: Ocorreram erros inesperados, por favor tente novamente.');
                window.location.reload();
              }
            }
          }

          function handleRequest()
          {
            try
            {
              if (ajaxReq.getReadyState() == 4 && ajaxReq.getStatus() == 200)
              {
                var xmlData = ajaxReq.getResponseXML().getElementsByTagName("status")[0];
                var att = getText(xmlData.getElementsByTagName('att')[0]);
                var success = getText(xmlData.getElementsByTagName('success')[0]);
                var matricula = getText(xmlData.getElementsByTagName('matricula')[0]);
                document.getElementById(att + '-matricula:' + matricula).disabled = false;
                document.getElementById('botao_busca').disabled = false;

                var _c = ['notas', 'faltas', 'parecer'];
                for (var i = 0; i < _c.length; i++)
                {
                  var _e = document.getElementsByClassName(_c[i]);
                  for (var j = 0; j < _e.length; j++)
                    _e[j].disabled = false;
                }

                var situacao = getText(xmlData.getElementsByTagName('situacao')[0]);
                document.getElementById('situacao'  + '-matricula:' + matricula).innerHTML = situacao;

                if (success == '1')
                  var s = '<span class="success" style="color: green;">Atualizado</span>';
                else
                   var s = '<span class="error" style="color: red;">Erro ao atualizar, tente novamente !</span>';
                document.getElementById('status_alteracao-matricula:' + matricula).innerHTML = s;
              }
            }
            catch(err)
            {
              try
              {
                document.getElementById('status_alteracao-matricula:' + matricula).innerHTML = '<span class="error" style="color: red;">ERRO3: Ocorreu um erro inesperado, por favor tente novamente.</span>';
                window.location.reload();
              }
              catch(err)
              {
                alert('ERRO4: Ocorreram erros inesperados, por favor tente novamente.');
                window.location.reload();
              }
            }
          }
        </script>
EOT;

  $this->appendOutput($a);
  }
}
?>

