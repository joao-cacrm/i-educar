(function($){
  $(document).ready(function(){

    // turma search expect an id for escola
    var $instituicaoField = getElementFor('instituicao');
    var $escolaField      = getElementFor('escola');

    var $serieField = getElementFor('serie');
    var $turmaField = getElementFor('turma');

    var handleGetTurmas = function(response) {
      var selectOptions = jsonResourcesToSelectOptions(response['options']);
      updateSelect($turmaField, selectOptions, "Selecione uma turma");
    }

    var updateTurmas = function(){
      resetSelect($turmaField);

      if ($instituicaoField.val() && $escolaField.val() && $serieField.val() && $serieField.is(':enabled')) {
        $turmaField.children().first().html('Aguarde carregando...');

        var urlForGetTurmas = getResourceUrlBuilder.buildUrl('/module/DynamicInput/turma', 'turmas', {
          instituicao_id : $instituicaoField.attr('value'),
          escola_id      : $escolaField.attr('value'),
          serie_id       : $serieField.attr('value')
        });

        var options = {
          url : urlForGetTurmas,
          dataType : 'json',
          success  : handleGetTurmas
        };

        getResources(options);
      }

      $turmaField.change();
    };

    // bind onchange event
    $serieField.change(updateTurmas);

  }); // ready
})(jQuery);
