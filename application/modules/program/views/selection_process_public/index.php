<h2 class="principal">Processos seletivos abertos</h2>

<?php if(!empty($openSelectiveProcesses)): ?>
  <div class="col-md-10 col-md-offset-1">
    <?php
      alert(function(){
        echo '<p>Aqui você pode ver os processos seletivos abertos dos programas e se inscrever no processo que desejar.</p>';
        echo "<p>Para visualizar as divulgações de um processo seletivo (como editais, retificações, comunicados, entre outros) basta clicar no ícone <i class='fa fa-bullhorn'></i>.</p>";
      });
    ?>
  </div>
  <br>

  <div class="row">
    <?php foreach($openSelectiveProcesses as $process): ?>
      <div class="col-md-6">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title"><b><?= $process->getName() ?></b></h3>
            <div class="box-tools pull-right">
              <?=
                anchor(
                  "selection_process/divulgations/{$process->getId()}",
                  "<i class='fa fa-bullhorn'></i>",
                  "class='btn btn-primary btn-sm'"
                )
              ?>
              <button class="btn btn-primary btn-sm" data-widget="collapse">
                <i class="fa fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="box-body">
              <h4>Curso: <strong><?= $courses[$process->getId()] ?></strong></h4>

              <br>
              <h4 class="text-center"><i class="fa fa-calendar"></i> Período de inscrições</h4>

              <p><b>Data de início</b>: <?= $process->getSettings()->getFormattedStartDate() ?></p>
              <p><b>Data de fim</b>: <?= $process->getSettings()->getFormattedEndDate() ?></p>
          </div>
          <div class="box-footer">
            <div class="row">
              <div class="col-md-6">
                <?=
                  anchor(
                    "selection_process/subscribe/{$process->getId()}",
                    "<i class='fa fa-plus-square'></i> Inscreva-me!",
                    "class='btn bg-blue btn-md btn-block'"
                  )
                ?>
              </div>
              <div class="col-md-6">
                <?=
                  anchor(
                    "selection_process/divulgations/{$process->getId()}",
                    "<i class='fa fa-bullhorn'></i> Divulgações",
                    "class='btn bg-green btn-md btn-block'"
                  )
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>
<?php else: ?>
  <?php callout('info', 'Não há processos seletivos abertos no momento.'); ?>
<?php endif ?>