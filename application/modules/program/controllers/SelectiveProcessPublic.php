
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'exception/UploadException.php');
require_once(MODULESPATH."/auth/constants/PermissionConstants.php");

class SelectiveProcessPublic extends MX_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('program/selectiveprocess_model', 'process_model');
        $this->load->model('program/selectiveprocessconfig_model', 'process_config_model');
        $this->load->model("program/selectiveprocessdivulgation_model", "divulgation_model");
        $this->load->model(
            'program/selectiveProcessSubscription_model',
            'process_subscription_model'
        );
    }

    // List all open selective processes
    public function index(){

        $openSelectiveProcesses = $this->process_model->getOpenSelectiveProcesses();
        $courses = $this->getCoursesName($openSelectiveProcesses);

        $data = [
            'openSelectiveProcesses' => $openSelectiveProcesses,
            'courses' => $courses
        ];

        loadTemplateSafelyByPermission(
            PermissionConstants::PUBLIC_SELECTION_PROCESS_PERMISSION,
            "program/selection_process_public/index",
            $data
        );
    }

    public function divulgations($processId){
        $selectiveProcess = $this->process_model->getById($processId);
        $processDivulgations = $this->divulgation_model->getProcessDivulgations($processId);

        $data = array(
            'selectiveprocess' => $selectiveProcess,
            'processDivulgations' => $processDivulgations
        );

        $this->load->helper('selectionprocess');

        loadTemplateSafelyByPermission(
            PermissionConstants::PUBLIC_SELECTION_PROCESS_PERMISSION,
            "program/selection_process_public/divulgations",
            $data
        );
    }

    public function subscribe($processId, $extraData=[]){
        $this->load->service(
            'program/SelectionProcessSubscription',
            'subscription_service'
        );
        $this->load->model('course_model');

        $process = $this->process_model->getById($processId);
        $requiredDocs = $this->process_config_model->getProcessDocs($processId);
        $userData = getSession()->getUserData();
        $userSubscription = $this->process_subscription_model->getByUserAndProcess(
            $processId, $userData->getId()
        );
        $subscriptionDocs = $this->subscription_service->getSubscriptionDocs($userSubscription);
        $researchLines = $this->course_model->getCourseResearchLines($process->getCourse());

        $data = [
            'process' => $process,
            'requiredDocs' => $requiredDocs,
            'subscriptionDocs' => $subscriptionDocs,
            'userData' => $userData,
            'userSubscription' => $userSubscription,
            'countries' => getAllCountries(),
            'researchLines' => makeDropdownArray(
                $researchLines,
                'id_research_line',
                'description'
            ),
            'filesErrors' => ''
        ];

        $template = !$userSubscription['finalized']
            // View to edit info and docs and then finalize subscription
            ? "program/selection_process_public/subscribe"
            // View to visualized finalized subscription data
            : "program/selection_process_public/subscription";

        loadTemplateSafelyByPermission(
            PermissionConstants::PUBLIC_SELECTION_PROCESS_PERMISSION,
            $template,
            array_merge($data, $extraData)
        );
    }

    public function subscribeTo($processId){
        $self = $this;
        withPermission(PermissionConstants::PUBLIC_SELECTION_PROCESS_PERMISSION,
            function() use($self, $processId){
                $dataOk = validateWithRule('selection_process_subscription');

                if($dataOk){

                    $this->load->service(
                        'program/SelectionProcessSubscription',
                        'subscription_service'
                    );

                    $candidateData = getSubmittedDataFor('selection_process_subscription');

                    try{
                        $this->subscription_service->newSubscription(
                            $processId,
                            $candidateData
                        );
                        $self->subscribe($processId);
                    }catch(UploadException $e){
                        getSession()->showFlashMessage(
                            'danger',
                            "Não foi possível salvar os documentos. Erro nos <a href='#required_docs'>documentos submetidos</a>."
                        );
                        $self->subscribe($processId, ['filesErrors' => $e->getErrorData()]);
                    }
                }else{
                    $self->subscribe($processId);
                }
            }
        );
    }

    public function finalizeSubscription($subscriptionId){
        $self = $this;
        withPermission(PermissionConstants::PUBLIC_SELECTION_PROCESS_PERMISSION,
            function() use($self, $subscriptionId){
                $self->load->service(
                    'program/SelectionProcessSubscription',
                    'subscription_service'
                );

                $finalized = $self->subscription_service->finalizeSubscription(
                    $subscriptionId
                );

                $status = $finalized ? 'success' : 'danger';
                $msg = $finalized
                    ? 'Sua inscrição foi finalizada com sucesso!'
                    : 'Não foi possível finalizar a inscrição informada.';
                getSession()->showFlashMessage($status, $msg);
                redirect('selection_process/public');
            }
        );
    }

    public function dowloadSubscriptionDoc($docId, $subscriptionId){
        $self = $this;
        withPermission(
            [
                PermissionConstants::PUBLIC_SELECTION_PROCESS_PERMISSION,
                PermissionConstants::SELECTION_PROCESS_PERMISSION
            ],
            function() use($self, $docId, $subscriptionId){
                $doc = $self->process_subscription_model->getSubscriptionDoc(
                    $subscriptionId,
                    $docId
                );
                downloadFile($doc['doc_path']);
            }
        );
    }

    private function getCoursesName($openSelectiveProcesses){
        $courses = array();
        if(!empty($openSelectiveProcesses)){
            $this->load->model("program/course_model");
            foreach ($openSelectiveProcesses as $process) {
                $courseId = $process->getCourse();
                $course = $this->course_model->getCourseName($courseId);
                $processId = $process->getId();
                $courses[$processId] = $course;
            }
        }
        return $courses;
    }
}
