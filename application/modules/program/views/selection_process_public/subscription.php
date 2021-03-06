<h2 class="principal">Sua inscrição no processo <b><i><?= $process->getName() ?></i></b></h2>

<div class="row">
  <div class="col-md-8 col-md-offset-2">
    <?php
      if(!$userSubscription['finalized']){
        alert(function(){
          echo "<p>Você ainda <b>não</b> finalizou sua inscrição neste processo. Para efetivar sua inscrição e enviá-la para homologação você deve finalizá-la.</p>";
        }, 'danger');
      }else{
        alert(function(){
          echo "<p>Você já finalizou sua inscrição neste processo. Veja os dados submetidos abaixo.</p>";
        });
      }
    ?>
  </div>
</div>

<div class="row">
  <?php goBackBtn("selection_process/my_processes"); ?>
</div>

<div class="row">
  <div class="col-md-10 col-md-offset-1">
    <div class="small-box bg-green">
      <div class="inner">
        <h3><?= $userSubscription['candidate_id'] ?></h3>
        <p>
          Este é o seu número de candidato(a), ele o(a) identifica durante o processo de seleção.
        </p>
        <p>Os seus resultados no processo serão divulgados através desse número.</p>
      </div>
      <div class="icon">
        <i class="fa fa-user"></i>
      </div>
    </div>
    <?php
      call_user_func(function() use ($userSubscription, $subscriptionDocs,
        $requiredDocs, $countries, $researchLines){
          include(MODULESPATH.'program/views/selection_process_public/_subscription_summary.php');
      });
    ?>
  </div>
</div>