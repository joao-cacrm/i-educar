<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
    *                                                                        *
    *   @author Prefeitura Municipal de Itajaí                               *
    *   @updated 29/03/2007                                                  *
    *   Pacote: i-PLB Software Público Livre e Brasileiro                    *
    *                                                                        *
    *   Copyright (C) 2006  PMI - Prefeitura Municipal de Itajaí             *
    *                       ctima@itajai.sc.gov.br                           *
    *                                                                        *
    *   Este  programa  é  software livre, você pode redistribuí-lo e/ou     *
    *   modificá-lo sob os termos da Licença Pública Geral GNU, conforme     *
    *   publicada pela Free  Software  Foundation,  tanto  a versão 2 da     *
    *   Licença   como  (a  seu  critério)  qualquer  versão  mais  nova.    *
    *                                                                        *
    *   Este programa  é distribuído na expectativa de ser útil, mas SEM     *
    *   QUALQUER GARANTIA. Sem mesmo a garantia implícita de COMERCIALI-     *
    *   ZAÇÃO  ou  de ADEQUAÇÃO A QUALQUER PROPÓSITO EM PARTICULAR. Con-     *
    *   sulte  a  Licença  Pública  Geral  GNU para obter mais detalhes.     *
    *                                                                        *
    *   Você  deve  ter  recebido uma cópia da Licença Pública Geral GNU     *
    *   junto  com  este  programa. Se não, escreva para a Free Software     *
    *   Foundation,  Inc.,  59  Temple  Place,  Suite  330,  Boston,  MA     *
    *   02111-1307, USA.                                                     *
    *                                                                        *
    * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
require_once ("include/clsBase.inc.php");
require_once ("include/clsCadastro.inc.php");
require_once ("include/clsBanco.inc.php");
require_once ("include/pmieducar/geral.inc.php");
require_once ("include/modules/clsModulesAuditoriaGeral.inc.php");

class clsIndexBase extends clsBase
{
    function Formular()
    {
        $this->SetTitulo( "{$this->_instituicao} i-Educar - Escola Localiza&ccedil;&atilde;o" );
        $this->processoAp = "562";
        $this->addEstilo("localizacaoSistema");     
    }
}

class indice extends clsCadastro
{
    /**
     * Referencia pega da session para o idpes do usuario atual
     *
     * @var int
     */
    var $pessoa_logada;

    var $cod_escola_localizacao;
    var $ref_usuario_exc;
    var $ref_usuario_cad;
    var $nm_localizacao;
    var $data_cadastro;
    var $data_exclusao;
    var $ativo;
    var $ref_cod_instituicao;

    function Inicializar()
    {
        $retorno = "Novo";
        @session_start();
        $this->pessoa_logada = $_SESSION['id_pessoa'];
        @session_write_close();

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra( 562, $this->pessoa_logada, 3, "educar_escola_localizacao_lst.php" );

        $this->cod_escola_localizacao=$_GET["cod_escola_localizacao"];

        if( is_numeric( $this->cod_escola_localizacao ) )
        {
            $obj = new clsPmieducarEscolaLocalizacao( $this->cod_escola_localizacao );
            $registro  = $obj->detalhe();
            if( $registro )
            {
                foreach( $registro AS $campo => $val )  // passa todos os valores obtidos no registro para atributos do objeto
                    $this->$campo = $val;

                $this->fexcluir = true;
                $retorno = "Editar";
            }
        }
        $this->url_cancelar = ($retorno == "Editar") ? "educar_escola_localizacao_det.php?cod_escola_localizacao={$registro["cod_escola_localizacao"]}" : "educar_escola_localizacao_lst.php";

        $nomeMenu = $retorno == "Editar" ? $retorno : "Cadastrar";
        $localizacao = new LocalizacaoSistema();
        $localizacao->entradaCaminhos( array(
             $_SERVER['SERVER_NAME']."/intranet" => "In&iacute;cio",
             "educar_index.php"                  => "Escola",
             ""        => "{$nomeMenu} localiza&ccedil;&atilde;o"             
        ));
        $this->enviaLocalizacao($localizacao->montar());

        $this->nome_url_cancelar = "Cancelar";
        return $retorno;
    }

    function Gerar()
    {
        // primary keys
        $this->campoOculto( "cod_escola_localizacao", $this->cod_escola_localizacao );

        // Filtros de Foreign Keys
        $obrigatorio = true;
        include("include/pmieducar/educar_campo_lista.php");

        // text
        $this->campoTexto( "nm_localizacao", "Localiza&ccedil;&atilde;o", $this->nm_localizacao, 30, 255, true );
    }

