<?php
/**
 * Sharif Judge online judge
 * @file Problems.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problems extends CI_Controller
{

	private $pid=1;
    private $edit=false;

	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');

		$this->all_assignments = $this->assignment_model->all_assignments();
	}


	// ------------------------------------------------------------------------

	/**
	 * Displays detail description of given problem
	 *
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function index()
	{
        $data = array(
			'all_problems' => $this->problem_model->all_problems(),
		);
        
		foreach ($data['all_problems'] as &$item)
		{
			$item['acpercent'] = round(($item['total_ac']/$item['total_submits'])*100).'%';
		}

		$this->twig->display('pages/problems.twig', $data);

	}

	/**
	 * Displays detail description of given problem
	 *
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function detail($problem_id = 1)
	{

		$data = $this->problem_model->get_problem($problem_id);
        $data['can_submit'] = TRUE;
		if ( ! is_numeric($problem_id) || $problem_id < 1 || $problem_id != $data['id'])
            show_404();
		$data['allowed_languages'] = explode(',',$data['allowed_languages']);
        $problems_root = rtrim($this->settings_model->get_setting('problems_root'),'/');
		$problem_dir = "$problems_root/p{$problem_id}";
		$path = "$problem_dir/desc.html";
		if (file_exists($path))
			$data['description'] = file_get_contents($path);
		$this->twig->display('pages/problem.twig', $data);
	}


	// ------------------------------------------------------------------------


	/**
	 * Edit problem description as html/markdown
	 *
	 * $type can be 'md', 'html', or 'plain'
	 *
	 * @param string $type
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function edit($problem_id = 1)
	{
		if ($this->user->level <= 1)
			show_404();
        $this->pid=$problem_id;
        $this->load->library('upload');
		//$data = $this->problem_model->get_problem($problem_id);
        $data['problem'] = $this->problem_model->get_problem($problem_id);
		if ( ! is_numeric($problem_id) || $problem_id < 1 || $problem_id != $data['problem']['id'])
            show_404();
		$this->form_validation->set_rules('ckeditor', 'ckeditor' ,''); /* todo: xss clean */
		if ($this->form_validation->run())
		{
            $this->edit=true;
            if($this->_add()){
			//$this->problem_model->save_problem($problem_id, $this->input->post('text'), $ext);
			redirect('problems/'.$problem_id);
            return true;
            }
		}
		$path = rtrim($this->settings_model->get_setting('problems_root'),'/')."/p{$problem_id}/desc.".html;
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);
        $data['edit']=true;
        $data['problem']['id']=$problem_id;
		$this->twig->display('pages/admin/add_problem.twig', $data);

	}
    public function add()
	{

		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->load->library('upload');

		if ( ! empty($_POST) )
			if ($this->_add()) // add/edit assignment
			{
				//if ( ! $this->edit) // if adding assignment (not editing)
				//{
				//   goto Assignments page
					$this->index();
					return;
				//}
			}

		$data = array(
			'edit' => $this->edit,
			'default_late_rule' => $this->settings_model->get_setting('default_late_rule'),
		);

			$names = $this->input->post('name');
			if ($names === NULL)
				$data['problem'] = 
					array(
						'id' => 1,
						'name' => 'Problem ',
						'score' => 100,
						'c_time_limit' => 500,
						'python_time_limit' => 1500,
						'java_time_limit' => 2000,
                        'pascal_time_limit' => 2000,
						'memory_limit' => 50000,
						'allowed_languages' => 'C,C++,Python 2,Python 3,Java,Free Pascal',
						'diff_cmd' => 'diff',
						'diff_arg' => '-bB',
						'is_upload_only' => 0,
                        'hard'=>0
				);
		$this->twig->display('pages/admin/add_problem.twig', $data);
	}
	private function _add()
	{

		// Check permission

		if ($this->user->level <= 1) // permission denied
			show_404();
		//$this->form_validation->set_rules('ckeditor', 'ckeditor', 'required');
		$this->form_validation->set_rules('name', 'problem name', 'required|max_length[70]');
		$this->form_validation->set_rules('score', 'problem score', 'required|integer');
		$this->form_validation->set_rules('c_time_limit', 'C/C++ time limit', 'required|integer');
		$this->form_validation->set_rules('python_time_limit', 'python time limit', 'required|integer');
		$this->form_validation->set_rules('java_time_limit', 'java time limit', 'required|integer');
        $this->form_validation->set_rules('pascal_time_limit', 'pascal time limit', 'required|integer');
		$this->form_validation->set_rules('memory_limit', 'memory limit', 'required|integer');
		$this->form_validation->set_rules('languages', 'languages', 'required');
		$this->form_validation->set_rules('diff_cmd', 'diff command', 'required');
		$this->form_validation->set_rules('diff_arg', 'diff argument', 'required');

		// Validate input data

		if ( ! $this->form_validation->run()){
            log_message('debug','failed!!!!!!!!!');
			return FALSE;
        }

		// Preparing variables
		// Adding/Editing assignment in database

		$the_id = $this->problem_model->add_problem($this->edit,$this->pid);
       /* if ($this->edit)
			$the_id = $this->edit_problem;
		else
			$the_id = $this->problem_model->new_problem_id();*/

		$problems_root = rtrim($this->settings_model->get_setting('problems_root'), '/');
		$problem_dir = "$problems_root/p{$the_id}";
		$this->messages[] = array(
			'type' => 'success',
			'text' => 'Assignment '.($this->edit?'updated':'added').' successfully.'
		);

		// Create assignment directory
		if ( ! file_exists($problem_dir) )
			mkdir($problem_dir, 0700);



		// Upload Tests (zip file)

		shell_exec('rm -f '.$problems_root.'/*.zip');
		$config = array(
			'upload_path' => $problems_root,
			'allowed_types' => 'zip',
		);
		$this->upload->initialize($config);
		$zip_uploaded = $this->upload->do_upload('tests_desc');
		$u_data = $this->upload->data();
		if ( $_FILES['tests_desc']['error'] === UPLOAD_ERR_NO_FILE )
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any zip file for tests. If needed, upload by editing assignment."
			);
		elseif ( ! $zip_uploaded )
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading tests zip file: ".$this->upload->display_errors('', '')
			);
		else
			$this->messages[] = array(
				'type' => 'success',
				'text' => "Tests (zip file) uploaded successfully."
			);



		// Extract Tests (zip file)

		if ($zip_uploaded) // if zip file is uploaded
		{
			// Create a temp directory
			$tmp_dir_name = "shj_tmp_directory";
			$tmp_dir = "$problems_root/$tmp_dir_name";
			shell_exec("rm -rf $tmp_dir; mkdir $tmp_dir;");

			// Extract new test cases and descriptions in temp directory
			$this->load->library('unzip');
			//$this->unzip->allow(array('txt', 'cpp', 'html', 'md', 'pdf'));
			$extract_result = $this->unzip->extract($u_data['full_path'], $tmp_dir);

			// Remove the zip file
			unlink($u_data['full_path']);

			if ( $extract_result )
			{
				// Remove previous test cases and descriptions
				shell_exec("cd $problem_dir;"
					." rm -rf */in; rm -rf */out; rm -f */tester.cpp; rm -f */tester.executable;"
					." rm -f */desc.html; rm -f */desc.md; rm -f */*.pdf;");
				if (glob("$tmp_dir/*.pdf"))
					shell_exec("cd $problem_dir; rm -f *.pdf");
				// Copy new test cases from temp dir
				shell_exec("cd $problems_root; cp -R $tmp_dir_name/* p{$the_id};");
				$this->messages[] = array(
					'type' => 'success',
					'text' => 'Tests (zip file) extracted successfully.'
				);
			}
			else
			{
				$this->messages[] = array(
					'type' => 'error',
					'text' => 'Error: Error extracting zip archive.'
				);
				foreach($this->unzip->errors_array() as $msg)
					$this->messages[] = array(
						'type' => 'error',
						'text' => " Zip Extraction Error: ".$msg
					);
			}

			// Remove temp directory
			shell_exec("rm -rf $tmp_dir");
		}



		// Create problem directories and parsing markdown files

			if ( ! file_exists("$problem_dir/p$the_id"))
				mkdir("$problem_dir/p$the_id", 0700);
            $this->problem_model->save_problem_description($the_id,$this->input->post('ckeditor'));
                log_message('debug',ok);
		return TRUE;
	}


}