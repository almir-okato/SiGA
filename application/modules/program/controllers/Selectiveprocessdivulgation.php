<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(MODULESPATH."/auth/constants/PermissionConstants.php");
require_once(MODULESPATH."/program/constants/SelectionProcessConstants.php");


class SelectiveProcessDivulgation extends MX_Controller {

   // Exceptions messages
    const NOTICE_FILE_ERROR = "Não foi possível salvar o arquivo. Tente novamente.";
    const NOTICE_FILE_REQUIRED = "Para a divulgação do edital você deve submeter um arquivo.";


    public function __construct(){
        parent::__construct();

        $this->load->model("program/selectiveprocess_model", "process_model");
        $this->load->model("program/selectiveprocessdivulgation_model", "divulgation_model");
        $this->load->model("program/selectiveProcessSubscription_model", "subscription_model");
    }

    public function index($selectiveProcessId){

        $selectiveProcess = $this->process_model->getById($selectiveProcessId);

        $settings = $selectiveProcess->getSettings();
        $allConfigs = $settings->isDatesDefined() && $settings->isNeededDocsSelected()
                        && $settings->isTeachersSelected();
        if(!$allConfigs){
            show_error("Você deve terminar de configurar o processo para divulgá-lo!");
        }
        else{
            $processDivulgations = $this->divulgation_model->getProcessDivulgations($selectiveProcessId);

            $data = array(
                'process' => $selectiveProcess,
                'processDivulgations' => $processDivulgations
            );

            $this->load->helper('selectionprocess');


            loadTemplateSafelyByPermission(PermissionConstants::SELECTION_PROCESS_PERMISSION, "program/selection_process/divulgations", $data);
        }
    }

    public function addDivulgation(){
        $processId = $this->input->post("process_id");
        $description = $this->input->post("description");
        $message = $this->input->post("message");
        $related_phase = $this->input->post("phase");
        $initial_divulgation = $this->input->post("initial_divulgation");

        $ids = array(
            "p" => $processId
        );

        $fieldId = "divulgation_file";
        $folderName = "process_divulgations";
        $allowedTypes = "jpg|png|pdf|jpeg";

        if (isset($_FILES['divulgation_file']) && is_uploaded_file($_FILES['divulgation_file']['tmp_name'])) {
           $filePath = uploadFile(FALSE, $ids, $fieldId, $folderName, $allowedTypes);
        }
        else{
           $filePath = NULL;
        }

        $today = new Datetime();
        $today = $today->format("Y/m/d");
        if($filePath || $filePath == NULL){
            $data = array(
                'id_process' => $processId,
                'description' => $description,
                'message' => $message,
                'initial_divulgation' => $initial_divulgation,
                'date' => $today,
            );
            $noticeUploaded = TRUE;
            if($initial_divulgation){
                $noticeUploaded = $this->divulgation_model->updateNoticeFile($processId, $filePath);
                $changed = $this->process_model->changeProcessStatus($processId, SelectionProcessConstants::DISCLOSED);
            }
            else{
                $data['file_path'] = $filePath;
                $changed = TRUE;
            }
            if($noticeUploaded && $changed){
                if($related_phase !== "0"){
                    $data['related_id_phase'] = $related_phase;
                }
                $saved = $this->divulgation_model->saveProcessDivulgation($data);
                $this->notififyNewDivulgation($data);
                if($saved){
                    $status = "success";
                    $message = "Nova divulgação realizada com sucesso.";
                }
                else{
                    $status = "danger";
                    $message = "Não foi possível fazer a nova divulgação. Tente novamente.";
                }
            }
            else{
                $status = "danger";
                $message = "Não foi possível fazer a nova divulgação. Tente novamente.";
            }

        }
        else{
            $status = "danger";
            $errors = $this->upload->display_errors();
            $message = $errors."<br>".self::NOTICE_FILE_ERROR_ON_UPLOAD.".";
        }

        $this->session->set_flashdata($status, $message);
        $this->index($processId);
    }

    private function notififyNewDivulgation($divulgation){
        $processId = $divulgation['id_process'];
        $candidates = $this->subscription_model->getProcessAllCandidates($processId);
        if(!empty($candidates)){
            $process = $this->process_model->getById($processId);
            $this->load->module('notification/notification');
            foreach ($candidates as $candidate) {
                $user = [
                    'id' => $candidate['id_user'],
                    'name' => $candidate['full_name'],
                    'email' => $candidate['email']
                ];

                $params = [
                    'subject' => "Nova divulgação no processo {$process->getName()}",
                    'link' => "selection_process/divulgations/{$processId}"
                ];


                $message = function ($params) use ($process){
                    $msg = "Informamos que há uma nova divulgação no processo <b>{$process->getName()}</b>.";
                    return $msg;
                };

                $emailMessage = function () use ($process, $divulgation, $params){
                    $divulgationsUrl = site_url($params['link']);

                    $msg = "Informamos que há uma nova divulgação no processo <b>{$process->getName()}</b>.";
                    $msg .= "<br>Veja a mensagem da <a href='{$divulgationsUrl}'>divulgação</a>:<br><br>";
                    $msg .= "<b>".$divulgation['description']."</b><br>";
                    $msg .= $divulgation['message'];
                    $msg .= "<br>";
                    return $msg;
                };

                $this->notification->notifyUser($user, $params, $message, $sender=FALSE, $onlyBar=FALSE, $emailMessage);
            }
        }
    }

    public function downloadDivulgationFile($divulgationId){

        $divulgation = $this->divulgation_model->getProcessDivulgationById($divulgationId);
        $filePath = $divulgation['file_path'];

        $this->load->helper('download');
        if(file_exists($filePath)){
            force_download($filePath, NULL);
        }
        else{
            $status = "danger";
            $message = "Nenhum arquivo encontrado.";
            $this->session->set_flashdata($status, $message);
            $processId = $divulgation['id_process'];
            redirect("selection_process/secretary_divulgations/{$processId}");
        }
    }

}
