<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(MODULESPATH."auth/constants/GroupConstants.php");
require_once(MODULESPATH."auth/constants/PermissionConstants.php");
require_once(MODULESPATH."program/domain/portal/ProgramInfo.php");
require_once(MODULESPATH."program/domain/portal/CourseInfo.php");

class Program extends MX_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->model('program/program_model');
		
	}

	public function index(){

		$programs = $this->program_model->getAllPrograms();

		$data = array(
			'programs' => $programs
		);

		loadTemplateSafelyByPermission(PermissionConstants::PROGRAMS_PERMISSION,'program/program/index', $data);
	}

	public function getAllPrograms(){

		$programs = $this->program_model->getAllPrograms();

		foreach ($programs as $arrayId => $program) {
			
			$id = $program['id_program'];
			$name = $program['program_name'];
			$acronym = $program['acronym'];
			$coordinator = $program['coordinator'];
			$contact = $program['contact'];
			$history = $program['history'];
			$summary = $program['summary'];
			$researchLine = $program['research_line'];

			$programObj = new ProgramInfo($id, $name, $acronym, $coordinator,
										$contact, $history, $summary, $researchLine);

			$programs[$arrayId] = $programObj;
		}

		return $programs;
	}

	public function getAllProgramAreas(){

		
		$programAreas = $this->program_model->getAllProgramAreas();

		if($programAreas !== FALSE){
			foreach ($programAreas as $areas){

				$areasResult[$areas['id_program_area']] = $areas['area_name'];
			}
		}else{
			$areasResult = FALSE;
		}

		return $areasResult;
	}

	public function getCoordinatorPrograms($coordinatorId){

		
		$programs = $this->program_model->getCoordinatorPrograms($coordinatorId);

		return $programs;
	}

	public function getProgramEvaluations($programId){

		
		$programEvaluations = $this->program_model->getProgramEvaluations($programId);

		return $programEvaluations;
	}

	public function getProgramEvaluation($programEvaluationId){

		
		$programEvaluation = $this->program_model->getProgramEvaluation($programEvaluationId);

		return $programEvaluation;
	}

	public function getProgramById($programId){

		
		$programs = $this->program_model->getProgramById($programId);

		return $programs;

	}

	public function getProgramAreaByProgramId($programId){
		
		$programArea = $this->program_model->getProgramAreaByProgramId($programId);

		return $programArea;

	}

	public function getProgramCourses($programId){
		
		$programCourses = $this->program_model->getProgramCourses($programId);

		if($programCourses !== FALSE){
			
			foreach ($programCourses as $arrayId => $course) {
				$id = $course['id_course'];
				$name = $course['course_name'];
				$programId = $course['id_program'];
		
				$this->load->model('program/course_model');
				$secretaries = $this->course_model->getAcademicSecretaryName($id);
				
				$this->load->module("program/teacher");
				$teachers = $this->teacher->getCourseTeachersForHomepage($id);

				$this->load->module("program/course");
				$researchLines = $this->course->getCourseResearchLines($id);

				$courseInfo = new CourseInfo($id, $name, $programId, $secretaries, $teachers, $researchLines);
				$programCourses[$arrayId] = $courseInfo;
			}
		}

		return $programCourses;
	}

	public function addCourseToProgram($courseId, $programId){

		
		$wasAdded = $this->program_model->addCourseToProgram($courseId, $programId);

		if($wasAdded){
			$insertStatus = "success";
			$insertMessage = "Curso adicionado com sucesso ao programa.";
		}else{
			$insertStatus = "danger";
			$insertMessage = "Não foi possível adicionar o curso informado.";
		}

		$session = getSession();
		$session->showFlashMessage($insertStatus, $insertMessage);
		redirect("program/editProgram/{$programId}");
	}

	public function getInformationAboutPrograms(){
		
		$programs = $this->getAllPrograms();
		$quantityOfPrograms = count($programs);
		
		//  Contains the courses, research lines and teachers
		$programs = $this->getProgramsCoursesInfo($programs);		
		$programs = $this->getProgramsWithInformation($programs);

		$this->load->model("program/coordinator_model");
		$programs = $this->coordinator_model->getCoordinatorsForHomepage($programs);

		$info = $this->getProgramInfo($programs);

		$data = array (
			'programs' => $programs,
			'secretaries' => $info['secretaries'],
			'researchLines' => $info['researchLines'],
			'coursesName' => $info['coursesName'],
			'teachers' => $info['teachers'],
			'quantityOfPrograms' => $quantityOfPrograms,
		);

		return $data;
	}

	public function removeCourseFromProgram($courseId, $programId){

		
		$wasRemoved = $this->program_model->removeCourseFromProgram($courseId, $programId);

		if($wasRemoved){
			$removeStatus = "success";
			$removeMessage = "Curso removido com sucesso do programa.";
		}else{
			$removeStatus = "danger";
			$removeMessage = "Não foi possível adicionar o curso informado.";
		}

		$session = getSession();
		$session->showFlashMessage($removeStatus, $removeMessage);
		redirect("program/editProgram/{$programId}");
	}

	public function editProgram($programId){

		$program = $this->program_model->getProgramById($programId);

		$this->load->model("auth/module_model");
		$foundGroup = $this->module_model->getGroupByGroupName(GroupConstants::COORDINATOR_GROUP);

		$this->load->module("auth/userController");
		$userGroup = $this->usercontroller->getGroup();
		
		if($foundGroup !== FALSE){
			$users = $this->usercontroller->getUsersOfGroup($foundGroup['id_group']);

			if($users !== FALSE){

				$usersForCoordinator = array();
				foreach($users as $user){
					$usersForCoordinator[$user['id']] = $user['name'];
				}
			}else{
				$usersForCoordinator = FALSE;
			}

		}else{
			$usersForCoordinator = FALSE;
		}

		$this->load->model("program/course_model");
		$courses = $this->course_model->getCoursesToProgram($programId);

		$data = array(
			'programData' => $program,
			'users' => $usersForCoordinator,
			'courses' => $courses,
			'userGroup' => $userGroup
 		);

		$groups = array(GroupConstants::ACADEMIC_SECRETARY_GROUP,GroupConstants::ADMIN_GROUP);

		loadTemplateSafelyByGroup($groups, "program/program/edit_program", $data);
	}

	public function updateProgram(){

		$programId = $this->input->post('programId');

		$programDataIsOk = $this->validatesNewProgramData();

		$session = getSession();
		if($programDataIsOk){

			$programName = $this->input->post('program_name');
			$programAcronym = $this->input->post('program_acronym');
			$programCoordinator = $this->input->post('program_coordinator');
			$openingYear = $this->input->post('opening_year');
			$programContact = $this->input->post('program_contact');
			$programHistory = $this->input->post('program_history');
			$programSummary = $this->input->post('program_summary');

			$dataIsOk = $this->verifyTheNewData($programId, $programName, $programAcronym);		

			if($dataIsOk){

				$programData = array(
					'program_name' => $programName,
					'acronym' => $programAcronym,
					'coordinator' => $programCoordinator,
					'opening_year' => $openingYear,
					'contact' => $programContact,
					'history' => $programHistory,
					'summary' => $programSummary
				);


				$wasUpdated = $this->program_model->editProgram($programId, $programData);

				if($wasUpdated){
					$insertStatus = "success";
					$insertMessage = "Programa atualizado com sucesso!";
				}else{
					$insertStatus = "danger";
					$insertMessage = "Não foi possível atualizar os registros. Tente novamente.";
				}
			}
			else{
				$insertStatus = "danger";
				$insertMessage = "Esse programa já está cadastrado.";
				$session->showFlashMessage($insertStatus, $insertMessage);
				redirect("program/editProgram/{$programId}");
			}
			$session->showFlashMessage($insertStatus, $insertMessage);
			redirect("program/editProgram/{$programId}");
			
		}
		else{
			$this->editProgram($programId);
		}
	}

	public function updateProgramArea(){
		$programId = $this->input->post('programId');

		$programArea = $this->input->post('new_program_area');

		$programData = array('id_area'=>$programArea);

		
		$wasUpdated = $this->program_model->editProgram($programId, $programData);

		if($wasUpdated){
			$insertStatus = "success";
			$insertMessage = "Programa atualizado com sucesso!";
		}else{
			$insertStatus = "danger";
			$insertMessage = "Não foi possível atualizar os registros. Tente novamente.";
		}

		$session = getSession();
		$session->showFlashMessage($insertStatus, $insertMessage);

		redirect('coordinator/coordinator_programs');

	}

	public function removeProgram($programId){

		
		$wasDeleted = $this->program_model->deleteProgram($programId);

		if($wasDeleted){
			$deleteStatus = "success";
			$deleteMessage = "Programa apagado com sucesso.";
		}else{
			$deleteStatus = "danger";
			$deleteMessage = "Não foi possível deletar o programa informado. Tente novamente.";
		}

		$session = getSession();
		$session->showFlashMessage($deleteStatus, $deleteMessage);
		redirect('program');
	}

	public function registerNewProgram(){

		$this->load->model("auth/module_model");
		$foundGroup = $this->module_model->getGroupByGroupName(GroupConstants::COORDINATOR_GROUP);

		if($foundGroup !== FALSE){

			$this->load->module("auth/userController");
			$users = $this->usercontroller->getUsersOfGroup($foundGroup['id_group']);

			if($users !== FALSE){

				$usersForCoordinator = array();
				foreach($users as $user){
					$usersForCoordinator[$user['id']] = $user['name'];
				}
			}else{
				$usersForCoordinator = FALSE;
			}

		}else{
			$usersForCoordinator = FALSE;
		}

		$programArea = $this->getAllProgramAreas();

		$data = array(
			'users' => $usersForCoordinator,
			'programArea' => $programArea
		);

		loadTemplateSafelyByPermission(PermissionConstants::COURSES_PERMISSION, "program/program/new_program", $data);
	}

	public function newProgram(){

		$programDataIsOk = $this->validatesNewProgramData();

		if($programDataIsOk){

			$programName = $this->input->post('program_name');
			$programAcronym = $this->input->post('program_acronym');
			$programCoordinator = $this->input->post('program_coordinator');
			$openingYear = $this->input->post('opening_year');
			$programArea = $this->input->post('program_area');

			$programData = array(
				'program_name' => $programName,
				'acronym' => $programAcronym,
				'coordinator' => $programCoordinator,
				'opening_year' =>$openingYear,
				'id_area' => $programArea
			);

			$programNotExists = $this->verifyIfProgramNotExists($programName, $programAcronym);

						
			$session = getSession();
			if($programNotExists){
				$wasSaved = $this->program_model->saveProgram($programData);

				if($wasSaved){
					$insertStatus = "success";
					$insertMessage = "Programa cadastrado com sucesso!";
				}
				else{
					$insertStatus = "danger";
					$insertMessage = "Não foi possível cadastrar o programa. Tente novamente.";
				}

				$session->showFlashMessage($insertStatus, $insertMessage);
				redirect('program');
			}
			else{
				$insertStatus = "danger";
				$insertMessage = "Esse programa já está cadastrado.";
				$session->showFlashMessage($insertStatus, $insertMessage);
				redirect('program/registerNewProgram');
			}
		}
		else{

			$this->registerNewProgram();
		}
	}

	/**
	 * Validates the data submitted on the new program form
	 */
	private function validatesNewProgramData(){

		// form validation
		$this->load->library("form_validation");
		$this->form_validation->set_rules("program_name", "Nome do Programa", "required|trim");
		$this->form_validation->set_rules("program_acronym", "Sigla do Programa", "required|alpha");
		$this->form_validation->set_error_delimiters("<p class='alert-danger'>", "</p>");
		$programDataStatus = $this->form_validation->run();

		return $programDataStatus;
	}

	function alpha_dash_space($str){
	    return ( ! preg_match("/^([-a-z_ ])+$/i", $str)) ? FALSE : TRUE;
	}

	private function verifyIfProgramNotExists($name, $acronym){

		$programNotExists = TRUE;

		$programs = $this->program_model->getAllPrograms();

		foreach ($programs as $program) {

			$nameExists = $name == $program['program_name'];

			$acronymExists = $acronym == $program['acronym'];

			if ($nameExists || $acronymExists){
				$programNotExists = FALSE;
				break;
			}
		}

		return $programNotExists;
	}

	// Verify if the name and acronym of the edited program already exists
	private function verifyTheNewData($id, $name, $acronym){

		$program = $this->getProgramById($id);

		$nameIsEqual = $name == $program['program_name'];
		$acronymIsEqual = $acronym == $program['acronym'];

		if($nameIsEqual && $acronymIsEqual){
			$dataIsOk = TRUE;
		}
		else{
			$dataIsOk = $this->verifyIfProgramNotExists($name, $acronym);
		}

		return $dataIsOk;
	}


	private function getProgramsWithInformation($allPrograms){
		
		$id = 0;
		$programs = array();
		if($allPrograms !== FALSE){
			foreach($allPrograms as $program){
				$summaryNonExists = empty($program->getSummary());
				$historyNonExists = empty($program->getHistory());
				$contactNonExists = empty($program->getContact());
				$researchLineNonExists = empty($program->getResearchLine());

				$coursesProgram = $program->getCourses();
				$coursesNonExists = empty($coursesProgram);
				
				if(!$summaryNonExists || !$historyNonExists || !$contactNonExists || !$researchLineNonExists || !$coursesNonExists){
						$programs[$id] = $program;
						$id++;
				}
			}
		}
		else{
			$programs = FALSE;
		}

		return $programs;
	
	}

	private function getProgramsCoursesInfo($programs){

		if($programs !== FALSE){

			foreach ($programs as $arrayId => $program) {
				$id = $program->getId();
				$coursesPrograms = $this->getProgramCourses($id);	
				$program->setCourses($coursesPrograms);
				
				$programs[$arrayId] = $program;
			}
		}

		return $programs;
	}


	private function getProgramInfo($programs){

		$academicSecretaries = array();
		$coursesResearchLines = array();
		$coursesName = array();
		$coursesTeachers = array();
		$courseTeachers = array(); 
		
		$secretaries = array();
		$researchLines = array();
		$teachers = array();
		$programsCourses = array();

		if($programs !== FALSE){

			foreach ($programs as $program) {
				$coursesProgram = $program->getCourses();
				$programId = $program->getId();
				foreach ($coursesProgram as $course) {
					$courseId = $course->getId();
					
					$courseSecretaries = $course->getAcademicSecretaries(); 
					array_push($academicSecretaries, $courseSecretaries);

					$researchLine = $course->getResearchLines(); 
					array_push($coursesResearchLines, $researchLine);
					
					$courseName = $course->getName(); 
					array_push($coursesName, $courseName);
				
					$teacher = $course->getTeachers();
					array_push($coursesTeachers, $teacher);
				}

				var_dump($academicSecretaries);
				var_dump("<br>");
				$academicSecretaries = array_unique($academicSecretaries, SORT_REGULAR);
				var_dump($academicSecretaries);
				var_dump("<br>");
				
				$secretaries[$programId] = $academicSecretaries;

				$teachers[$programId] = array_unique($coursesTeachers, SORT_REGULAR);
				
				$programsCourses[$programId] = $coursesName;

				$coursesResearchLines = array_unique($coursesResearchLines, SORT_REGULAR);
				$researchLines[$programId] = $coursesResearchLines;
			}
		}

		$info = array(
			'secretaries' => $secretaries,
			'researchLines' => $researchLines,
			'coursesName' => $programsCourses,
			'teachers' => $teachers
		);

		return $info;
	}
}