    function Novo()
    {
        @session_start();
         $this->pessoa_logada = $_SESSION['id_pessoa'];
        @session_write_close();

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra( 562, $this->pessoa_logada, 3, "educar_escola_localizacao_lst.php" );

        $obj = new clsPmieducarEscolaLocalizacao( null,null,$this->pessoa_logada,$this->nm_localizacao,null,null,1,$this->ref_cod_instituicao );
        $cadastrou = $obj->cadastra();
        if( $cadastrou )
        {
            $escolaLocalizacao = new clsPmieducarEscolaLocalizacao($cadastrou);
            $escolaLocalizacao = $escolaLocalizacao->detalhe();

            $auditoria = new clsModulesAuditoriaGeral("escola_localizacao", $this->pessoa_logada, $cadastrou);
            $auditoria->inclusao($escolaLocalizacao);

            $this->mensagem .= "Cadastro efetuado com sucesso.<br>";
            header( "Location: educar_escola_localizacao_lst.php" );
            die();
            return true;
        }

        $this->mensagem = "Cadastro n&atilde;o realizado.<br>";
        echo "<!--\nErro ao cadastrar clsPmieducarEscolaLocalizacao\nvalores obrigatorios\nis_numeric( $this->pessoa_logada ) && is_numeric( $this->ref_cod_instituicao ) && is_string( $this->nm_localizacao )\n-->";
        return false;
    }

    function Editar()
    {
        @session_start();
         $this->pessoa_logada = $_SESSION['id_pessoa'];
        @session_write_close();

        $escolaLocalizacaoDetalhe = new clsPmieducarEscolaLocalizacao($this->cod_escola_localizacao);
        $escolaLocalizacaoDetalheAntes = $escolaLocalizacaoDetalhe->detalhe();

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra( 562, $this->pessoa_logada, 3, "educar_escola_localizacao_lst.php" );

        $obj = new clsPmieducarEscolaLocalizacao( $this->cod_escola_localizacao,$this->pessoa_logada,null,$this->nm_localizacao,null,null,1,$this->ref_cod_instituicao );
        $editou = $obj->edita();
        if( $editou )
        {
            $escolaLocalizacaoDetalheDepois = $escolaLocalizacaoDetalhe->detalhe();
            $auditoria = new clsModulesAuditoriaGeral("escola_localizacao", $this->pessoa_logada, $this->cod_escola_localizacao);
            $auditoria->alteracao($escolaLocalizacaoDetalheAntes, $escolaLocalizacaoDetalheDepois);

            $this->mensagem .= "Edi&ccedil;&atilde;o efetuada com sucesso.<br>";
            header( "Location: educar_escola_localizacao_lst.php" );
            die();
            return true;
        }

        $this->mensagem = "Edi&ccedil;&atilde;o n&atilde;o realizada.<br>";
        echo "<!--\nErro ao editar clsPmieducarEscolaLocalizacao\nvalores obrigatorios\nif( is_numeric( $this->cod_escola_localizacao ) && is_numeric( $this->pessoa_logada ) )\n-->";
        return false;
    }

    function Excluir()
    {
        @session_start();
         $this->pessoa_logada = $_SESSION['id_pessoa'];
        @session_write_close();

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra( 562, $this->pessoa_logada, 3, "educar_escola_localizacao_lst.php" );

        $obj = new clsPmieducarEscolaLocalizacao( $this->cod_escola_localizacao,$this->pessoa_logada,null,null,null,null,0 );
        $escolaLocalizacao = $obj->detalhe();
        $excluiu = $obj->excluir();
        if( $excluiu )
        {
            $auditoria = new clsModulesAuditoriaGeral("escola_localizacao", $this->pessoa_logada, $this->cod_escola_localizacao);
            $auditoria->exclusao($escolaLocalizacao);

            $this->mensagem .= "Exclus&atilde;o efetuada com sucesso.<br>";
            header( "Location: educar_escola_localizacao_lst.php" );
            die();
            return true;
        }

        $this->mensagem = "Exclus&atilde;o n&atilde;o realizada.<br>";
        echo "<!--\nErro ao excluir clsPmieducarEscolaLocalizacao\nvalores obrigatorios\nif( is_numeric( $this->cod_escola_localizacao ) && is_numeric( $this->pessoa_logada ) )\n-->";
        return false;
    }
}

// cria uma extensao da classe base
$pagina = new clsIndexBase();
// cria o conteudo
$miolo = new indice();
// adiciona o conteudo na clsBase
$pagina->addForm( $miolo );
// gera o html
$pagina->MakeAll();
?>