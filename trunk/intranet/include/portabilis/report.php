<?php

#error_reporting(E_ALL);
#ini_set("display_errors", 1);

$paths_to_include = array();
$paths_to_include[] = dirname(__FILE__) . '/libs'; #/intranet/include/portabilis/libs
$paths_to_include[] = dirname(dirname(dirname(__FILE__))); #/intranet

foreach ($paths_to_include as $p)
  set_include_path(get_include_path() . PATH_SEPARATOR . $p);

require_once("include/clsBase.inc.php");
require_once("include/clsCadastro.inc.php");
require_once("include/clsBanco.inc.php");
require_once("include/pmieducar/geral.inc.php");

class clsIndexBase extends clsBase
{
	function Formular()
	{
		$this->processoAp = "999101";
	}
}

class RemoteReportFactory
{

  function __construct($settings)
  {
    $this->settings = $settings;
  }

  function build()
  {
    throw new Exception("The method 'build' from class RemoteReportFactory must be overridden!");
  }

}

class RemoteReportJasperFactory extends RemoteReportFactory
{
  function build($templateName = '', $args = array())
  {
    if (! trim($templateName))
      throw new Exception("The attribute 'templateName' must be defined!");

    require_once 'include/portabilis/libs/XML/RPC2/Client.php';

    if (! count($args))
      $args['fake_arg'] = '';

    $client = XML_RPC2_Client::create($this->settings['url']);
    $result = $client->build_report_jasper($app_name = $this->settings['app_name'], 
                                           $template_name = $templateName, 
                                           $username = $this->settings['username'], 
                                           $password = $this->settings['password'],
                                           $args = $args);

      header('Content-type: application/pdf');
      header("Content-Disposition: inline; filename={$result['filename']}");

      return base64_decode($result['report']);
  }
}

class Report extends clsCadastro
{

  function render()
  {
    if (! count($_POST))
    {
      $this->appendFixups();
      $this->renderForm();
    }
    else
    {
      $this->autoValidate();
      $this->validate();
      if (count($this->validationErrors) > 0)
        $this->onValidationError();
      else
      {
        $this->onValidationSuccess();
        $this->renderReport();
      }
    }
  }

  function renderForm()
  {

    #TODO show error messages if exists...
    $this->setForm();
    $this->nome_url_sucesso = "Exibir";
    #$miolo = new indice();
    #$pagina->addForm($miolo);
    $this->page->SetTitulo('Relat&oacute;rio' . $this->name);
    $this->page->addForm($this);
    $this->page->MakeAll(); 
  }

  function renderReport()
  {
    try
    {
      print $this->reportFactory->build($templateName = $this->templateName, $args = $this->args);
    }
    catch (Exception $e) 
    {
      echo "<html><head><link rel='stylesheet' type='text/css' href='styles/reset.css'><link rel='stylesheet' type='text/css' href='styles/portabilis.css'><link rel='stylesheet' type='text/css' href='styles/min-portabilis.css'></head>";
      echo "<body><div id='error'><h1>Erro inesperado</h1><p class='explication'>Descupe-nos ocorreu algum erro no sistema, <strong>por favor tente novamente mais tarde</strong></p><ul class='unstyled'><li><a href='/intranet/index.php'>- Voltar para o sistema</a></li><li>- Tentou mais de uma vez e o erro persiste ? Por favor, <a target='_blank' href='http://www.portabilis.com.br/site/suporte'>solicite suporte</a> ou envie um email para suporte@portabilis.com.br</li></ul><div id='detail'><p><strong>Detalhes:</strong> {$e->getMessage()}</p></div></div></body></html>";
    }
  }

  function setForm()
  {
    throw new Exception("The method 'setForm' from class Report must be overridden!");
  }

  function setTemplateName($name)
  {
    $this->templateName = $name;
  }

  function addArg($name, $value)
  {
    $this->args[$name] = $value;
  }

  function addValidationError($message)
  {
    $this->validationErrors[] = array('message' => $message);
  }

  function addRequiredField($name, $label = '')
  {
    if (! $label)
      $label = $name;

    $this->requiredFields[] = array('name' => $name, 'label' => $label);
  }


  function addRequiredFields($fieldsList)
  {
    //adiciona uma lista (array de arrays) de fields requiridos
    //ex: $this->addRequiredFields(array(array('ref_cod_curso', 'curso'), array('ref_cod_escola', 'escola')));
    
    if (! is_array($fieldsList))
      throw new Exception("Invalid type for arg 'fieldsList'");

    foreach ($fieldsList as $f)
    {
      if (! isset($f[1]))
        $f[] = $f[0];

      $this->requiredFields[] = array('name' => $f[0], 'label' => $f[1]);
    }
  }

  function autoValidate($method = 'post')
  {

    foreach ($this->requiredFields as $f)
    {
      if ($method == 'post')
        $dict = $_POST;
      elseif($method == 'get')
        $dict = $_POST;
      else
        throw new Exception('Invalid method!');

      if (! isset($dict[$f['name']]) || ! trim($dict[$f['name']]))
        $this->addValidationError('Informe um valor no campo "' . $f['label'] . '"');
        
    }
  }

  function validate()
  {
    //colocar aqui as validacoes serverside, exemplo se histórico possui todos os campos...
    //retornar dict msgs, se nenhuma msg entao esta validado ex: $this->addValidationError('O cadastro x esta em y status');
  }

  function onValidationSuccess()
  {
    //defina aqui operacoes apos o sucesso da validacao (antes de imprimir) , como os argumentos ex: $this->addArg('id', 1); $this->addArg('id_2', 2);
  }

  function onValidationError()
  {
    $msg = 'O relatório não pode ser emitido, dica(s):\n\n';
    foreach ($this->validationErrors as $e)
    {
      $msg .= '- ' . $e['message'] . '\n';
    }
    $msg .= '\npor favor, verifique esta(s) situaçõe(s) e tente novamente.';
    $msg = "<script type='text/javascript'>alert('$msg'); close();</script> ";
    print utf8_decode($msg);
  }

  function appendFixups()
  {
    $js = <<<EOT

<script type="text/javascript">
  function printReport()
  {
	  document.formcadastro.target = '_blank';
	  document.getElementById( 'btn_enviar' ).disabled =false;
	  document.formcadastro.submit();
  }
</script>

EOT;
    $this->appendOutput($js);
  }

  function __construct($name, $templateName)
  {

		@session_start();
		$this->_user_id = $_SESSION['id_pessoa'];
		@session_write_close();

    if (! $this->_user_id)
      header('Location: logof.php');

    $config = $GLOBALS['coreExt']['Config']->report->remote_factory;

    $this->reportFactorySettings = array();
    $this->reportFactorySettings['url'] = $config->url;
    $this->reportFactorySettings['app_name'] = $config->this_app_name;
    $this->reportFactorySettings['username'] = $config->username;
    $this->reportFactorySettings['password'] = $config->password;
    $this->reportFactorySettings['show_exceptions_msg'] = $config->show_exceptions_msg;

    $this->reportFactory = new RemoteReportJasperFactory($settings = $this->reportFactorySettings);

    $this->name = '';
    $this->templateName = $templateName;
    $this->args = array();

    $this->page = new clsIndexBase();
    $this->validationErrors = array();
    $this->requiredFields = array();

    #variaveis usadas pelo modulo /intranet/include/pmieducar/educar_campo_lista.php
    $this->verificar_campos_obrigatorios = True;
    $this->add_onchange_events = True;

    $this->acao_executa_submit = false;
    $this->acao_enviar = 'printReport()';
  }
}
?>
